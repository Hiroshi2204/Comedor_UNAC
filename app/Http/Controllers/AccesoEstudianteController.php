<?php

namespace App\Http\Controllers;

use App\Models\Estudiante;
use App\Services\SgaAuthService;
use App\Services\SgaSsoToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class AccesoEstudianteController extends Controller
{
    public function __construct(private SgaAuthService $sga, private SgaSsoToken $sso) {}

    public function formulario()
    {
        return view('estudiante.login', ['username' => session('login_username')]);
    }

    /** Link desde el SGA. Con firma válida entra directo; sin firma solo pre-llena el código. */
    public function enlaceSga(Request $r)
    {
        $username = trim((string) $r->query('username'));
        $firmado = $r->filled('sig');

        if ($username !== '' && $firmado) {
            if ($this->sso->verificar($username, (string) $r->query('ts'), (string) $r->query('sig'))) {
                if ($e = $this->buscar($username)) {
                    return $this->iniciar($r, $e);
                }
                return redirect()->route('estudiante.login')
                    ->withErrors(['username' => 'No figuras como matriculado en el comedor.']);
            }
            Log::warning('Enlace SGA con firma inválida o vencida', ['username' => $username, 'ip' => $r->ip()]);
            session()->flash('login_username', $username);
            return redirect()->route('estudiante.login')
                ->withErrors(['username' => 'El enlace venció o no es válido. Ingresa tu contraseña del SGA.']);
        }

        // sin firma: no se confía en el username, solo se pre-llena el formulario
        if ($username !== '') {
            session()->flash('login_username', $username);
        }
        return redirect()->route('estudiante.login');
    }

    public function ingresar(Request $r)
    {
        $datos = $r->validate([
            'username' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'max:100'],
        ]);

        $entrada  = trim($datos['username']);
        $username = $this->resolverCodigo($entrada);

        // la clave de bloqueo usa lo escrito, para no revelar si el correo existe
        $clave = 'login-sga:' . mb_strtolower($entrada) . '|' . $r->ip();

        if (RateLimiter::tooManyAttempts($clave, 5)) {
            $seg = RateLimiter::availableIn($clave);
            return $this->error($r, "Demasiados intentos. Espera {$seg} segundos.");
        }

        // correo que no corresponde a ningún alumno: mismo mensaje genérico
        if ($username === null) {
            RateLimiter::hit($clave, 60);
            return $this->error($r, 'Código o contraseña incorrectos.');
        }

        try {
            $ok = $this->sga->validar($username, $datos['password']);
        } catch (\Throwable $ex) {
            report($ex);
            return $this->error($r, 'No se pudo conectar con el SGA. Intenta en unos minutos.');
        }

        if (!$ok) {
            RateLimiter::hit($clave, 60);
            return $this->error($r, 'Código o contraseña incorrectos.');
        }
        RateLimiter::clear($clave);

        // recién con credenciales válidas se revela si está matriculado (evita enumerar códigos)
        $e = $this->buscar($username);
        if (!$e) {
            return $this->error($r, 'Tu cuenta del SGA es válida, pero no figuras como matriculado en el comedor.');
        }

        return $this->iniciar($r, $e);
    }

    public function salir(Request $r)
    {
        $r->session()->forget('estudiante_id');
        $r->session()->invalidate();
        $r->session()->regenerateToken();

        return redirect()->route('estudiante.login');
    }

    private function buscar(string $username): ?Estudiante
    {
        return Estudiante::where('codigo_universitario', $username)->where('matriculado', true)->first();
    }

    private function iniciar(Request $r, Estudiante $e)
    {
        $r->session()->regenerate(); // evita fijación de sesión
        $r->session()->put('estudiante_id', $e->id);

        return redirect()->route('estudiante.inicio');
    }

    private function error(Request $r, string $mensaje)
    {
        return back()->withInput($r->only('username')) // nunca se devuelve la contraseña
            ->withErrors(['username' => $mensaje]);
    }

    private function resolverCodigo(string $entrada): ?string
    {
        if (!str_contains($entrada, '@')) {
            return $entrada;
        }

        return Estudiante::where('email_institucional', $entrada)
            ->where('matriculado', true)
            ->value('codigo');   // ajusta al nombre real de la columna del código
    }
}
