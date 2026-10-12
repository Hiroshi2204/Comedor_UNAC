<?php

namespace App\Services;

/**
 * Decide qué servicios (desayuno, almuerzo, cena) puede pedir un alumno en un día
 * y en qué franjas libres, a partir de sus clases de ese día. Sin dependencias de Laravel.
 */
class PlanificadorComedor
{
    public function __construct(private array $cfg) {}

    /**
     * @param  array $clases  [['07:15','09:00'], ['14:00','16:00']] (clases de UN día)
     * @return array  ['almuerzo' => ['12:15','12:30',...], ...]  solo servicios elegibles con franja libre
     */
    public function planificar(array $clases): array
    {
        if (!$clases) {
            return []; // sin clases ese día: no se genera ticket
        }

        $iv = array_map(fn ($c) => [$this->min($c[0]), $this->min($c[1])], $clases);
        $primera = min(array_column($iv, 0));
        $ultima  = max(array_column($iv, 1));

        $elegibles = [
            'desayuno' => $primera >= $this->min($this->cfg['desayuno_inicio_desde'])
                       && $primera <  $this->min($this->cfg['desayuno_inicio_hasta']),
            'almuerzo' => $primera < $this->min($this->cfg['servicios']['cena']['inicio']), // no solo-noche
            'cena'     => $ultima  > $this->min($this->cfg['cena_si_termina_despues']),
        ];

        $res = [];
        foreach ($elegibles as $servicio => $ok) {
            if (!$ok) continue;
            $franjas = $this->franjasLibres($this->cfg['servicios'][$servicio], $iv);
            if ($franjas) $res[$servicio] = $franjas;
        }
        return $res;
    }

    /** ¿Puede el alumno comer ahora? Necesita una franja completa libre de clases, con el margen de caminata. */
    public function libreAhora(array $clases, string $hhmm): bool
    {
        $s = $this->min($hhmm);
        foreach ($clases as [$ini, $fin]) {
            if ($s < $this->min($fin) + $this->cfg['margen_min']
                && $s + $this->cfg['franja_min'] > $this->min($ini) - $this->cfg['margen_min']) {
                return false;
            }
        }
        return true;
    }

    private function franjasLibres(array $srv, array $iv): array
    {
        $paso   = $this->cfg['franja_min'];
        $margen = $this->cfg['margen_min'];
        $libres = [];

        for ($s = $this->min($srv['inicio']); $s + $paso <= $this->min($srv['fin']); $s += $paso) {
            $choca = false;
            foreach ($iv as [$a, $b]) {
                if ($s < $b + $margen && $s + $paso > $a - $margen) { $choca = true; break; }
            }
            if (!$choca) $libres[] = sprintf('%02d:%02d', intdiv($s, 60), $s % 60);
        }
        return $libres;
    }

    private function min(string $hhmm): int
    {
        [$h, $m] = array_map('intval', explode(':', $hhmm));
        return $h * 60 + $m;
    }
}