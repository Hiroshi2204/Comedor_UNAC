<?php

namespace App\Console\Commands;

use App\Models\Estudiante;
use App\Models\HorarioClase;
use App\Services\PlanificadorComedor;
use Carbon\Carbon;
use Illuminate\Console\Command;

class VerServiciosEstudiante extends Command
{
    protected $signature = 'comedor:ver-servicios {codigo? : Código universitario (si se omite, un becado al azar)} {fecha? : YYYY-MM-DD (por defecto hoy)}';
    protected $description = 'Muestra las clases de un alumno ese día y los servicios que le corresponden';

    public function handle(): int
    {
        $est = $this->argument('codigo')
            ? Estudiante::where('codigo_universitario', $this->argument('codigo'))->first()
            : Estudiante::whereHas('becaActiva')->inRandomOrder()->first();

        if (!$est) {
            $this->error('No se encontró el estudiante.');
            return self::FAILURE;
        }

        $fecha = $this->argument('fecha') ? Carbon::parse($this->argument('fecha')) : today();
        $dia = [1 => 'lunes', 2 => 'martes', 3 => 'miercoles', 4 => 'jueves', 5 => 'viernes', 6 => 'sabado'][$fecha->dayOfWeekIso] ?? null;

        $clases = HorarioClase::where('estudiante_id', $est->id)
            ->where('dia_semana', $dia)
            ->where(fn ($q) => $q->whereNull('fecha_inicio')->orWhere('fecha_inicio', '<=', $fecha->toDateString()))
            ->where(fn ($q) => $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $fecha->toDateString()))
            ->orderBy('hora_inicio')
            ->get(['curso', 'hora_inicio', 'hora_fin', 'aula']);

        $this->info("{$est->codigo_universitario} | {$est->escuela_profesional} | " . ($est->becaActiva ? 'BECADO' : 'no becado') . " | {$fecha->toDateString()} ({$dia})");

        $this->table(['Curso', 'Inicio', 'Fin', 'Aula'], $clases->map(fn ($c) => [
            $c->curso, substr($c->hora_inicio, 0, 5), substr($c->hora_fin, 0, 5), $c->aula,
        ])->all());

        $plan = (new PlanificadorComedor(config('comedor')))->planificar(
            $clases->map(fn ($c) => [substr($c->hora_inicio, 0, 5), substr($c->hora_fin, 0, 5)])->all()
        );

        if (!$plan) {
            $this->warn('No puede sacar ticket ese día.');
        }
        foreach ($plan as $servicio => $franjas) {
            $this->line(str_pad(ucfirst($servicio), 9) . count($franjas) . ' franjas: ' . $franjas[0] . ' a ' . end($franjas));
        }
        return self::SUCCESS;
    }
}