<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\UsuarioSistema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class LoginController extends Controller
{
    /** Hash ficticio: se compara siempre, exista o no el usuario, para que la respuesta tarde lo mismo. */
    private const HASH_FICTICIO = '$2y$10$gLYILVpURsqFj4MGH6p9AOKgcWvWk2FPO./.J6onMNzdvSMpaaiHS';

    public function create()
    {
        return view('admin.login');
    }

    public function store(Request $r)
    {
        $datos = $r->validate([
            'login'    => ['required', 'string', 'max:150'],
            'password' => ['required', 'string', 'max:100'],
        ]);

        $login = trim($datos['login']);
        $clave = 'login-admin:' . mb_strtolower($login) . '|' . $r->ip();

        if (RateLimiter::tooManyAttempts($clave, 5)) {
            $seg = RateLimiter::availableIn($clave);
            return back()->withInput($r->only('login'))
                ->withErrors(['login' => "Demasiados intentos. Espera {$seg} segundos."]);
        }

        $campo = str_contains($login, '@') ? 'email' : 'username';
        $usuario = UsuarioSistema::where($campo, $login)->where('rol', 'administrador')->where('activo', true)->first();

        $claveOk = Hash::check($datos['password'], $usuario?->password ?? self::HASH_FICTICIO);

        // mismo mensaje para usuario inexistente, inactivo, sin rol de administrador o clave errónea
        if (!$usuario || !$claveOk) {
            RateLimiter::hit($clave, 60);
            Log::info('Login de administrador fallido', ['login' => $login, 'ip' => $r->ip()]);

            return back()->withInput($r->only('login'))
                ->withErrors(['login' => 'Usuario o contraseña incorrectos.']);
        }

        RateLimiter::clear($clave);
        Auth::guard('web')->login($usuario);
        $r->session()->regenerate(); // evita fijación de sesión

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $r)
    {
        Auth::guard('web')->logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();

        return redirect()->route('login');
    }
}
