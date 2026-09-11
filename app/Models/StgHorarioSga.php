<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StgHorarioSga extends Model
{
    const UPDATED_AT = null; // tabla de solo carga, sin updated_at

    protected $table = 'stg_horarios_sga';

    protected $fillable = [
        'periodo_academico',
        'escuela',
        'seccion',
        'cod_curso',
        'curso',
        'ciclo',
        'docente',
        'dia',
        'hora_inicio',
        'hora_fin',
        'aula',
        'sede',
        'n_matriculados',
        'archivo_origen',
        'cargado_en',
    ];

    protected $casts = [
        'cargado_en' => 'datetime',
    ];

    public function horariosClase()
    {
        return $this->hasMany(HorarioClase::class, 'stg_horario_id');
    }
}
