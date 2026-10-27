<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Reloj de demostración: hace que todo el sistema (web y comandos) use una fecha y hora simuladas.
 * El reloj sigue avanzando desde la hora elegida. Solo funciona si comedor.demo.habilitado es true
 * (DEMO_RELOJ=true en el .env) y nunca en producción.
 */
class RelojDemo
{
    private static function archivo(): string
    {
        return storage_path('app/demo_reloj.json');
    }

    public static function habilitado(): bool
    {
        return (bool) config('comedor.demo.habilitado');
    }

    /** Segundos de desfase entre la hora real y la simulada, o null si no hay simulación. */
    private static function desfase(): ?int
    {
        $f = self::archivo();
        if (!is_file($f)) return null;
        $d = json_decode((string) @file_get_contents($f), true);
        return is_array($d) && isset($d['desfase']) ? (int) $d['desfase'] : null;
    }

    /** Se llama al arrancar la aplicación (AppServiceProvider::boot). */
    public static function aplicar(): void
    {
        if (!self::habilitado()) return;

        Carbon::setTestNow(); // hora real
        $desfase = self::desfase();
        if ($desfase !== null) {
            Carbon::setTestNow(Carbon::now()->addSeconds($desfase));

            // La sesión y sus cookies se miden con el reloj del navegador (real). Con la hora simulada:
            // - cookie con vencimiento: si la hora simulada es anterior a la real, nace ya vencida (error 419);
            // - archivo de sesión: si la simulada va muy adelantada, la sesión parece caducada.
            config(['session.expire_on_close' => true, 'session.lifetime' => 43200]);
        }
    }

    public static function activo(): bool
    {
        return self::habilitado() && self::desfase() !== null;
    }

    /** Hora real del servidor, ignorando la simulación. */
    public static function real(): Carbon
    {
        $simulada = Carbon::getTestNow();
        Carbon::setTestNow();
        $real = Carbon::now();
        if ($simulada) Carbon::setTestNow($simulada);
        return $real;
    }

    public static function fijar(Carbon $objetivo): void
    {
        $desfase = $objetivo->getTimestamp() - self::real()->getTimestamp();
        file_put_contents(self::archivo(), json_encode(['desfase' => $desfase, 'fijado' => date('c')]));
        self::aplicar();
        self::limpiarCaches();
    }

    public static function avanzar(int $minutos): void
    {
        self::fijar(Carbon::now()->addMinutes($minutos));
    }

    public static function desactivar(): void
    {
        @unlink(self::archivo());
        self::aplicar();
        self::limpiarCaches();
    }

    /** La caché de becados elegibles depende de la fecha: se descarta al cambiar la hora. */
    private static function limpiarCaches(): void
    {
        foreach ([self::real(), Carbon::now()] as $base) {
            foreach (range(-8, 8) as $i) {
                Cache::forget('comedor.elegibles.' . $base->copy()->addDays($i)->toDateString());
            }
        }
    }
}
