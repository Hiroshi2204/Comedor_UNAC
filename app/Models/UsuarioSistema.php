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

    protected $fillable = ['nombre', 'email', 'username', 'password', 'rol', 'puesto', 'activo'];

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
    public function esDePuerta(): bool
    {
        return $this->esTrabajador() && $this->puesto === 'puerta';
    }

    public function esDeCocina(): bool
    {
        return $this->esTrabajador() && $this->puesto === 'cocina';
    }

    /** $escaneo: 'puerta' (valida el ingreso) o 'entrega' (cocina entrega la comida). */
    public function puedeEscanear(string $escaneo): bool
    {
        return $this->activo && match ($escaneo) {
            'puerta'  => $this->esDePuerta(),
            'entrega' => $this->esDeCocina(),
            default   => false,
        };
    }
}
