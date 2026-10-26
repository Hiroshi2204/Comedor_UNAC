<?php

namespace App\Support;

/** Reglas de franjas: cuáles se pueden elegir hoy y a dónde se reprograma quien no llegó. PHP puro. */
class FranjasComedor
{
    /** ¿Ya no se puede elegir esta franja? (faltan menos de $cierreMinAntes minutos para que termine) */
    public static function cerrada(array $franja, \DateTimeImmutable $ahora, int $cierreMinAntes): bool
    {
        return $ahora >= self::hora($ahora, $franja['fin'])->modify("-{$cierreMinAntes} minutes");
    }

    /** Las franjas que todavía se pueden elegir hoy. */
    public static function seleccionables(array $franjas, \DateTimeImmutable $ahora, int $cierreMinAntes): array
    {
        return array_values(array_filter($franjas, fn ($f) => !self::cerrada($f, $ahora, $cierreMinAntes)));
    }

    /**
     * Última oportunidad: si el alumno no llegó en su franja, se le manda a la ÚLTIMA franja del servicio.
     * Solo ocurre una vez por ticket y solo si todavía queda tiempo ese día.
     *
     * @param array $ticket   ['fecha' => 'Y-m-d', 'ventana_id' => int, 'ultima_oportunidad' => bool]
     * @param array $franjas  franjas del servicio ordenadas por inicio: [['id' => int, 'inicio' => 'HH:MM', 'fin' => 'HH:MM'], ...]
     * @return array|null     ['ventana_id' => int, 'vence_at' => DateTimeImmutable]; null = ya no hay oportunidad (inasistencia)
     */
    public static function ultimaOportunidad(array $ticket, array $franjas, \DateTimeImmutable $ahora, int $toleranciaMin, string $finServicio): ?array
    {
        if ($ticket['ultima_oportunidad'] || !$franjas) {
            return null;                                   // ya la usó
        }
        if ($ticket['fecha'] !== $ahora->format('Y-m-d')) {
            return null;                                   // ticket de otro día: no se reprograma
        }

        $ultima = end($franjas);
        if ($ultima['id'] === $ticket['ventana_id']) {
            return null;                                   // ya estaba en la última franja
        }

        $vence = self::hora($ahora, $ultima['fin'])->modify("+{$toleranciaMin} minutes");
        $tope  = self::hora($ahora, $finServicio);
        if ($vence > $tope) {
            $vence = $tope;                                // no pasa del cierre del servicio
        }
        if ($vence <= $ahora) {
            return null;                                   // el servicio ya terminó
        }

        return ['ventana_id' => $ultima['id'], 'vence_at' => $vence];
    }

    private static function hora(\DateTimeImmutable $base, string $hhmm): \DateTimeImmutable
    {
        [$h, $m] = array_map('intval', explode(':', substr($hhmm, 0, 5)));

        return $base->setTime($h, $m, 0);
    }
}