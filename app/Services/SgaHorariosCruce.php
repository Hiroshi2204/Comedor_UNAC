<?php

namespace App\Services;

/** Cruza programación horaria y alumnos por sección del SGA. PHP puro, sin Laravel. */
class SgaHorariosCruce
{
    /**
     * @param array $estudiantes [codigo_universitario => estudiante_id]
     */
    public static function cruzar(array $programacion, array $matriculados, array $estudiantes): array
    {
        $slots = [];
        $porSeccion = [];
        $porParticion = [];
        $vistos = [];
        $conParticion = 0;

        foreach ($programacion as $f) {
            $h = SgaHorarioParser::fila($f);
            $seccionId = $f['eduSeccionId'] ?? null;
            if (!$h || !$seccionId) continue;

            $clave = implode('|', [$seccionId, $f['eduSeccionParticionId'] ?? '', $h['dia_semana'], $h['hora_inicio'], $h['hora_fin'], $f['tipo'] ?? '']);
            if (isset($vistos[$clave])) continue;   // el SGA repite filas (p. ej. por docente)
            $vistos[$clave] = true;

            if (!empty($f['eduSeccionParticionId'])) $conParticion++;

            $idx = count($slots);
            $slots[$idx] = [
                'stg' => [
                    'periodo_academico' => $f['periodo'] ?? '',
                    'escuela'           => $f['escuela'] ?? '',
                    'seccion'           => $f['seccion'] ?? null,
                    'cod_curso'         => $f['codCur'] ?? null,
                    'curso'             => $f['curso'] ?? null,
                    'ciclo'             => $f['ciclo'] ?? null,
                    'docente'           => trim(($f['paterno'] ?? '') . ' ' . ($f['materno'] ?? '') . ' ' . ($f['nombres'] ?? '')) ?: null,
                    'dia'               => $f['dia'] ?? null,
                    'hora_inicio'       => $h['hora_inicio'],
                    'hora_fin'          => $h['hora_fin'],
                    'aula'              => $f['aula'] ?? null,
                    'sede'              => $f['sede'] ?? null,
                    'n_matriculados'    => $f['matriculados'] ?? null,
                    'archivo_origen'    => 'API SGA',
                    'edu_seccion_id'    => $seccionId,
                    'edu_particion_id'  => $f['eduSeccionParticionId'] ?? null,
                    'tipo'              => $f['tipo'] ?? null,
                    'fecha_inicio'      => self::fecha($f['fechaInicio'] ?? null),
                    'fecha_fin'         => self::fecha($f['fechaFin'] ?? null),
                ],
                'h' => [
                    'curso'        => $f['curso'] ?? null,
                    'dia_semana'   => $h['dia_semana'],
                    'hora_inicio'  => $h['hora_inicio'],
                    'hora_fin'     => $h['hora_fin'],
                    'aula'         => $f['aula'] ?? null,
                    'fecha_inicio' => self::fecha($f['fechaInicio'] ?? null),
                    'fecha_fin'    => self::fecha($f['fechaFin'] ?? null),
                ],
            ];
            $porSeccion[$seccionId][] = $idx;
            if (!empty($f['eduSeccionParticionId'])) {
                $porParticion[$f['eduSeccionParticionId']][] = $idx;
            }
        }

        //     $horarios = [];
        //     $visto = [];
        //     $sinEstudiante = [];
        //     $tipos = [];

        //     foreach ($matriculados as $m) {
        //         $tipo = $m['tipo'] ?? '?';
        //         $tipos[$tipo] = ($tipos[$tipo] ?? 0) + 1;

        //         $estId = $estudiantes[$m['codAlu'] ?? ''] ?? null;
        //         if (!$estId) {
        //             $sinEstudiante[$m['codAlu'] ?? '?'] = true;
        //             continue;
        //         }

        //         foreach ($porSeccion[$m['eduSeccionId'] ?? ''] ?? [] as $idx) {
        //             $h = $slots[$idx]['h'];
        //             $k = $estId . '|' . $h['dia_semana'] . '|' . $h['hora_inicio'] . '|' . $h['hora_fin'] . '|' . $slots[$idx]['stg']['cod_curso'];
        //             if (isset($visto[$k])) continue;
        //             $visto[$k] = true;
        //             $horarios[] = [$estId, $idx];
        //         }
        //     }

        //     return [
        //         'slots' => $slots,
        //         'horarios' => $horarios,
        //         'alumnos_sin_estudiante' => count($sinEstudiante),
        //         'tipos' => $tipos,
        //         'slots_con_particion' => $conParticion,
        //     ];
        // }
        // 1) qué secciones y qué grupos (particiones) tiene cada alumno
        $alumnos = [];
        $sinEstudiante = [];
        $tipos = [];
        $g = ['por_particion' => 0, 'por_seccion' => 0, 'sin_cruce' => 0]; // diagnóstico de las filas tipo G

        foreach ($matriculados as $m) {
            $tipo = $m['tipo'] ?? '?';
            $tipos[$tipo] = ($tipos[$tipo] ?? 0) + 1;

            $estId = $estudiantes[$m['codAlu'] ?? ''] ?? null;
            if (!$estId) {
                $sinEstudiante[$m['codAlu'] ?? '?'] = true;
                continue;
            }

            $id  = $m['eduSeccionId'] ?? null;
            $pid = $m['eduSeccionParticionId'] ?? null;

            $esSeccion   = $id && isset($porSeccion[$id]);
            $particionId = ($pid && isset($porParticion[$pid])) ? $pid : (($id && isset($porParticion[$id])) ? $id : null);

            if ($esSeccion)   $alumnos[$estId]['secciones'][$id] = true;
            if ($particionId) $alumnos[$estId]['particiones'][$particionId] = true;

            if ($tipo === 'G') {
                if ($particionId) $g['por_particion']++;
                elseif ($esSeccion) $g['por_seccion']++;
                else $g['sin_cruce']++;
            }
        }

        // 2) horarios: teoría de la sección + solo los grupos propios (si no se pudo saber, se asumen todos)
        $horarios = [];
        $visto = [];
        $ambiguos = 0;

        $agregar = function (int $estId, int $idx) use (&$horarios, &$visto, $slots) {
            $h = $slots[$idx]['h'];
            $k = $estId . '|' . $h['dia_semana'] . '|' . $h['hora_inicio'] . '|' . $h['hora_fin'] . '|' . $slots[$idx]['stg']['cod_curso'];
            if (isset($visto[$k])) return;
            $visto[$k] = true;
            $horarios[] = [$estId, $idx];
        };

        foreach ($alumnos as $estId => $a) {
            $propias = $a['particiones'] ?? [];

            foreach (array_keys($a['secciones'] ?? []) as $secId) {
                $slotsSec = $porSeccion[$secId];
                $conGrupos = array_filter($slotsSec, fn($i) => $slots[$i]['stg']['edu_particion_id']);
                $resuelta  = $conGrupos && array_filter($conGrupos, fn($i) => isset($propias[$slots[$i]['stg']['edu_particion_id']]));

                if ($conGrupos && !$resuelta) $ambiguos++;

                foreach ($slotsSec as $idx) {
                    $p = $slots[$idx]['stg']['edu_particion_id'];
                    if ($p && $resuelta && !isset($propias[$p])) continue; // grupo de otros alumnos
                    $agregar($estId, $idx);
                }
            }

            foreach (array_keys($propias) as $pid) {
                foreach ($porParticion[$pid] as $idx) $agregar($estId, $idx);
            }
        }

        return [
            'slots' => $slots,
            'horarios' => $horarios,
            'alumnos_sin_estudiante' => count($sinEstudiante),
            'tipos' => $tipos,
            'slots_con_particion' => $conParticion,
            'g' => $g,
            'ambiguos' => $ambiguos,
        ];
    }

    private static function fecha(?string $dmY): ?string
    {
        $d = $dmY ? \DateTime::createFromFormat('d-m-Y', $dmY) : false;
        return $d ? $d->format('Y-m-d') : null;
    }
}
