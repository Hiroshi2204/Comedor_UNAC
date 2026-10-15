<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inasistencia extends Model
{
    protected $table = 'inasistencias';

    protected $fillable = ['estudiante_id', 'ticket_id', 'periodo_academico_id', 'fecha'];

    protected $casts = ['fecha' => 'date'];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class);
    }
}
