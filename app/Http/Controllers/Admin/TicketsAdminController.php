<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EscaneoTicket;
use App\Models\Ticket;
use App\Models\UsuarioSistema;
use App\Support\Fmt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/** Consulta de tickets y trazabilidad de su ciclo de vida (solo lectura). */
class TicketsAdminController extends Controller
{
    public const ESTADOS = ['generado', 'validado', 'recogido', 'vencido', 'no_recogido', 'cancelado'];

    public function index(Request $r)
    {
        [$f, $aviso] = $this->filtros($r);

        $tickets = $this->consulta($f)->with(['estudiante', 'ventanaAtencion'])
            ->orderByDesc('fecha')->orderByDesc('id')->paginate(20)->withQueryString();

        $conteo = $this->consulta($f)->reorder()->selectRaw('estado, COUNT(*) AS n')->groupBy('estado')->pluck('n', 'estado');

        return view('admin.tickets.index', compact('tickets', 'f', 'aviso', 'conteo'));
    }

    public function show(Ticket $ticket)
    {
        $ticket->load(['estudiante', 'ventanaAtencion', 'trabajador', 'validadoPor', 'cancelacion', 'inasistencia', 'ticketOrigen', 'ticketReasignado']);

        $escaneos = EscaneoTicket::where('ticket_id', $ticket->id)->orderBy('id')->get();
        $nombres  = UsuarioSistema::whereIn('id', $escaneos->pluck('trabajador_id')->filter())->pluck('nombre', 'id');

        return view('admin.tickets.show', compact('ticket', 'escaneos', 'nombres'));
    }

    public function exportar(Request $r)
    {
        [$f] = $this->filtros($r);
        $nombre = 'tickets_' . $f['desde'] . '_a_' . $f['hasta'] . '.csv';

        return response()->streamDownload(function () use ($f) {
            $o = fopen('php://output', 'w');
            fwrite($o, "\xEF\xBB\xBF");   // BOM: Excel abre bien las tildes
            fputcsv($o, ['Ticket', 'Fecha', 'Servicio', 'Franja', 'Codigo', 'Estudiante', 'Escuela', 'Tipo', 'Estado', 'Generado', 'Validado en puerta', 'Recogido']);

            $this->consulta($f)->with(['estudiante', 'ventanaAtencion'])->orderBy('fecha')->orderBy('id')
                ->chunk(1000, function ($lote) use ($o) {
                    foreach ($lote as $t) {
                        fputcsv($o, [
                            Fmt::ticket($t), $t->fecha->format('Y-m-d'), $t->servicio, Fmt::franja($t->ventanaAtencion),
                            $t->estudiante?->codigo_universitario, $t->estudiante?->nombreCompleto(), $t->estudiante?->escuela_profesional,
                            $t->tipo, $t->estado, $t->created_at?->format('Y-m-d H:i'),
                            $t->validado_at?->format('Y-m-d H:i'), $t->fecha_hora_recojo?->format('Y-m-d H:i'),
                        ]);
                    }
                });
            fclose($o);
        }, $nombre, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return array{0:array,1:?string} filtros ya validados + aviso si alguno se ignoró */
    private function filtros(Request $r): array
    {
        $v = Validator::make($r->query(), [
            'desde'    => ['nullable', 'date_format:Y-m-d'],
            'hasta'    => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'estado'   => ['nullable', 'in:' . implode(',', self::ESTADOS)],
            'tipo'     => ['nullable', 'in:becado,no_becado'],
            'servicio' => ['nullable', 'in:desayuno,almuerzo,cena'],
            'q'        => ['nullable', 'string', 'max:60'],
        ]);

        $aviso = $v->fails() ? 'Algún filtro no es válido y se ignoró. Revisa las fechas.' : null;
        $d = $v->fails() ? [] : $v->validated();

        $hoy = today()->toDateString();
        $d['desde'] = $d['desde'] ?? ($d['hasta'] ?? $hoy);
        $d['hasta'] = $d['hasta'] ?? $d['desde'];

        return [$d, $aviso];
    }

    private function consulta(array $f)
    {
        return Ticket::query()
            ->whereBetween('fecha', [$f['desde'], $f['hasta']])
            ->when($f['estado'] ?? null,   fn ($q, $x) => $q->where('estado', $x))
            ->when($f['tipo'] ?? null,     fn ($q, $x) => $q->where('tipo', $x))
            ->when($f['servicio'] ?? null, fn ($q, $x) => $q->where('servicio', $x))
            ->when(trim($f['q'] ?? '') !== '', function ($q) use ($f) {
                $b = trim($f['q']);
                $q->where(function ($w) use ($b) {
                    $w->where('codigo_qr', $b);
                    if (preg_match('/^TK-\d{4}-(\d+)$/i', $b, $m)) {
                        $w->orWhere('id', (int) $m[1]);
                    }
                    $w->orWhereHas('estudiante', function ($e) use ($b) {
                        $e->where('codigo_universitario', 'like', $b . '%')
                          ->orWhere('dni', $b)
                          ->orWhere('apellidos', 'like', '%' . $b . '%')
                          ->orWhere('nombres', 'like', '%' . $b . '%');
                    });
                });
            });
    }
}
