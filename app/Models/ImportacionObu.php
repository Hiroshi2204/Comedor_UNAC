<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportacionObu extends Model
{
    protected $table = 'importaciones_obu';

    protected $fillable = ['periodo_id', 'lote_id', 'archivo', 'registros', 'resumen', 'estado', 'procesados', 'usuario_id', 'procesado_at'];

    protected $casts = ['resumen' => 'array', 'procesado_at' => 'datetime'];

    public function usuario()
    {
        return $this->belongsTo(UsuarioSistema::class, 'usuario_id');
    }
}
