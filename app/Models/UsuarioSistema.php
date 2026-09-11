<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class UsuarioSistema extends Authenticatable
{
    use Notifiable;

    protected $table = 'usuarios_sistema';

    protected $fillable = ['nombre', 'email', 'password', 'rol', 'activo'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'activo' => 'boolean',
        'password' => 'hashed', // Laravel 10: hashea automáticamente al asignar
    ];

    public function ticketsEntregados()
    {
        return $this->hasMany(Ticket::class, 'trabajador_id');
    }

    public function esAdministrador(): bool
    {
        return $this->rol === 'administrador';
    }

    public function esTrabajador(): bool
    {
        return $this->rol === 'trabajador';
    }
}
