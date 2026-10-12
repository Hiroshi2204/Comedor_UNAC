<?php

namespace App\Services;

use App\Models\Becado;
use App\Models\HorarioClase;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class ElegiblesBecados
{
    private const DIAS = [1 => 'lunes', 2 => 'martes', 3 => 'miercoles', 4 => 'jueves', 5 => 'viernes'];

    public function para(Carbon $fecha): array
    {
        $cero = ['desayuno' => 0, 'almuerzo' => 0, 'cena' => 0];
        $dia = self::DIAS[$fecha->dayOfWeekIso] ?? null;
        if (!$dia) return $cero;

        return Cache::remember('comedor.elegibles.' . $fecha->toDateString(), now()->addHours(6), function () use ($fecha, $dia, $cero) {
            $f = $fecha->toDateString();
            $ids = Becado::activos()->pluck('estudiante_id')->all();

            $horarios = HorarioClase::whereIn('estudiante_id', $ids)->where('dia_semana', $dia)
                ->where(fn ($q) => $q->whereNull('fecha_inicio')->orWhere('fecha_inicio', '<=', $f))
                ->where(fn ($q) => $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $f))
                ->get(['estudiante_id', 'hora_inicio', 'hora_fin'])->groupBy('estudiante_id');

            $planificador = new PlanificadorComedor(config('comedor'));
            $cuenta = $cero;

            foreach ($horarios as $clases) {
                $plan = $planificador->planificar(
                    $clases->map(fn ($c) => [substr($c->hora_inicio, 0, 5), substr($c->hora_fin, 0, 5)])->all()
                );
                foreach (array_keys($plan) as $s) $cuenta[$s]++;
            }
            return $cuenta;
        });
    }
}