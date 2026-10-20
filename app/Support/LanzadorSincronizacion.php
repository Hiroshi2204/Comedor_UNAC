<?php

namespace App\Support;

/**
 * Lanza `php artisan sga:sincronizar-horarios` como un proceso aparte (como en la
 * consola), sin depender de un worker de colas. La salida queda en
 * storage/logs/sincronizacion-horarios.log.
 */
class LanzadorSincronizacion
{
    /** @return string|null null si se lanzó; si no, el motivo del fallo. */
    public static function lanzar(int $periodoId): ?string
    {
        $windows = DIRECTORY_SEPARATOR === '\\';
        $php     = self::php();
        $artisan = base_path('artisan');
        $log     = storage_path('logs/sincronizacion-horarios.log');

        if (!is_file($artisan)) {
            return 'No se encontró el archivo artisan.';
        }
        if (!function_exists($windows ? 'popen' : 'exec') || self::deshabilitada($windows ? 'popen' : 'exec')) {
            return 'El servidor tiene deshabilitadas las funciones para lanzar procesos. Ejecuta el comando por consola.';
        }

        @file_put_contents($log, '[' . date('Y-m-d H:i:s') . "] Lanzando sincronización del período {$periodoId}\n", FILE_APPEND);

        if ($windows) {
            $cmd = 'start /B "" "' . $php . '" "' . $artisan . '" sga:sincronizar-horarios ' . $periodoId . ' >> "' . $log . '" 2>&1';
            $h = @popen($cmd, 'r');
            if ($h === false) return 'No se pudo iniciar el proceso.';
            pclose($h);
        } else {
            $cmd = 'nohup ' . escapeshellarg($php) . ' ' . escapeshellarg($artisan) . ' sga:sincronizar-horarios '
                 . $periodoId . ' >> ' . escapeshellarg($log) . ' 2>&1 &';
            @exec($cmd);
        }

        return null;
    }

    /** PHP de línea de comandos: PHP_CLI_BINARY del .env, o el actual si es php, o "php". */
    private static function php(): string
    {
        $cfg = config('comedor.php_cli');
        if ($cfg) return $cfg;

        if (PHP_BINARY && preg_match('/^php[\d.\-]*(\.exe)?$/i', basename(PHP_BINARY))) {
            return PHP_BINARY;
        }
        return 'php';
    }

    private static function deshabilitada(string $fn): bool
    {
        $lista = array_map('trim', explode(',', (string) ini_get('disable_functions')));
        return in_array($fn, $lista, true);
    }
}
