<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estudiante extends Model
{
    protected $fillable = [
        'codigo_universitario',
        'dni',
        'nombres',
        'apellidos',
        'email_institucional',
        'celular',
        'escuela_profesional',
        'periodo_academico_id',
        'matriculado',
        'ultima_sincronizacion_sga',
    ];

    protected $casts = [
        'matriculado' => 'boolean',
        'ultima_sincronizacion_sga' => 'datetime',
    ];

    public function periodoAcademico()
    {
        return $this->belongsTo(PeriodoAcademico::class);
    }

    public function becados()
    {
        return $this->hasMany(Becado::class);
    }

    public function becaActiva()
    {
        return $this->hasOne(Becado::class)->where('estado', 'activo');
    }

    public function horariosClase()
    {
        return $this->hasMany(HorarioClase::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function cancelaciones()
    {
        return $this->hasMany(CancelacionTicket::class);
    }

    public function inasistencias()
    {
        return $this->hasMany(Inasistencia::class);
    }

    public function sanciones()
    {
        return $this->hasMany(Sancion::class);
    }

    public function sancionActiva()
    {
        return $this->hasOne(Sancion::class)->where('estado', 'activa');
    }

    public function nombreCompleto(): string
    {
        return "{$this->nombres} {$this->apellidos}";
    }
}
