<?php

namespace App\Services;

class CupoComedor
{
    public static function paraNoBecados(
        int $stock,
        int $emitidos,
        int $emitidosBecados,
        int $elegiblesBecados,
        float $factor,
        int $minParaCierre,
        int $liberaMinAntes
    ): int {
        $reserva = $minParaCierre <= $liberaMinAntes
            ? 0
            : max(0, (int) ceil($elegiblesBecados * $factor) - $emitidosBecados);

        return max(0, $stock - $emitidos - $reserva);
    }
}
