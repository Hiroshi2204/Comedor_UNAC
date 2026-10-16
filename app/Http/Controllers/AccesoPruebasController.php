<?php

namespace App\Http\Controllers;

use App\Models\Estudiante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * ACCESO DE PRUEBAS: entra como un estudiante escribiendo solo su código (sin contraseña).
 * Solo funciona con ACCESO_PRUEBAS=true Y entorno local/testing. Nunca debe existir en producción.
 */
class AccesoPruebasController extends Controller
{
    public static function habilitado(): bool
    {
        return (bool) config('comedor.acceso_pruebas.activo') && app()->environment(['local', 'testing']);
    }

    public function formulario()
    {
        abort_unless(self::habilitado(), 404);

        $columnas = ['id', 'codigo_universitario', 'escuela_profesional'];

        // atajos para probar rápido: algunos becados y algunos no becados
        $atajos = Estudiante::where('matriculado', true)->whereHas('becaActiva')
                ->inRandomOrder()->limit(5)->get($columnas)
                ->map(fn ($e) => ['codigo' => $e->codigo_universitario, 'escuela' => $e->escuela_profesional, 'tipo' => 'Becado'])
            ->concat(
                Estudiante::where('matriculado', true)->whereDoesntHave('becaActiva')
                    ->inRandomOrder()->limit(3)->get($columnas)
                    ->map(fn ($e) => ['codigo' => $e->codigo_universitario, 'escuela' => $e->escuela_profesional, 'tipo' => 'No becado'])
            );

        return view('estudiante.acceso-pruebas', ['atajos' => $atajos]);
    }

    public function ingresar(Request $r)
    {
        abort_unless(self::habilitado(), 404);

        $datos = $r->validate(['codigo' => ['required', 'string', 'max:30']]);
        $codigo = trim($datos['codigo']);

        $e = Estudiante::where('codigo_universitario', $codigo)->where('matriculado', true)->first();
        if (!$e) {
            return back()->withInput()->withErrors(['codigo' => 'No existe un estudiante matriculado con ese código.']);
        }

        Log::warning('Acceso de PRUEBAS sin contraseña', ['codigo' => $e->codigo_universitario, 'ip' => $r->ip()]);

        // misma sesión que usa el login real con el SGA: el resto del sistema no distingue la diferencia
        $r->session()->regenerate();
        $r->session()->put('estudiante_id', $e->id);

        return redirect()->route('estudiante.inicio');
    }
}