<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $fillable = [
        'estudiante_id',
        'periodo_academico_id',
        'ventana_atencion_id',
        'ticket_origen_id',
        'trabajador_id',
        'tipo',
        'fecha',
        'estado',
        'codigo_qr',
        'canal_envio',
        'fecha_hora_envio',
        'fecha_hora_recojo',
    ];

    protected $casts = [
        'fecha' => 'date',
        'fecha_hora_envio' => 'datetime',
        'fecha_hora_recojo' => 'datetime',
    ];

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class);
    }

    public function periodoAcademico()
    {
        return $this->belongsTo(PeriodoAcademico::class);
    }

    public function ventanaAtencion()
    {
        return $this->belongsTo(VentanaAtencion::class);
    }

    public function trabajador()
    {
        return $this->belongsTo(UsuarioSistema::class, 'trabajador_id');
    }

    // Etapa 5: ticket de no becado que ocupó el cupo de este ticket cancelado
    public function ticketOrigen()
    {
        return $this->belongsTo(Ticket::class, 'ticket_origen_id');
    }

    public function ticketReasignado()
    {
        return $this->hasOne(Ticket::class, 'ticket_origen_id');
    }

    public function cancelacion()
    {
        return $this->hasOne(CancelacionTicket::class);
    }

    public function inasistencia()
    {
        return $this->hasOne(Inasistencia::class);
    }

    public function logsWhatsapp()
    {
        return $this->hasMany(LogNotificacionWhatsapp::class);
    }
}
