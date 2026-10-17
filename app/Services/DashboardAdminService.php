<?php

namespace App\Services;

use App\Models\Sancion;
use App\Models\Ticket;
use App\Models\VentanaAtencion;
use App\Support\PanelAdmin;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardAdminService
{
    private const LIBERAN_CUPO = ['cancelado', 'vencido', 'no_recogido'];
    private const COLOR_SERVICIO = ['desayuno' => 'naranja', 'almuerzo' => 'azul', 'cena' => 'morado'];

    public function datos(Carbon $ahora): array
    {
        $porEstado = Ticket::where('fecha', $ahora->toDateString())
            ->select('estado', DB::raw('count(*) as n'))->groupBy('estado')->pluck('n', 'estado')->all();

        $periodo = $this->periodo();
        $panel = PanelAdmin::resumen($porEstado, Sancion::where('estado', 'activa')->count(), $periodo);

        return $panel + [
            'ventanas'    => $this->proximasVentanas($ahora),
            'actividad'   => $this->actividad($ahora),
            'periodo'     => $periodo,
            'actualizado' => $ahora->format('H:i'),
        ];
    }

    private function periodo(): string
    {
        $c = (string) config('sga.periodo');

        return preg_match('/^(\d{4})([A-Za-z])$/', $c, $m) ? $m[1] . '-' . strtoupper($m[2]) : $c;
    }

    /** Próximas 4 franjas de atención de hoy, con cuántos tickets lleva cada una. */
    private function proximasVentanas(Carbon $ahora): array
    {
        if ($ahora->dayOfWeekIso > 5) {
            return [];
        }

        $ventanas = VentanaAtencion::where('activo', true)
            ->where('hora_fin', '>', $ahora->format('H:i:s'))
            ->orderBy('hora_inicio')->limit(4)->get();
        if ($ventanas->isEmpty()) {
            return [];
        }

        $usados = Ticket::where('fecha', $ahora->toDateString())
            ->whereNotIn('estado', self::LIBERAN_CUPO)
            ->whereIn('ventana_atencion_id', $ventanas->pluck('id'))
            ->select('ventana_atencion_id', DB::raw('count(*) as n'))
            ->groupBy('ventana_atencion_id')->pluck('n', 'ventana_atencion_id');

        return $ventanas->map(fn($v) => [
            'rango'     => substr($v->hora_inicio, 0, 5) . ' – ' . substr($v->hora_fin, 0, 5),
            'servicio'  => ucfirst($v->servicio),
            'ocupacion' => ($usados[$v->id] ?? 0) . ' / ' . $v->capacidad_maxima,
            'color'     => self::COLOR_SERVICIO[$v->servicio] ?? 'azul',
        ])->all();
    }

    /** Los últimos movimientos del día: escaneos, cancelaciones, inasistencias y sanciones. */
    private function actividad(Carbon $ahora): array
    {
        $dia = $ahora->toDateString();
        $eventos = [];
        $ts = fn($v) => Carbon::parse($v)->timestamp;

        $escaneos = DB::table('escaneos_ticket as x')
            ->leftJoin('tickets as t', 't.id', '=', 'x.ticket_id')
            ->leftJoin('estudiantes as e', 'e.id', '=', 't.estudiante_id')
            ->whereDate('x.created_at', $dia)->orderByDesc('x.created_at')->limit(10)
            ->get(['x.created_at as cuando', 'x.puesto', 'x.exitoso', 'x.resultado', 't.servicio', 'e.codigo_universitario as codigo']);

        foreach ($escaneos as $x) {
            $quien = $x->codigo ?? 'código desconocido';

            if (!$x->exitoso) {
                $e = ['titulo' => 'Escaneo rechazado', 'detalle' => "{$quien} · " . PanelAdmin::motivoEscaneo($x->resultado), 'color' => 'gris'];
            } elseif ($x->puesto === 'puerta') {
                $e = ['titulo' => 'Ingreso validado', 'detalle' => "{$quien} · {$x->servicio}", 'color' => 'verde'];
            } else {
                $e = ['titulo' => 'Recojo', 'detalle' => "{$quien} · {$x->servicio} entregado", 'color' => 'azul'];
            }
            $eventos[] = $e + ['ts' => $ts($x->cuando)];
        }

        $cancelaciones = DB::table('cancelaciones_ticket as c')
            ->join('tickets as t', 't.id', '=', 'c.ticket_id')
            ->join('estudiantes as e', 'e.id', '=', 'c.estudiante_id')
            ->whereDate('c.fecha_cancelacion', $dia)->orderByDesc('c.fecha_cancelacion')->limit(5)
            ->get(['c.fecha_cancelacion as cuando', 't.servicio', 'e.codigo_universitario as codigo']);
        foreach ($cancelaciones as $c) {
            $eventos[] = ['titulo' => 'Cancelación', 'detalle' => "{$c->codigo} · {$c->servicio} liberó un cupo", 'color' => 'naranja', 'ts' => $ts($c->cuando)];
        }

        $inasistencias = DB::table('inasistencias as i')
            ->join('tickets as t', 't.id', '=', 'i.ticket_id')
            ->join('estudiantes as e', 'e.id', '=', 'i.estudiante_id')
            ->whereDate('i.created_at', $dia)->orderByDesc('i.created_at')->limit(5)
            ->get(['i.created_at as cuando', 't.servicio', 'e.codigo_universitario as codigo']);
        foreach ($inasistencias as $i) {
            $eventos[] = ['titulo' => 'Inasistencia', 'detalle' => "{$i->codigo} · {$i->servicio} sin usar", 'color' => 'rojo', 'ts' => $ts($i->cuando)];
        }

        $sanciones = DB::table('sanciones as s')
            ->join('estudiantes as e', 'e.id', '=', 's.estudiante_id')
            ->whereDate('s.created_at', $dia)->orderByDesc('s.created_at')->limit(5)
            ->get(['s.created_at as cuando', 'e.codigo_universitario as codigo']);
        foreach ($sanciones as $s) {
            $eventos[] = ['titulo' => 'Sanción', 'detalle' => "{$s->codigo} suspendido por inasistencias", 'color' => 'rojo', 'ts' => $ts($s->cuando)];
        }

        return PanelAdmin::ordenarActividad($eventos, 8);
    }
}
