<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Becado extends Model
{
    protected $fillable = [
        'estudiante_id',
        'periodo_academico_id',
        'stg_becado_id',
        'codigo_beca',
        'tipo_beca',
        'estado',
        'fecha_registro',
    ];

    protected $casts = [
        'fecha_registro' => 'date',
    ];

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class);
    }

    public function periodoAcademico()
    {
        return $this->belongsTo(PeriodoAcademico::class);
    }

    public function stgBecadoObu()
    {
        return $this->belongsTo(StgBecadoObu::class, 'stg_becado_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }
}
