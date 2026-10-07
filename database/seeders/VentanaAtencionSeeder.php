<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VentanaAtencionSeeder extends Seeder
{
    public function run(): void
    {
        $paso = config('comedor.franja_min');
        $filas = [];

        foreach (config('comedor.servicios') as $servicio => $s) {
            $ini   = $this->min($s['inicio']);
            $n     = intdiv($this->min($s['fin']) - $ini, $paso);
            $base  = intdiv($s['stock'], $n);
            $resto = $s['stock'] % $n;

            for ($i = 0; $i < $n; $i++) {
                $desde = $ini + $i * $paso;
                $filas[] = [
                    'nombre'           => ucfirst($servicio) . ' ' . $this->hhmm($desde) . '-' . $this->hhmm($desde + $paso),
                    'servicio'         => $servicio,
                    'hora_inicio'      => $this->hhmm($desde) . ':00',
                    'hora_fin'         => $this->hhmm($desde + $paso) . ':00',
                    'capacidad_maxima' => $base + ($i < $resto ? 1 : 0),
                    'activo'           => true,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ];
            }
        }

        DB::table('ventanas_atencion')->insert($filas);
    }

    private function min(string $t): int
    {
        [$h, $m] = array_map('intval', explode(':', $t));
        return $h * 60 + $m;
    }

    private function hhmm(int $m): string
    {
        return sprintf('%02d:%02d', intdiv($m, 60), $m % 60);
    }
}