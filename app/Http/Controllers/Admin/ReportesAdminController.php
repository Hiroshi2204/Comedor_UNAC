<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inasistencia;
use App\Models\Sancion;
use App\Models\Ticket;
use App\Support\Periodo;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportesAdminController extends Controller
{
    private const RANGOS = ['semana' => 'esta semana', 'mes' => 'este mes', 'periodo' => 'todo el período', 'custom' => 'personalizado'];

    public function index(Request $r)
    {
        [$desde, $hasta, $rango] = $this->rango($r);
        $datos = $this->calcular($desde, $hasta, $rango === 'periodo');

        return view('admin.reportes.index', $datos + [
            'rango' => $rango, 'rangos' => self::RANGOS, 'desde' => $desde, 'hasta' => $hasta, 'periodo' => Periodo::etiqueta(),
        ]);
    }

    public function exportar(Request $r)
    {
        [$desde, $hasta, $rango] = $this->rango($r);

        $q = Ticket::whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->when($rango === 'periodo' && Periodo::id(), fn ($w) => $w->where('periodo_academico_id', Periodo::id()))
            ->selectRaw('fecha, servicio, estado, tipo, COUNT(*) AS n')
            ->groupBy('fecha', 'servicio', 'estado', 'tipo')->orderBy('fecha')->get();

        $nombre = 'reporte_comedor_' . $desde->toDateString() . '_a_' . $hasta->toDateString() . '.csv';

        return response()->streamDownload(function () use ($q) {
            $o = fopen('php://output', 'w');
            fwrite($o, "\xEF\xBB\xBF");
            fputcsv($o, ['Fecha', 'Servicio', 'Tipo', 'Estado', 'Tickets']);
            foreach ($q as $f) {
                fputcsv($o, [Carbon::parse($f->fecha)->format('Y-m-d'), $f->servicio, $f->tipo, $f->estado, $f->n]);
            }
            fclose($o);
        }, $nombre, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return array{0:Carbon,1:Carbon,2:string} */
    private function rango(Request $r): array
    {
        $d = $r->validate([
            'rango' => ['nullable', 'in:' . implode(',', array_keys(self::RANGOS))],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
        ]);
        $rango = $d['rango'] ?? 'semana';

        switch ($rango) {
            case 'mes':
                return [today()->startOfMonth(), today(), 'mes'];
            case 'periodo':
                $t = Ticket::when(Periodo::id(), fn ($w) => $w->where('periodo_academico_id', Periodo::id()));
                $min = (clone $t)->min('fecha'); $max = (clone $t)->max('fecha');
                return [$min ? Carbon::parse($min) : today(), $max ? Carbon::parse($max) : today(), 'periodo'];
            case 'custom':
                if (!empty($d['desde'])) {
                    $a = Carbon::parse($d['desde']);
                    return [$a, Carbon::parse($d['hasta'] ?? $d['desde']), 'custom'];
                }
                // sin fechas: vuelve a la semana
            default:
                return [today()->startOfWeek(), today(), 'semana'];
        }
    }

    private function calcular(Carbon $desde, Carbon $hasta, bool $soloPeriodo): array
    {
        $a = $desde->toDateString(); $b = $hasta->toDateString();
        $pid = $soloPeriodo ? Periodo::id() : null;

        $filas = Ticket::whereBetween('fecha', [$a, $b])
            ->when($pid, fn ($w) => $w->where('periodo_academico_id', $pid))
            ->selectRaw('fecha, servicio, estado, COUNT(*) AS n')->groupBy('fecha', 'servicio', 'estado')->get();

        $total = (int) $filas->sum('n');
        $por   = fn (string $e) => (int) $filas->where('estado', $e)->sum('n');
        $recogidos = $por('recogido'); $vencidos = $por('vencido'); $noRec = $por('no_recogido');
        $cancelados = $por('cancelado');
        $pct = fn (int $x) => $total > 0 ? round($x * 100 / $total, 1) : 0;

        // asistencia = recogidos / (recogidos + inasistencias + no recogidos): solo lo que ya cerró
        $asist = fn ($grupo) => ($c = $grupo->sum(fn ($f) => in_array($f->estado, ['recogido', 'vencido', 'no_recogido']) ? $f->n : 0)) > 0
            ? (int) round($grupo->where('estado', 'recogido')->sum('n') * 100 / $c) : null;

        $dias = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
        $porDia = collect(range(1, 6))->map(fn ($i) => [
            'etiqueta' => $dias[$i - 1],
            'valor'    => $asist($filas->filter(fn ($f) => Carbon::parse($f->fecha)->dayOfWeekIso === $i)),
        ]);

        $porServicio = collect(['desayuno', 'almuerzo', 'cena'])->map(function ($s) use ($filas, $asist) {
            $g = $filas->where('servicio', $s);
            return (object) [
                'servicio' => ucfirst($s), 'generados' => (int) $g->sum('n'),
                'recogidos' => (int) $g->where('estado', 'recogido')->sum('n'),
                'asistencia' => $asist($g),
            ];
        });

        $diasConTickets = $filas->pluck('fecha')->map(fn ($f) => Carbon::parse($f)->toDateString())->unique()->count();

        return [
            'total'       => $total,
            'porDiaHabil' => $diasConTickets > 0 ? (int) round($total / $diasConTickets) : 0,
            'recogidos'   => $recogidos,
            'pRecogidos'  => $pct($recogidos),
            'inasist'     => Inasistencia::whereBetween('fecha', [$a, $b])->when($pid, fn ($w) => $w->where('periodo_academico_id', $pid))->count(),
            'sanciones'   => Sancion::whereBetween('created_at', [$desde->copy()->startOfDay(), $hasta->copy()->endOfDay()])->count(),
            'estados'     => [
                ['Recogidos', $recogidos, $pct($recogidos), 'b-verde'],
                ['Pendientes', $por('generado') + $por('validado'), $pct($por('generado') + $por('validado')), 'b-azul'],
                ['No asistieron', $vencidos, $pct($vencidos), 'b-rojo'],
                ['Sin recoger', $noRec, $pct($noRec), 'b-morado'],
                ['Cancelados', $cancelados, $pct($cancelados), 'b-naranja'],
            ],
            'porDia'      => $porDia,
            'porServicio' => $porServicio,
        ];
    }
}
