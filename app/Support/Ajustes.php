<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Parámetros operativos editables desde el panel. Se guardan en la tabla `ajustes`
 * (clave = ruta de config, p. ej. "comedor.ticket.vigencia_min") y pisan config/comedor.php al arrancar.
 * Sin fila guardada, rige el valor de config/comedor.php.
 */
class Ajustes
{
    /** clave => [etiqueta, ayuda, tipo, mínimo, máximo, paso] */
    public const CAMPOS = [
        'comedor.inasistencias.limite'          => ['Inasistencias para sanción', 'Cantidad de tickets vencidos sin asistir que activan la sanción.', 'int', 1, 20, 1],
        'comedor.ticket.vigencia_min'           => ['Vigencia del ticket (min)', 'Minutos que vale un ticket generado al momento.', 'int', 5, 120, 1],
        'comedor.ticket.anticipacion_min'       => ['Anticipación (min)', 'Se puede generar desde N min antes de que abra el servicio.', 'int', 0, 60, 1],
        'comedor.ticket.cierre_min_antes'       => ['Cierre de generación (min)', 'Se deja de generar N min antes de que cierre el servicio.', 'int', 0, 60, 1],
        'comedor.reserva.tolerancia_min'        => ['Tolerancia de reserva (min)', 'Minutos extra para recoger tras terminar la franja reservada.', 'int', 0, 60, 1],
        'comedor.escaneo.entrega_min'           => ['Plazo de entrega (min)', 'Minutos para recoger la comida después de pasar la puerta.', 'int', 1, 60, 1],
        'comedor.escaneo.gracia_cierre_min'     => ['Gracia al cierre (min)', 'Tolerancia después del cierre del servicio.', 'int', 0, 60, 1],
        'comedor.no_becados.factor_reserva'     => ['Factor de reserva (no becados)', '1 = se reserva un cupo por cada becado elegible que aún no genera.', 'float', 0, 3, 0.1],
        'comedor.no_becados.libera_min_antes_cierre' => ['Liberación de reserva (min)', 'En los últimos N min la reserva para becados se libera a no becados.', 'int', 0, 120, 1],
    ];

    /** Aplica los valores guardados sobre la configuración. Llamar desde AppServiceProvider::boot(). */
    public static function aplicar(): void
    {
        try {
            foreach (self::guardados() as $clave => $valor) {
                if (isset(self::CAMPOS[$clave])) {
                    config([$clave => self::CAMPOS[$clave][2] === 'float' ? (float) $valor : (int) $valor]);
                }
            }
        } catch (\Throwable $e) {
            // base de datos aún sin migrar (composer install, migrate, etc.)
        }
    }

    /** @return array<string,string> */
    public static function guardados(): array
    {
        return Cache::remember('ajustes.comedor', 3600, function () {
            return Schema::hasTable('ajustes') ? DB::table('ajustes')->pluck('valor', 'clave')->all() : [];
        });
    }

    public static function guardar(array $valores): void
    {
        foreach ($valores as $clave => $valor) {
            if (!isset(self::CAMPOS[$clave])) {
                continue;
            }
            DB::table('ajustes')->updateOrInsert(['clave' => $clave], ['valor' => (string) $valor, 'updated_at' => now()]);
        }
        Cache::forget('ajustes.comedor');
    }

    /** Quita el valor guardado y vuelve al de config/comedor.php. */
    public static function restablecer(): void
    {
        DB::table('ajustes')->whereIn('clave', array_keys(self::CAMPOS))->delete();
        Cache::forget('ajustes.comedor');
    }
}
