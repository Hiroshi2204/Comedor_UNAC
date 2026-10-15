<?php

namespace App\Services;

use App\Exceptions\TicketNoDisponible;
use App\Models\Becado;
use App\Models\Estudiante;
use App\Models\HorarioClase;
use App\Models\Ticket;
use App\Models\VentanaAtencion;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class SolicitudTicketService
{
    private const DIAS = [1 => 'lunes', 2 => 'martes', 3 => 'miercoles', 4 => 'jueves', 5 => 'viernes', 6 => 'sabado'];
    private const NO_CUENTAN = ['cancelado', 'vencido', 'no_recogido'];

    private PlanificadorComedor $planificador;

    public function __construct(private ElegiblesBecados $elegibles)
    {
        $this->planificador = new PlanificadorComedor(config('comedor'));
    }

    /**
     * Qué puede hacer el estudiante para una fecha (hoy = ticket al momento; mañana = reserva, solo becados).
     */
    public function disponibilidad(Estudiante $e, ?Carbon $fecha = null, ?Carbon $ahora = null): array
    {
        $ahora ??= now();
        $fecha = ($fecha ?? $ahora)->copy()->startOfDay();
        $hoy   = $ahora->copy()->startOfDay();
        $modo  = $fecha->eq($hoy) ? 'ahora' : ($fecha->eq($hoy->copy()->addDay()) ? 'reserva' : null);
        $esBecado = $this->esBecado($e);

        if ($motivo = $this->motivoNoElegible($e, $fecha, $ahora, $modo, $esBecado)) {
            return ['elegible' => false, 'motivo' => $motivo, 'modo' => $modo, 'tipo' => null, 'servicios' => []];
        }

        $clases = $this->clases($e, $fecha);
        $plan = $this->planificador->planificar($clases);
        if (!$plan) {
            return ['elegible' => false, 'motivo' => 'No tienes clases ese día.', 'modo' => $modo, 'tipo' => null, 'servicios' => []];
        }

        $mios = Ticket::where('estudiante_id', $e->id)->where('fecha', $fecha->toDateString())->get()->keyBy('servicio');
        $ventanas = VentanaAtencion::where('activo', true)->orderBy('hora_inicio')->get()->groupBy('servicio');
        $usadosFranja = $modo === 'reserva' ? $this->usadosPorFranja($fecha) : collect();
        $libre = $modo === 'ahora'
            ? (config('comedor.permitir_ticket_en_clase') || $this->planificador->libreAhora($clases, $ahora->format('H:i')))
            : true;
        $elegibles = $modo === 'ahora' ? $this->elegibles->para($fecha) : [];

        $servicios = [];
        foreach ($plan as $servicio => $franjasLibres) {
            $cfg    = config("comedor.servicios.{$servicio}");
            $abre   = $this->hora($fecha, $cfg['inicio'])->subMinutes(config('comedor.ticket.anticipacion_min'));
            $cierra = $this->hora($fecha, $cfg['fin'])->subMinutes(config('comedor.ticket.cierre_min_antes'));
            $t      = $mios[$servicio] ?? null;
            $lista  = [];
            $cupos  = 0;

            if ($modo === 'reserva') {
                foreach ($ventanas[$servicio] ?? [] as $v) {
                    $ini = substr($v->hora_inicio, 0, 5);
                    if (!in_array($ini, $franjasLibres, true)) continue;
                    $c = max(0, $v->capacidad_maxima - ($usadosFranja[$v->id] ?? 0));
                    $lista[] = ['ventana_id' => $v->id, 'inicio' => $ini, 'fin' => substr($v->hora_fin, 0, 5), 'cupos' => $c];
                    $cupos += $c;
                }
            } else {
                $cupos = $this->cupos($servicio, $esBecado, $fecha, $ahora, $elegibles[$servicio] ?? 0);
            }

            $estado = match (true) {
                $t && $t->estado === 'cancelado'     => 'cancelado',
                $t !== null                          => 'con_ticket',
                $modo === 'reserva' && $cupos <= 0   => 'agotado',
                $modo === 'reserva'                  => 'reservable',
                $ahora->lt($abre)                    => 'aun_no_abre',
                $ahora->gt($cierra)                  => 'cerrado',
                !$libre                              => 'en_clase',
                $cupos <= 0                          => 'agotado',
                default                              => 'disponible',
            };

            $servicios[$servicio] = [
                'estado'  => $estado,
                'ticket'  => $t,
                'abre'    => $abre->format('H:i'),
                'cierra'  => $cierra->format('H:i'),
                'cupos'   => $cupos,
                'franjas' => $lista,   // solo en modo reserva
            ];
        }

        return ['elegible' => true, 'motivo' => null, 'modo' => $modo, 'tipo' => $esBecado ? 'becado' : 'no_becado', 'servicios' => $servicios];
    }

    /**
     * Fecha de hoy: ticket al momento (ignora $ventanaId). Fecha de mañana: reserva en la franja $ventanaId.
     */
    public function solicitar(Estudiante $e, string $servicio, ?int $ventanaId = null, ?Carbon $fecha = null, ?Carbon $ahora = null): Ticket
    {
        $ahora ??= now();
        $fecha = ($fecha ?? $ahora)->copy()->startOfDay();

        $disp = $this->disponibilidad($e, $fecha, $ahora);
        if (!$disp['elegible']) {
            throw new TicketNoDisponible($disp['motivo']);
        }

        $s = $disp['servicios'][$servicio] ?? throw new TicketNoDisponible('Ese servicio no te corresponde ese día según tu horario.');
        $esBecado = $disp['tipo'] === 'becado';
        $reserva  = $disp['modo'] === 'reserva';

        match ($s['estado']) {
            'disponible', 'reservable' => null,
            'con_ticket'  => throw new TicketNoDisponible("Ya tienes un ticket de {$servicio} para ese día."),
            'cancelado'   => throw new TicketNoDisponible("Cancelaste el {$servicio} de ese día y no puedes volver a pedirlo."),
            'aun_no_abre' => throw new TicketNoDisponible("El {$servicio} aún no abre. Podrás pedirlo desde las {$s['abre']}."),
            'cerrado'     => throw new TicketNoDisponible("Ya no se generan tickets de {$servicio} (cerró a las {$s['cierra']})."),
            'en_clase'    => throw new TicketNoDisponible('Estás en clase o por entrar a una. Pide tu ticket cuando estés libre.'),
            'agotado'     => throw new TicketNoDisponible($esBecado
                ? "Se agotaron los cupos de {$servicio} para ese día."
                : "Por ahora no hay cupos para no becados de {$servicio}. Si sobran, se abren en los últimos "
                . config('comedor.no_becados.libera_min_antes_cierre') . ' minutos.'),
        };

        if ($reserva && !collect($s['franjas'])->contains('ventana_id', $ventanaId)) {
            throw new TicketNoDisponible('Elige una franja disponible.');
        }

        try {
            return DB::transaction(function () use ($e, $servicio, $ventanaId, $fecha, $ahora, $esBecado, $reserva) {
                // bloquea las franjas del servicio (siempre en el mismo orden): serializa las solicitudes y protege el cupo
                $franjas = VentanaAtencion::where('servicio', $servicio)->orderBy('id')->lockForUpdate()->get();
                $cfg = config("comedor.servicios.{$servicio}");
                $tope = $this->hora($fecha, $cfg['fin']);

                if ($reserva) {
                    $v = $franjas->firstWhere('id', $ventanaId) ?? throw new TicketNoDisponible('Elige una franja disponible.');
                    $usados = Ticket::where('ventana_atencion_id', $v->id)->where('fecha', $fecha->toDateString())
                        ->whereNotIn('estado', self::NO_CUENTAN)->count();
                    if ($usados >= $v->capacidad_maxima) {
                        throw new TicketNoDisponible('Esa franja ya no tiene cupos. Elige otra.');
                    }
                    $vence = $this->hora($fecha, $v->hora_fin)->addMinutes(config('comedor.reserva.tolerancia_min'));
                } else {
                    $elegibles = $this->elegibles->para($fecha)[$servicio] ?? 0;
                    if ($this->cupos($servicio, $esBecado, $fecha, $ahora, $elegibles) <= 0) {
                        throw new TicketNoDisponible("Se agotaron los cupos de {$servicio}.");
                    }
                    $hms = $ahora->format('H:i:s');
                    $v = $franjas->first(fn($f) => $f->hora_inicio <= $hms && $hms < $f->hora_fin)
                        ?? ($hms < $franjas->first()->hora_inicio ? $franjas->first() : $franjas->last());
                    $vence = $ahora->copy()->addMinutes(config('comedor.ticket.vigencia_min'));
                }
                if ($vence->gt($tope)) $vence = $tope;

                return Ticket::create([
                    'estudiante_id'        => $e->id,
                    'periodo_academico_id' => $e->periodo_academico_id,
                    'ventana_atencion_id'  => $v->id,
                    'tipo'                 => $esBecado ? 'becado' : 'no_becado',
                    'servicio'             => $servicio,
                    'fecha'                => $fecha->toDateString(),
                    'estado'               => 'generado',
                    'vence_at'             => $vence,
                ]);
            });
        } catch (QueryException $ex) {
            if ($ex->getCode() === '23000') { // doble clic: choca con unique(estudiante, fecha, servicio)
                throw new TicketNoDisponible("Ya tienes un ticket de {$servicio} para ese día.");
            }
            throw $ex;
        }
    }

    private function motivoNoElegible(Estudiante $e, Carbon $fecha, Carbon $ahora, ?string $modo, bool $esBecado): ?string
    {
        if ($fecha->dayOfWeekIso > 6) return 'El comedor atiende de lunes a sábado.';
        if ($modo === null) return 'Solo puedes pedir tickets para hoy o para mañana.';
        if (!$e->matriculado) return 'No figuras como matriculado.';
        if ($e->sancionActiva) return 'Tienes una sanción por inasistencias. Comunícate con el administrador del comedor para que la levante.';

        if ($modo === 'reserva') {
            if (!$esBecado) return 'Los no becados solo pueden generar su ticket el mismo día.';

            $abre = $fecha->copy()->subDay()->setTimeFromTimeString(config('comedor.reserva.abre'));
            if ($ahora->lt($abre)) {
                return 'Podrás reservar desde las ' . config('comedor.reserva.abre') . ' del día anterior.';
            }
        }
        return null;
    }

    private function esBecado(Estudiante $e): bool
    {
        return Becado::where('estudiante_id', $e->id)
            ->where('periodo_academico_id', $e->periodo_academico_id)->where('estado', 'activo')->exists();
    }

    private function usadosPorFranja(Carbon $fecha)
    {
        return Ticket::where('fecha', $fecha->toDateString())->whereNotIn('estado', self::NO_CUENTAN)
            ->select('ventana_atencion_id', DB::raw('count(*) as n'))
            ->groupBy('ventana_atencion_id')->pluck('n', 'ventana_atencion_id');
    }

    /** [total de tickets vigentes, de esos cuántos son de becados] */
    private function conteos(string $servicio, Carbon $fecha): array
    {
        $q = Ticket::where('fecha', $fecha->toDateString())->where('servicio', $servicio)
            ->whereNotIn('estado', self::NO_CUENTAN);

        return [(clone $q)->count(), (clone $q)->where('tipo', 'becado')->count()];
    }

    /** Cupos del servicio para ticket al momento. Los becados toman del stock; los no becados, de lo que no está reservado. */
    private function cupos(string $servicio, bool $esBecado, Carbon $fecha, Carbon $ahora, int $elegibles): int
    {
        $cfg = config("comedor.servicios.{$servicio}");
        [$total, $becados] = $this->conteos($servicio, $fecha);

        if ($esBecado) {
            return max(0, $cfg['stock'] - $total);
        }

        $minParaCierre = intdiv(max(0, $this->hora($fecha, $cfg['fin'])->timestamp - $ahora->timestamp), 60);

        return CupoComedor::paraNoBecados(
            $cfg['stock'],
            $total,
            $becados,
            $elegibles,
            config('comedor.no_becados.factor_reserva'),
            $minParaCierre,
            config('comedor.no_becados.libera_min_antes_cierre')
        );
    }

    private function hora(Carbon $base, string $hhmm): Carbon
    {
        return $base->copy()->setTimeFromTimeString($hhmm);
    }

    private function clases(Estudiante $e, Carbon $fecha): array
    {
        $f = $fecha->toDateString();

        return HorarioClase::where('estudiante_id', $e->id)
            ->where('periodo_academico_id', $e->periodo_academico_id)
            ->where('dia_semana', self::DIAS[$fecha->dayOfWeekIso])
            ->where(fn($q) => $q->whereNull('fecha_inicio')->orWhere('fecha_inicio', '<=', $f))
            ->where(fn($q) => $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $f))
            ->get(['hora_inicio', 'hora_fin'])
            ->map(fn($c) => [substr($c->hora_inicio, 0, 5), substr($c->hora_fin, 0, 5)])
            ->all();
    }
}
