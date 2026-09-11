<?php

namespace Database\Seeders;

use App\Models\VentanaAtencion;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
class VentanaAtencionSeeder extends Seeder
{
    public function run(): void
    {
        $ventanas = [
            ['nombre' => 'Grupo A', 'hora_inicio' => '11:45', 'hora_fin' => '12:00'],
            ['nombre' => 'Grupo B', 'hora_inicio' => '12:00', 'hora_fin' => '12:15'],
            ['nombre' => 'Grupo C', 'hora_inicio' => '12:15', 'hora_fin' => '12:30'],
            ['nombre' => 'Grupo D', 'hora_inicio' => '12:30', 'hora_fin' => '12:45'],
        ];

        foreach ($ventanas as $v) {
            VentanaAtencion::firstOrCreate(
                ['nombre' => $v['nombre']],
                [
                    'hora_inicio' => $v['hora_inicio'],
                    'hora_fin' => $v['hora_fin'],
                    'capacidad_maxima' => 150,
                    'activo' => true,
                ]
            );
        }
    }
}