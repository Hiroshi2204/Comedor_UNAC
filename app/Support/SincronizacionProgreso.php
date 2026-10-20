<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Avance de la sincronización de horarios, guardado en caché para que lo
 * escriba el comando (cola, consola o cron) y lo lea la pantalla del admin.
 */
class SincronizacionProgreso
{
    private const TTL = 21600;     // 6 horas
    private const MAX_LINEAS = 200;
    public const SIN_NOTICIAS_SEG = 900;  // 15 min sin avance = posible proceso detenido
    public const ARRANQUE_SEG = 60;       // si en 60 s no pasa de «iniciando», el proceso no arrancó

    private static function clave(int $periodoId): string
    {
        return "sga.sync.$periodoId";
    }

    public static function obtener(int $periodoId): ?array
    {
        return Cache::get(self::clave($periodoId));
    }

    /** ¿Estaba en marcha pero ya no da señales de vida? */
    public static function colgada(array $e): bool
    {
        if (!in_array($e['estado'], ['en_cola', 'corriendo'], true)) return false;
        $limite = $e['estado'] === 'en_cola' ? self::ARRANQUE_SEG : self::SIN_NOTICIAS_SEG;
        return (time() - $e['actualizado']) >= $limite;
    }

    /** ¿Hay una sincronización en marcha (y viva) para este período? */
    public static function activa(int $periodoId): bool
    {
        $e = self::obtener($periodoId);
        return $e && in_array($e['estado'], ['en_cola', 'corriendo'], true) && !self::colgada($e);
    }

    public static function encolar(int $periodoId, ?int $usuarioId = null): void
    {
        self::guardar($periodoId, [
            'estado' => 'en_cola', 'origen' => 'panel', 'usuario_id' => $usuarioId,
            'total' => 0, 'hechas' => 0, 'fase' => 'Iniciando el proceso…',
            'lineas' => [], 'resultado' => null, 'error' => null,
            'inicio' => time(), 'fin' => null,
        ]);
    }

    public static function iniciar(int $periodoId, int $total): void
    {
        $previo = self::obtener($periodoId);
        $origen = ($previo['estado'] ?? null) === 'en_cola' ? 'panel' : 'consola';
        self::guardar($periodoId, [
            'estado' => 'corriendo', 'origen' => $origen, 'usuario_id' => $previo['usuario_id'] ?? null,
            'total' => $total, 'hechas' => 0, 'fase' => 'Descargando programación del SGA…',
            'lineas' => [], 'resultado' => null, 'error' => null,
            'inicio' => $previo['inicio'] ?? time(), 'fin' => null,
        ]);
    }

    /** Una escuela terminada. */
    public static function escuela(int $periodoId, string $texto): void
    {
        $e = self::obtener($periodoId) ?? [];
        $e['hechas'] = ($e['hechas'] ?? 0) + 1;
        $e['lineas'][] = ['h' => date('H:i:s'), 't' => $texto];
        $e['lineas'] = array_slice($e['lineas'], -self::MAX_LINEAS);
        $e['fase'] = ($e['hechas'] >= ($e['total'] ?? 0))
            ? 'Armando horarios de cada estudiante (puede tardar unos minutos)…'
            : 'Descargando escuela ' . ($e['hechas'] + 1) . ' de ' . $e['total'] . '…';
        self::guardar($periodoId, $e);
    }

    /** Cambio de etapa (sin avanzar el conteo de escuelas). */
    public static function fase(int $periodoId, string $texto): void
    {
        $e = self::obtener($periodoId) ?? [];
        $e['fase'] = $texto;
        $e['lineas'][] = ['h' => date('H:i:s'), 't' => $texto];
        $e['lineas'] = array_slice($e['lineas'], -self::MAX_LINEAS);
        self::guardar($periodoId, $e);
    }

    public static function terminar(int $periodoId, array $resultado, array $extra = []): void
    {
        $e = self::obtener($periodoId) ?? [];
        $e['estado'] = 'terminado';
        $e['hechas'] = $e['total'] ?? 0;
        $e['fase'] = 'Sincronización completada';
        $e['resultado'] = $resultado;
        $e['extra'] = $extra;
        $e['fin'] = time();
        $e['lineas'][] = ['h' => date('H:i:s'), 't' => 'Listo.'];
        self::guardar($periodoId, $e);
    }

    public static function fallar(int $periodoId, string $mensaje): void
    {
        $e = self::obtener($periodoId) ?? [];
        $e['estado'] = 'error';
        $e['fase'] = 'La sincronización falló';
        $e['error'] = $mensaje;
        $e['fin'] = time();
        $e['lineas'][] = ['h' => date('H:i:s'), 't' => 'ERROR: ' . $mensaje];
        self::guardar($periodoId, $e);
    }

    private static function guardar(int $periodoId, array $e): void
    {
        $e['actualizado'] = time();
        Cache::put(self::clave($periodoId), $e, self::TTL);
    }
}
