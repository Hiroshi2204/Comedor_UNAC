<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CancelacionTicket extends Model
{
    protected $table = 'cancelaciones_ticket';

    protected $fillable = ['ticket_id', 'estudiante_id', 'motivo', 'fecha_cancelacion'];

    protected $casts = [
        'fecha_cancelacion' => 'datetime',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class);
    }
}
