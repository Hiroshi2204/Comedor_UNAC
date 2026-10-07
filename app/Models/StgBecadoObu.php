<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StgBecadoObu extends Model
{
    use HasFactory;
    protected $table = 'stg_becados_obu';

    protected $fillable = [
        'periodo_id',
        'lote_id',
        'hoja',
        'escuela',
        'fila_excel',
        'nombre_completo',
        'dni_raw',
        'codigo_raw',
        'dni',
        'codigo',
        'estado',
        'observacion',
    ];

    public function becados()
    {
        return $this->hasMany(Becado::class, 'stg_becado_id');
    }
    public function periodo()
    {
        return $this->belongsTo(PeriodoAcademico::class, 'periodo_id');
    }
}
