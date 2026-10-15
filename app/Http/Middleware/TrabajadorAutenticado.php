<?php

namespace App\Http\Middleware;

use App\Models\UsuarioSistema;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Deja pasar solo a trabajadores activos con puesto (puerta o cocina). */
class TrabajadorAutenticado
{
    public function handle(Request $request, Closure $next)
    {
        $guard = Auth::guard('sistema');
        $u = $guard->user();

        // instanceof: garantiza el tipo (y el editor reconoce esTrabajador())
        if (!$u instanceof UsuarioSistema || !$u->activo || !$u->esTrabajador() || !$u->puesto) {
            $guard->logout();
            return redirect()->route('trabajador.login');
        }

        $request->attributes->set('trabajador', $u);

        $response = $next($request);
        // que el navegador no guarde estas pantallas (datos de estudiantes y QR)
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}