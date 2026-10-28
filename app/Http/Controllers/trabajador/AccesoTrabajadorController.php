<?php

namespace App\Http\Controllers\Trabajador;

use App\Http\Controllers\Controller;
use App\Models\UsuarioSistema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

/** Login del personal (puerta y cocina) con cuenta local de `usuarios_sistema`. */
class AccesoTrabajadorController extends Controller
{

    public function formulario()
    {
        return Auth::guard('sistema')->check()
            ? redirect()->route('trabajador.inicio')
            : view('trabajador.login');
    }

    public function ingresar(Request $r)
    {
        $d = $r->validate([
            'username' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'max:100'],
        ]);

        $id    = trim($d['username']);
        $clave = 'login-trab:' . mb_strtolower($id) . '|' . $r->ip();

        if (RateLimiter::tooManyAttempts($clave, 5)) {
            return $this->error($r, 'Demasiados intentos. Espera ' . RateLimiter::availableIn($clave) . ' segundos.');
        }

        $guard = Auth::guard('sistema');
        $campo = str_contains($id, '@') ? 'email' : 'username';

        // solo cuentas activas; la contraseña se compara con el hash guardado
        if (!$guard->attempt([$campo => $id, 'password' => $d['password'], 'activo' => true])) {
            RateLimiter::hit($clave, 60);
            return $this->error($r, 'Usuario o contraseña incorrectos.');
        }

        $u = $guard->user();
        if (!$u instanceof UsuarioSistema || !$u->esTrabajador() || !$u->puesto) {
            $guard->logout();
            return $this->error($r, 'Tu cuenta no tiene acceso a esta pantalla.');
        }

        RateLimiter::clear($clave);
        $r->session()->regenerate();

        return redirect()->route('trabajador.inicio');
    }

    public function salir(Request $r)
    {
        Auth::guard('sistema')->logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();

        return redirect()->route('trabajador.login');
    }

    private function error(Request $r, string $mensaje)
    {
        return redirect()->route('trabajador.login')
            ->withInput($r->only('username'))
            ->withErrors(['username' => $mensaje]);
    }
}
