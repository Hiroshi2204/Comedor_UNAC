<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Ticket extends Model
{
    protected $fillable = [
        'estudiante_id',
        'periodo_academico_id',
        'ventana_atencion_id',
        'ticket_origen_id',
        'trabajador_id',
        'tipo',
        'servicio',
        'fecha',
        'estado',
        'codigo_qr',
        'fecha_hora_recojo',
        'vence_at',
    ];

    protected $casts = [
        'fecha' => 'date',
        'fecha_hora_recojo' => 'datetime',
        'vence_at' => 'datetime',
        'validado_at' => 'datetime',
        'ultima_oportunidad_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(fn($t) => $t->codigo_qr ??= Str::random(40));
    }

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

    public function validadoPor()
    {
        return $this->belongsTo(UsuarioSistema::class, 'validado_por');
    }
    /** Código legible para mostrar (no es el contenido del QR). Si existe una columna `codigo`, se respeta. */
    public function getCodigoAttribute($valor): string
    {
        return $valor ?: sprintf('TK-%s-%05d', $this->fecha?->format('Y') ?? date('Y'), $this->id);
    }

    public function getEnUltimaOportunidadAttribute(): bool
    {
        return $this->ultima_oportunidad_at !== null && $this->estado === 'generado';
    }
}
