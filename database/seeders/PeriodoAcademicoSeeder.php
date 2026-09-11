<?php

namespace Database\Seeders;

use App\Models\PeriodoAcademico;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PeriodoAcademicoSeeder extends Seeder
{
    public function run(): void
    {
        PeriodoAcademico::firstOrCreate(
            ['nombre' => '2026-A'],
            [
                'fecha_inicio' => '2026-03-16',
                'fecha_fin' => '2026-07-25',
                'activo' => true,
            ]
        );
    }
}