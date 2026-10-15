<?php

namespace Database\Seeders;

use App\Models\UsuarioSistema;
use Illuminate\Database\Seeder;

class TrabajadorSeeder extends Seeder
{
    public function run(): void
    {
        $trabajadores = [
            ['nombre' => 'Trabajador Comedor 1', 'email' => 'trabajador1@unac.edu.pe', 'username' => 'trabajador1', 'puesto' => 'puerta'],
            ['nombre' => 'Trabajador Comedor 2', 'email' => 'trabajador2@unac.edu.pe', 'username' => 'trabajador2', 'puesto' => 'cocina'],
        ];

        foreach ($trabajadores as $t) {
            $usuario = UsuarioSistema::firstOrCreate(
                ['email' => $t['email']],
                [
                    'nombre' => $t['nombre'],
                    'username' => $t['username'],
                    'password' => 'trabajador123',
                    'rol' => 'trabajador',
                    'puesto' => $t['puesto'],
                    'activo' => true,
                ]
            );

            // si el usuario ya existía (creado antes de existir el puesto), se le asigna
            if ($usuario->puesto !== $t['puesto']) {
                $usuario->update(['puesto' => $t['puesto']]);
            }
        }
    }
}