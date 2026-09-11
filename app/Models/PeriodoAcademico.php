<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PeriodoAcademico extends Model
{
    protected $table = 'periodos_academicos';
    protected $fillable = ['nombre', 'fecha_inicio', 'fecha_fin', 'activo'];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'activo' => 'boolean',
    ];

    public function estudiantes()
    {
        return $this->hasMany(Estudiante::class);
    }

    public function becados()
    {
        return $this->hasMany(Becado::class);
    }

    public function horariosClase()
    {
        return $this->hasMany(HorarioClase::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}
