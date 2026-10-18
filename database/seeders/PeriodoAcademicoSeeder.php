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
            ['nombre' => '2026-B'],
            [
                'fecha_inicio' => '2026-08-31',
                'fecha_fin' => '2026-12-18',
                'activo' => true,
            ]
        );
    }
}