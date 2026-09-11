<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogNotificacionWhatsapp extends Model
{
    protected $table = 'logs_notificacion_whatsapp';

    protected $fillable = [
        'ticket_id', 'numero_destino', 'estado_envio',
        'respuesta_proveedor', 'intentos', 'fecha_envio',
    ];

    protected $casts = [
        'fecha_envio' => 'datetime',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
}