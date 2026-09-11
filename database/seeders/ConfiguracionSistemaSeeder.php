<?php

namespace Database\Seeders;

use App\Models\ConfiguracionSistema;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ConfiguracionSistemaSeeder extends Seeder
{
    public function run(): void
    {
        $configuraciones = [
            ['clave' => 'hora_envio_tickets', 'valor' => '10:00', 'descripcion' => 'Hora diaria de disparo del envío de tickets a becados.'],
            ['clave' => 'limite_inasistencias_sancion', 'valor' => '3', 'descripcion' => 'Número de inasistencias que activan la suspensión del beneficio.'],
            ['clave' => 'minutos_tolerancia_recojo', 'valor' => '15', 'descripcion' => 'Minutos de tolerancia dentro de la ventana de atención.'],
        ];

        foreach ($configuraciones as $c) {
            ConfiguracionSistema::firstOrCreate(
                ['clave' => $c['clave']],
                ['valor' => $c['valor'], 'descripcion' => $c['descripcion']]
            );
        }
    }
}