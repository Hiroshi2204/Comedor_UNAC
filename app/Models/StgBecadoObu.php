<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StgBecadoObu extends Model
{
    const UPDATED_AT = null;

    protected $table = 'stg_becados_obu';

    protected $fillable = [
        'codigo_estudiante',
        'dni',
        'nombres_apellidos',
        'tipo_beca',
        'periodo_academico',
        'estado',
        'archivo_origen',
        'cargado_en',
    ];

    protected $casts = [
        'cargado_en' => 'datetime',
    ];

    public function becados()
    {
        return $this->hasMany(Becado::class, 'stg_becado_id');
    }
}
