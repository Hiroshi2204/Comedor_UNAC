<?php

namespace Database\Seeders;

use App\Models\UsuarioSistema;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        UsuarioSistema::firstOrCreate(
            ['email' => 'admin@unac.edu.pe'],
            [
                'nombre' => 'Administrador Comedor',
                'password' => 'CambiarEstaClave123!', // se hashea solo (cast 'hashed')
                'rol' => 'administrador',
                'activo' => true,
            ]
        );
    }
}
