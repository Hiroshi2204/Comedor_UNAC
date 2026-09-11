<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HorarioClase extends Model
{
    protected $table = 'horarios_clase';

    protected $fillable = [
        'estudiante_id',
        'periodo_academico_id',
        'stg_horario_id',
        'curso',
        'dia_semana',
        'hora_inicio',
        'hora_fin',
        'aula',
    ];

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class);
    }

    public function periodoAcademico()
    {
        return $this->belongsTo(PeriodoAcademico::class);
    }

    public function stgHorarioSga()
    {
        return $this->belongsTo(StgHorarioSga::class, 'stg_horario_id');
    }

    // Etapa 3: "¿tiene clases hoy?"
    public function scopeDelDia($query, string $diaSemana)
    {
        return $query->where('dia_semana', $diaSemana);
    }
}
