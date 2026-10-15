<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class SgaSsoToken
{
    public static function firmar(string $username, int $ts): string
    {
        return hash_hmac('sha256', $username . '|' . $ts, (string) config('sga.sso.secret'));
    }

    /** Verifica firma, vigencia y que no se haya usado antes. */
    public function verificar(string $username, string $ts, string $sig): bool
    {
        $secret = config('sga.sso.secret');
        $ttl = (int) config('sga.sso.ttl');

        if (!$secret || $username === '' || !ctype_digit($ts) || $sig === '') {
            return false;
        }
        if (abs(time() - (int) $ts) > $ttl) {
            return false;
        }

        $esperada = self::firmar($username, (int) $ts);
        if (!hash_equals($esperada, strtolower($sig))) {
            return false;
        }

        // un solo uso: add() es atómico y falla si la clave ya existe
        return Cache::add('sga-sso:' . $esperada, 1, $ttl * 2);
    }
}