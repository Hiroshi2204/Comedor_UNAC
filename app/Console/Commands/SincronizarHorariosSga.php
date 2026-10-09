<?php

namespace App\Console\Commands;

use App\Models\Becado;
use App\Services\SgaHorariosSyncService;
use Illuminate\Console\Command;

class SincronizarHorariosSga extends Command
{
    protected $signature = 'sga:sincronizar-horarios
        {periodo : ID del periodo académico}
        {codigo? : Código del periodo en el SGA (por defecto SGA_PERIODO)}
        {--escuela= : Código de una escuela (ej. 01) para probar solo esa}';
    protected $description = 'Trae programación horaria y alumnos por sección del SGA y arma horarios_clase';

    public function handle(SgaHorariosSyncService $servicio): int
    {
        $ruta = config('sga.escuelas_json');
        if (!is_file($ruta)) {
            $this->error("Falta {$ruta}");
            return self::FAILURE;
        }

        $escuelas = json_decode(file_get_contents($ruta), true);
        $completa = !$this->option('escuela');
        if (!$completa) {
            $escuelas = array_values(array_filter($escuelas, fn ($e) => $e['codigo'] === $this->option('escuela')));
        }
        if (!$escuelas) {
            $this->error('No hay escuelas para sincronizar.');
            return self::FAILURE;
        }

        $periodoId = (int) $this->argument('periodo');
        $r = $servicio->sincronizar($periodoId, $this->argument('codigo') ?? config('sga.periodo'), $escuelas, $completa,
            fn ($e, $p, $m) => $this->line("{$e['codigo']} {$e['nombre']}: {$p} filas de programación, {$m} alumnos-sección"));

        $this->info('Listo');
        $this->table(['Dato', 'Valor'], [
            ['Horarios de clase creados', $r['horarios']],
            ['Estudiantes con horario', $r['estudiantes_con_horario']],
            ['Bloques horarios (staging)', $r['slots']],
            ['Alumnos del SGA que no están en estudiantes', $r['alumnos_sin_estudiante']],
            ['Bloques con partición', $r['slots_con_particion']],
            ['Valores de "tipo" en matriculados', json_encode($r['tipos'])],
        ]);

        if ($completa) {
            $sin = Becado::where('periodo_academico_id', $periodoId)->where('estado', 'activo')
                ->whereDoesntHave('estudiante.horariosClase')->count();
            $this->warn("Becados activos SIN ningún horario (no podrán sacar ticket): {$sin}");
        }
        return self::SUCCESS;
    }
}