<?php

namespace App\Console\Commands;

use App\Models\Becado;
use App\Models\HorarioClase;
use App\Services\PlanificadorComedor;
use Illuminate\Console\Command;

class DemandaComedor extends Command
{
    protected $signature = 'comedor:demanda';
    protected $description = 'Cuántos becados pueden pedir cada servicio por día de la semana y cuánto sobra del stock';

    public function handle(): int
    {
        $ids = Becado::where('estado', 'activo')->pluck('estudiante_id')->all();
        $horarios = HorarioClase::whereIn('estudiante_id', $ids)
            ->get(['estudiante_id', 'dia_semana', 'hora_inicio', 'hora_fin'])
            ->groupBy('estudiante_id');

        $planificador = new PlanificadorComedor(config('comedor'));
        $stock = config('comedor.servicios');
        $filas = [];

        foreach (['lunes', 'martes', 'miercoles', 'jueves', 'viernes'] as $dia) {
            $cuenta = ['desayuno' => 0, 'almuerzo' => 0, 'cena' => 0];
            $conClases = 0;

            foreach ($ids as $id) {
                $clases = ($horarios[$id] ?? collect())->where('dia_semana', $dia)
                    ->map(fn ($c) => [substr($c->hora_inicio, 0, 5), substr($c->hora_fin, 0, 5)])
                    ->values()->all();
                if (!$clases) continue;

                $conClases++;
                foreach (array_keys($planificador->planificar($clases)) as $s) $cuenta[$s]++;
            }

            $fila = [$dia, $conClases];
            foreach ($cuenta as $s => $n) {
                $dif = $stock[$s]['stock'] - $n;
                $fila[] = $n . ($dif >= 0 ? " (sobran {$dif})" : ' (FALTAN ' . abs($dif) . ')');
            }
            $filas[] = $fila;
        }

        $this->table(['Día', 'Becados con clases', 'Desayuno (600)', 'Almuerzo (2200)', 'Cena (300)'], $filas);
        return self::SUCCESS;
    }
}