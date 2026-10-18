<?php

namespace App\Support;

use Illuminate\Support\Str;

class Fmt
{
    /** Código visible del ticket: TK-2026-00128 (el QR real sigue siendo secreto). */
    public static function ticket($t): string
    {
        return 'TK-' . $t->fecha->format('Y') . '-' . str_pad((string) $t->id, 5, '0', STR_PAD_LEFT);
    }

    public static function nombre(?string $n): string
    {
        return Str::title(mb_strtolower((string) $n));
    }

    public static function hora($v): string
    {
        return $v ? substr((string) (is_object($v) ? $v->format('H:i') : $v), 0, 5) : '—';
    }

    public static function franja($ventana): string
    {
        return $ventana ? substr($ventana->hora_inicio, 0, 5) . '–' . substr($ventana->hora_fin, 0, 5) : '—';
    }
}
