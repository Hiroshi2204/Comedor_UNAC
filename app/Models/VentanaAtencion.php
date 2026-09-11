<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VentanaAtencion extends Model
{
    protected $table = 'ventanas_atencion';
    protected $fillable = ['nombre', 'hora_inicio', 'hora_fin', 'capacidad_maxima', 'activo'];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}
