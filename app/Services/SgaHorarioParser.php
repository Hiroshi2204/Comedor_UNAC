<?php

namespace App\Services;

class SgaHorarioParser
{
    private const DIAS = [
        'lun' => 'lunes', 'mar' => 'martes', 'mie' => 'miercoles',
        'jue' => 'jueves', 'vie' => 'viernes', 'sab' => 'sabado',
    ];

    /** Devuelve null si la fila no es utilizable (domingo, sin hora). */
    public static function fila(array $f): ?array
    {
        $dia = strtr(mb_strtolower(trim($f['dia'] ?? '')), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u']);
        $dia = self::DIAS[substr($dia, 0, 3)] ?? null;

        if (!$dia || !preg_match('/(\d{1,2}):(\d{2})\s*a\s*(\d{1,2}):(\d{2})/', $f['hora'] ?? '', $m)) {
            return null;
        }

        return [
            'dia_semana'  => $dia,
            'hora_inicio' => sprintf('%02d:%02d', $m[1], $m[2]),
            'hora_fin'    => sprintf('%02d:%02d', $m[3], $m[4]),
        ];
    }
}