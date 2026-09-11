<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfiguracionSistema extends Model
{
    protected $table = 'configuraciones_sistema';

    protected $fillable = ['clave', 'valor', 'descripcion'];

    public static function obtener(string $clave, $default = null)
    {
        return static::where('clave', $clave)->value('valor') ?? $default;
    }
}