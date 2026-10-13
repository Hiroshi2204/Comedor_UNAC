<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EscaneoTicket extends Model
{
    const UPDATED_AT = null;

    protected $table = 'escaneos_ticket';

    protected $fillable = ['ticket_id', 'codigo_qr', 'puesto', 'trabajador_id', 'exitoso', 'resultado'];

    protected $casts = ['exitoso' => 'boolean'];
}
