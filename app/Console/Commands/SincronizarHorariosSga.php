<?php

namespace App\Console\Commands;

use App\Models\Becado;
use App\Services\SgaHorariosSyncService;
use App\Support\SincronizacionProgreso as Progreso;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SincronizarHorariosSga extends Command
{
    protected $signature = 'sga:sincronizar-horarios
        {periodo : ID del periodo académico}
        {codigo? : Código del periodo en el SGA (por defecto SGA_PERIODO)}
        {--escuela= : Código de una escuela (ej. 01) para probar solo esa}';
    protected $description = 'Trae programación horaria y alumnos por sección del SGA y arma horarios_clase';

    public function handle(SgaHorariosSyncService $servicio): int
    {
        $periodoId = (int) $this->argument('periodo');

        $ruta = config('sga.escuelas_json');
        if (!is_file($ruta)) {
            $this->error("Falta {$ruta}");
            Progreso::fallar($periodoId, "Falta el archivo de escuelas ({$ruta})");
            return self::FAILURE;
        }

        $escuelas = json_decode(file_get_contents($ruta), true);
        $completa = !$this->option('escuela');
        if (!$completa) {
            $escuelas = array_values(array_filter($escuelas, fn($e) => $e['codigo'] === $this->option('escuela')));
        }
        if (!$escuelas) {
            $this->error('No hay escuelas para sincronizar.');
            Progreso::fallar($periodoId, 'No hay escuelas para sincronizar.');
            return self::FAILURE;
        }

        Progreso::iniciar($periodoId, count($escuelas));

        try {
            $r = $servicio->sincronizar(
                $periodoId,
                $this->argument('codigo') ?? config('sga.periodo'),
                $escuelas,
                $completa,
                function ($e, $p, $m) use ($periodoId) {
                    $texto = "{$e['codigo']} {$e['nombre']}: {$p} filas de programación, {$m} alumnos-sección";
                    $this->line($texto);
                    Progreso::escuela($periodoId, $texto);
                },
                function ($texto) use ($periodoId) {
                    $this->line($texto);
                    Progreso::fase($periodoId, $texto);
                }
            );
        } catch (\Throwable $ex) {
            report($ex);
            Progreso::fallar($periodoId, $ex->getMessage());
            $this->error($ex->getMessage());
            return self::FAILURE;
        }

        $this->info('Listo');
        $this->table(['Dato', 'Valor'], [
            ['Horarios de clase creados', $r['horarios']],
            ['Estudiantes con horario', $r['estudiantes_con_horario']],
            ['Bloques horarios (staging)', $r['slots']],
            ['Alumnos del SGA que no están en estudiantes', $r['alumnos_sin_estudiante']],
            ['Bloques con partición', $r['slots_con_particion']],
            ['Valores de "tipo" en matriculados', json_encode($r['tipos'])],
            ['Filas tipo G (por partición / por sección / sin cruce)', json_encode($r['g'])],
            ['Cursos con grupos sin resolver', $r['ambiguos']],
        ]);

        $extra = [];
        if ($completa) {
            $sin = Becado::where('periodo_academico_id', $periodoId)->where('estado', 'activo')
                ->whereDoesntHave('estudiante.horariosClase')->count();
            $this->warn("Becados activos SIN ningún horario (no podrán sacar ticket): {$sin}");
            $extra['becados_sin_horario'] = $sin;
        }
        foreach (range(0, 7) as $i) {
            Cache::forget('comedor.elegibles.' . today()->addDays($i)->toDateString());
        }

        Progreso::terminar($periodoId, [
            'Horarios de clase creados' => $r['horarios'],
            'Estudiantes con horario' => $r['estudiantes_con_horario'],
            'Bloques horarios (staging)' => $r['slots'],
            'Alumnos del SGA que no están en estudiantes' => $r['alumnos_sin_estudiante'],
            'Bloques con partición' => $r['slots_con_particion'],
            'Cursos con grupos sin resolver' => $r['ambiguos'],
        ], $extra);

        return self::SUCCESS;
    }
}
