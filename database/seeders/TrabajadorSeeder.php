<?php

namespace Database\Seeders;

use App\Models\UsuarioSistema;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TrabajadorSeeder extends Seeder
{
    public function run(): void
    {
        $trabajadores = [
            ['nombre' => 'Trabajador Comedor 1', 'email' => 'trabajador1@unac.edu.pe'],
            ['nombre' => 'Trabajador Comedor 2', 'email' => 'trabajador2@unac.edu.pe'],
        ];

        foreach ($trabajadores as $t) {
            UsuarioSistema::firstOrCreate(
                ['email' => $t['email']],
                [
                    'nombre' => $t['nombre'],
                    'password' => 'Trabajador123!',
                    'rol' => 'trabajador',
                    'activo' => true,
                ]
            );
        }
    }
}