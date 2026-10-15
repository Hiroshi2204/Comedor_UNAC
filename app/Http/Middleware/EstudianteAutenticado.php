<?php

namespace App\Http\Middleware;

use App\Models\Estudiante;
use Closure;
use Illuminate\Http\Request;

class EstudianteAutenticado
{
    public function handle(Request $request, Closure $next)
    {
        $id = $request->session()->get('estudiante_id');
        $e = $id ? Estudiante::where('id', $id)->where('matriculado', true)->first() : null;

        if (!$e) {
            $request->session()->forget('estudiante_id');
            return redirect()->route('estudiante.login');
        }

        $request->attributes->set('estudiante', $e);
        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}
