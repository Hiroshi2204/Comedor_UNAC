<?php

namespace App\Services;

use App\Models\Estudiante;
use Illuminate\Support\Facades\DB;

class SgaHorariosSyncService
{
    public function __construct(private SgaClient $sga) {}

    public function sincronizar(int $periodoId, string $codigoPeriodo, array $escuelas, bool $completa, ?callable $alAvanzar = null): array
    {
        $calendarioId = config('sga.calendario')
            ?? throw new \RuntimeException('Falta SGA_CALENDARIO_ACADEMICO_ID en el .env');

        $programacion = [];
        $matriculados = [];

        foreach ($escuelas as $e) {
            $p = $this->sga->llamar('obtener-programacion-horaria', $calendarioId, $e['ad_org_id']);
            if (($p['calendario']['codigo'] ?? null) !== $codigoPeriodo) {
                throw new \RuntimeException('El calendario del .env corresponde a ' . ($p['calendario']['codigo'] ?? '?') . " y se esperaba {$codigoPeriodo}.");
            }
            $m = $this->sga->llamar('obtener-alumnos-matriculados', $calendarioId, $e['ad_org_id']);

            $programacion = array_merge($programacion, $p['programacionHoraria'] ?? []);
            // solo lo necesario: no se retienen nombres, DNI ni correos
            foreach ($m['alumnosMatriculados'] ?? [] as $a) {
                $matriculados[] = ['eduSeccionId' => $a['eduSeccionId'] ?? null, 'codAlu' => $a['codAlu'] ?? null, 'tipo' => $a['tipo'] ?? null];
            }
            if ($alAvanzar) $alAvanzar($e, count($p['programacionHoraria'] ?? []), count($m['alumnosMatriculados'] ?? []));
        }

        $estudiantes = Estudiante::pluck('id', 'codigo_universitario')->all();
        $r = SgaHorariosCruce::cruzar($programacion, $matriculados, $estudiantes);

        DB::transaction(function () use ($r, $periodoId, $codigoPeriodo, $completa) {
            if ($completa) {
                DB::table('horarios_clase')->where('periodo_academico_id', $periodoId)->delete();
                DB::table('stg_horarios_sga')->where('periodo_academico', $codigoPeriodo)->delete();
            } else { // corrida parcial de prueba: solo lo que se vuelve a cargar
                $estIds = array_values(array_unique(array_column($r['horarios'], 0)));
                foreach (array_chunk($estIds, 1000) as $bloque) {
                    DB::table('horarios_clase')->where('periodo_academico_id', $periodoId)->whereIn('estudiante_id', $bloque)->delete();
                }
                $nombres = array_values(array_unique(array_map(fn($s) => $s['stg']['escuela'], $r['slots'])));
                DB::table('stg_horarios_sga')->where('periodo_academico', $codigoPeriodo)->whereIn('escuela', $nombres)->delete();
            }

            $ids = [];
            foreach ($r['slots'] as $i => $s) {
                $ids[$i] = DB::table('stg_horarios_sga')->insertGetId($s['stg']);
            }

            $ahora = now();
            foreach (array_chunk($r['horarios'], 1000) as $bloque) {
                DB::table('horarios_clase')->insert(array_map(function ($par) use ($r, $ids, $periodoId, $ahora) {
                    [$estId, $idx] = $par;
                    return [
                        'estudiante_id'        => $estId,
                        'periodo_academico_id' => $periodoId,
                        'stg_horario_id'       => $ids[$idx],
                        'created_at'           => $ahora,
                        'updated_at'           => $ahora,
                    ] + $r['slots'][$idx]['h'];
                }, $bloque));
            }
        });

        return [
            'matriculados_filas'      => count($matriculados),
            'slots'                   => count($r['slots']),
            'horarios'                => count($r['horarios']),
            'estudiantes_con_horario' => count(array_unique(array_column($r['horarios'], 0))),
            'alumnos_sin_estudiante'  => $r['alumnos_sin_estudiante'],
            'tipos'                   => $r['tipos'],
            'slots_con_particion'     => $r['slots_con_particion'],
        ];
    }
}
