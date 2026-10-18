<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Período académico activo. Se toma de config('sga.periodo') (p. ej. "2026B") y se busca
 * en `periodos_academicos` comparando cualquier columna de texto, sin depender de sus nombres.
 */
class Periodo
{
    private static ?array $cache = null;

    /** @return array{id:?int,etiqueta:string,fila:?array} */
    public static function activo(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $codigo   = (string) config('sga.periodo', '');
        $etiqueta = preg_replace('/^(\d{4})[-\s]?(.+)$/', '$1-$2', $codigo) ?: '—';
        $norm     = fn ($v) => strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $v));
        $id = null; $fila = null;

        try {
            $filas = DB::table('periodos_academicos')->orderByDesc('id')->get()->map(fn ($f) => (array) $f)->all();

            foreach ($filas as $f) {
                foreach ($f as $valor) {
                    if (is_string($valor) && $codigo !== '' && $norm($valor) === $norm($codigo)) {
                        $id = (int) $f['id']; $fila = $f; break 2;
                    }
                }
            }
            if ($id === null && $filas) {   // sin coincidencia: el más reciente
                $fila = $filas[0]; $id = (int) $fila['id'];
            }
        } catch (\Throwable $e) {
            // tabla inexistente o sin acceso: el panel sigue funcionando sin período
        }

        return self::$cache = ['id' => $id, 'etiqueta' => $etiqueta, 'fila' => $fila];
    }

    public static function id(): ?int
    {
        return self::activo()['id'];
    }

    public static function etiqueta(): string
    {
        return self::activo()['etiqueta'];
    }

    /** Primer valor no vacío entre varias columnas posibles de una fila de periodo. */
    public static function campo(array $fila, array $candidatas): mixed
    {
        foreach ($candidatas as $c) {
            if (isset($fila[$c]) && $fila[$c] !== '') {
                return $fila[$c];
            }
        }
        return null;
    }
}
