<?php

namespace App\Services;

/**
 * Reglas del doble escaneo (puerta -> entrega). PHP puro, sin Laravel, para poder probarlas.
 */
class ReglasEscaneo
{
    public function __construct(private array $cfg) {}

    /**
     * @param array $t  estado, fecha (Y-m-d), servicio, franja_inicio (HH:MM|null), vence_at (Y-m-d H:i:s|null),
     *                  validado_at (Y-m-d H:i:s|null), recogido_at (Y-m-d H:i:s|null)
     * @return array|null  [codigo, mensaje] si se rechaza; null si procede
     */
    public function evaluar(array $t, string $puesto, \DateTimeImmutable $ahora): ?array
    {
        $estado = $t['estado'];

        if ($estado === 'cancelado') return ['cancelado', 'El estudiante canceló este ticket.'];
        if (in_array($estado, ['vencido', 'no_recogido'], true)) return ['vencido', 'Este ticket venció.'];
        if ($estado === 'recogido') {
            return ['ya_recogido', 'Este ticket ya se usó: la comida se entregó a las ' . $this->hm($t['recogido_at']) . '.'];
        }

        if ($t['fecha'] !== $ahora->format('Y-m-d')) {
            return ['fuera_de_fecha', 'Este ticket es del ' . \DateTimeImmutable::createFromFormat('Y-m-d', $t['fecha'])->format('d/m') . '.'];
        }

        if ($puesto === 'puerta' && $estado === 'validado') {
            return ['ya_validado', 'Este ticket ya pasó por la puerta a las ' . $this->hm($t['validado_at']) . '.'];
        }
        if ($puesto === 'entrega' && $estado === 'generado' && ($this->cfg['escaneo']['exigir_puerta'] ?? true)) {
            return ['falta_puerta', 'Falta validar al estudiante en la puerta.'];
        }

        if ($estado === 'generado' && !empty($t['franja_inicio'])) {
            $desde = $this->hora($ahora, $t['franja_inicio'])->modify('-' . $this->cfg['ticket']['anticipacion_min'] . ' minutes');
            if ($ahora < $desde) {
                return ['muy_temprano', 'Su franja empieza a las ' . $t['franja_inicio'] . '. Debe volver desde las ' . $desde->format('H:i') . '.'];
            }
        }

        if (!empty($t['vence_at']) && $ahora > new \DateTimeImmutable($t['vence_at'])) {
            return ['vencido', 'El ticket venció a las ' . $this->hm($t['vence_at']) . '.'];
        }

        return null;
    }

    /** Al pasar por la puerta se le da tiempo extra para recoger la comida (sin sobrepasar el cierre + gracia). */
    public function venceTrasPuerta(?string $venceActual, string $servicio, \DateTimeImmutable $ahora): \DateTimeImmutable
    {
        $e = $this->cfg['escaneo'];
        $cierre = $this->hora($ahora, $this->cfg['servicios'][$servicio]['fin'])->modify('+' . $e['gracia_cierre_min'] . ' minutes');
        $extendido = $ahora->modify('+' . $e['entrega_min'] . ' minutes');
        if ($extendido > $cierre) $extendido = $cierre;

        $actual = $venceActual ? new \DateTimeImmutable($venceActual) : null;
        return ($actual && $actual > $extendido) ? $actual : $extendido;
    }

    private function hora(\DateTimeImmutable $base, string $hhmm): \DateTimeImmutable
    {
        [$h, $m] = array_map('intval', explode(':', substr($hhmm, 0, 5)));
        return $base->setTime($h, $m, 0);
    }

    private function hm(?string $dt): string
    {
        return $dt ? (new \DateTimeImmutable($dt))->format('H:i') : '?';
    }
}