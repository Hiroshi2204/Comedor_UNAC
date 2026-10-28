<?php

namespace App\Http\Controllers\Trabajador;

use App\Http\Controllers\Controller;
use App\Models\EscaneoTicket;
use App\Models\Estudiante;
use App\Models\Ticket;
use App\Models\VentanaAtencion;
use App\Services\EscaneoTicketService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Pantallas del trabajador. El puesto decide qué hace:
 *   puerta -> valida el ingreso ('puerta'),  cocina -> entrega la comida ('entrega').
 * Las reglas y el registro de escaneos los hace EscaneoTicketService (ya existente).
 */
class PanelTrabajadorController extends Controller
{
    public function __construct(private EscaneoTicketService $escaneos) {}

    public function inicio(Request $r)
    {
        $u    = $r->attributes->get('trabajador');
        $hoy  = today();
        $ahora = now();
        $servicio = in_array($r->query('servicio'), ['desayuno', 'almuerzo', 'cena'], true) ? $r->query('servicio') : null;

        return view('trabajador.inicio', [
            'u'         => $u,
            'servicio'  => $servicio,
            'metricas'  => $this->metricas($u, $hoy, $servicio),
            'ventana'   => $this->ventana($hoy, $ahora),
            'operativo' => $this->servicioOperativo($ahora),
            'atendidos' => $this->atendidos($u, $hoy),
        ]);
    }

    /** Recibe el QR (cámara o lector) o el código universitario y lleva a la pantalla de verificación. */
    public function buscar(Request $r)
    {
        $u = $r->attributes->get('trabajador');
        $q = trim($r->validate(['codigo' => ['required', 'string', 'max:100']])['codigo']);
        $accion = $this->accion($u);

        $ticket = Ticket::where('codigo_qr', $q)->first();

        if (!$ticket) {
            $e = Estudiante::where('codigo_universitario', $q)->first();
            $ticket = $e ? $this->ticketDelDia($e, $accion) : null;

            if (!$ticket) {
                // un QR desconocido queda registrado; un código de estudiante mal escrito no
                if (strlen($q) >= 30) {
                    EscaneoTicket::create([
                        'ticket_id' => null,
                        'codigo_qr' => Str::limit($q, 64, ''),
                        'puesto' => $accion,
                        'trabajador_id' => $u->id,
                        'exitoso' => false,
                        'resultado' => 'no_existe',
                    ]);
                }
                return redirect()->route('trabajador.inicio')->withErrors([
                    'codigo' => $e ? 'Ese estudiante no tiene tickets para hoy.' : 'No se encontró un ticket ni un estudiante con ese código.',
                ]);
            }
        }

        // si ya se sabe que no procede, el intento rechazado queda registrado
        $c = $this->escaneos->consultar($ticket, $accion);
        if ($c['rechazo']) {
            EscaneoTicket::create([
                'ticket_id' => $ticket->id,
                'codigo_qr' => $ticket->codigo_qr,
                'puesto' => $accion,
                'trabajador_id' => $u->id,
                'exitoso' => false,
                'resultado' => $c['rechazo'][0],
            ]);
        }

        return redirect()->route('trabajador.ticket', $ticket);
    }

    public function ver(Request $r, Ticket $ticket)
    {
        $u = $r->attributes->get('trabajador');
        $c = $this->escaneos->consultar($ticket, $this->accion($u));

        return view('trabajador.verificar', [
            'u'          => $u,
            'ticket'     => $ticket,
            'ficha'      => $c['ficha'],
            'rechazo'    => $c['rechazo'],
            'matriculado' => (bool) ($ticket->estudiante->matriculado ?? true),
        ]);
    }

    public function confirmar(Request $r, Ticket $ticket)
    {
        $u = $r->attributes->get('trabajador');
        $accion = $this->accion($u);

        // el servicio vuelve a evaluar todo dentro de una transacción con bloqueo (dos trabajadores, doble toque…)
        $res = $accion === 'puerta'
            ? $this->escaneos->validarEnPuerta($ticket->codigo_qr, $u)
            : $this->escaneos->entregar($ticket->codigo_qr, $u);

        if (!$res['ok']) {
            return redirect()->route('trabajador.ticket', $ticket)->withErrors(['ticket' => $res['mensaje']]);
        }

        $nombre = Str::title(mb_strtolower($res['ticket']['estudiante']));
        $texto  = $accion === 'puerta' ? 'Ingreso registrado' : 'Entrega registrada';

        return redirect()->route('trabajador.inicio')->with('ok', "{$texto}: {$nombre} ({$res['ticket']['servicio']}).");
    }

    public function actividad(Request $r)
    {
        $u = $r->attributes->get('trabajador');
        $filtro = in_array($r->query('ver'), ['aceptados', 'rechazados'], true) ? $r->query('ver') : null;

        $base = EscaneoTicket::where('trabajador_id', $u->id)->whereDate('created_at', today());
        $totales = [
            'todos'      => (clone $base)->count(),
            'aceptados'  => (clone $base)->where('exitoso', true)->count(),
            'rechazados' => (clone $base)->where('exitoso', false)->count(),
        ];

        $escaneos = $base
            ->when($filtro, fn($q) => $q->where('exitoso', $filtro === 'aceptados'))
            ->with('ticket.estudiante')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('trabajador.actividad', ['u' => $u, 'filtro' => $filtro, 'totales' => $totales, 'escaneos' => $escaneos]);
    }

    // ---------------------------------------------------------------- apoyo

    private function accion($u): string
    {
        return $u->esDePuerta() ? 'puerta' : 'entrega';
    }

    /** El ticket de hoy que le toca atender a este puesto (puerta: el que aún no entró; cocina: el que ya entró). */
    private function ticketDelDia(Estudiante $e, string $accion): ?Ticket
    {
        $tickets = Ticket::where('estudiante_id', $e->id)->whereDate('fecha', today())->orderByDesc('id')->get();

        foreach ($accion === 'puerta' ? ['generado', 'validado'] : ['validado', 'generado'] as $estado) {
            if ($t = $tickets->firstWhere('estado', $estado)) {
                return $t;
            }
        }
        return $tickets->first();
    }

    private function metricas($u, Carbon $hoy, ?string $servicio): array
    {
        $c = Ticket::whereDate('fecha', $hoy)
            ->when($servicio, fn($q, $s) => $q->where('servicio', $s))
            ->selectRaw('estado, COUNT(*) AS n')->groupBy('estado')->pluck('n', 'estado');
        $n = fn(string $k) => (int) ($c[$k] ?? 0);

        $esperados = $n('generado') + $n('validado') + $n('recogido') + $n('no_recogido') + $n('vencido');

        if ($u->esDePuerta()) {
            return [
                ['etiqueta' => 'Esperados',          'valor' => $esperados,                                      'color' => 'azul'],
                ['etiqueta' => 'Ingresaron',         'valor' => $n('validado') + $n('recogido') + $n('no_recogido'), 'color' => 'verde'],
                ['etiqueta' => 'Pendientes',         'valor' => $n('generado'),                                  'color' => 'ambar'],
                ['etiqueta' => 'No se presentaron',  'valor' => $n('vencido'),                                   'color' => 'rojo'],
            ];
        }

        return [
            ['etiqueta' => 'Esperados',    'valor' => $esperados,                           'color' => 'azul'],
            ['etiqueta' => 'Recogidos',    'valor' => $n('recogido'),                       'color' => 'verde'],
            ['etiqueta' => 'Pendientes',   'valor' => $n('generado') + $n('validado'),      'color' => 'ambar'],
            ['etiqueta' => 'No recogidos', 'valor' => $n('no_recogido') + $n('vencido'),    'color' => 'rojo'],
        ];
    }

    /** La ventana en curso o, si no hay, la siguiente de hoy. */
    private function ventana(Carbon $hoy, Carbon $ahora): ?array
    {
        $hms = $ahora->format('H:i:s');
        $v = VentanaAtencion::where('activo', true)->where('hora_fin', '>', $hms)->orderBy('hora_inicio')->first();
        if (!$v) {
            return null;
        }

        $asignados = Ticket::whereDate('fecha', $hoy)->where('ventana_atencion_id', $v->id)->where('estado', '!=', 'cancelado')->count();
        $cap = max(1, (int) $v->capacidad_maxima);

        return [
            'inicio'     => substr($v->hora_inicio, 0, 5),
            'fin'        => substr($v->hora_fin, 0, 5),
            'servicio'   => $v->servicio,
            'asignados'  => $asignados,
            'capacidad'  => $v->capacidad_maxima,
            'porcentaje' => (int) round($asignados * 100 / $cap),
            'en_curso'   => $v->hora_inicio <= $hms,
        ];
    }

    /** Servicio que está atendiendo ahora (con la anticipación y la gracia de cierre configuradas). */
    private function servicioOperativo(Carbon $ahora): ?string
    {
        $ant = (int) config('comedor.ticket.anticipacion_min', 5);
        $gra = (int) config('comedor.escaneo.gracia_cierre_min', 10);

        foreach (config('comedor.servicios') as $nombre => $s) {
            $desde = $ahora->copy()->setTimeFromTimeString($s['inicio'])->subMinutes($ant);
            $hasta = $ahora->copy()->setTimeFromTimeString($s['fin'])->addMinutes($gra);
            if ($ahora->between($desde, $hasta)) {
                return $nombre;
            }
        }
        return null;
    }

    /** Últimos tickets que este trabajador atendió hoy. */
    private function atendidos($u, Carbon $hoy): array
    {
        $q = Ticket::with(['estudiante', 'ventanaAtencion']);

        if ($u->esDePuerta()) {
            $q->where('validado_por', $u->id)->whereDate('validado_at', $hoy)->orderByDesc('validado_at');
            $hora = fn(Ticket $t) => $t->validado_at;
        } else {
            $q->where('trabajador_id', $u->id)->whereDate('fecha_hora_recojo', $hoy)->orderByDesc('fecha_hora_recojo');
            $hora = fn(Ticket $t) => $t->fecha_hora_recojo;
        }

        return $q->limit(8)->get()->map(fn(Ticket $t) => [
            'codigo'  => $t->estudiante->codigo_universitario,
            'nombre'  => Str::title(mb_strtolower($t->estudiante->nombreCompleto())),
            'ventana' => $t->ventanaAtencion
                ? substr($t->ventanaAtencion->hora_inicio, 0, 5) . '–' . substr($t->ventanaAtencion->hora_fin, 0, 5) : null,
            'hora'    => $hora($t)?->format('H:i'),
        ])->all();
    }
}
