<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Deja pasar solo a administradores activos; si cambiaron de rol o los desactivaron, cierra su sesión. */
class SoloAdministrador
{
    public function handle(Request $request, Closure $next)
    {
        $u = $request->user('web');

        if (!$u || !$u->activo || !$u->esAdministrador()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['login' => 'Tu sesión no tiene permiso para este panel.']);
        }

        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store, private'); // el botón "atrás" no muestra datos tras salir

        return $response;
    }
}