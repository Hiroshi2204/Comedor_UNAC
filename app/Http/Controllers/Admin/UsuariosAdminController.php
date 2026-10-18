<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UsuarioSistema;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Cuentas locales del personal (administradores y trabajadores). */
class UsuariosAdminController extends Controller
{
    public function index()
    {
        $usuarios = UsuarioSistema::orderByRaw("rol = 'administrador' DESC")->orderBy('nombre')->get();

        return view('admin.usuarios', ['usuarios' => $usuarios]);
    }

    public function guardar(Request $r)
    {
        $d = $r->validate([
            'nombre'   => ['required', 'string', 'max:120'],
            'username' => ['required', 'string', 'max:60', 'alpha_dash', 'unique:usuarios_sistema,username'],
            'email'    => ['nullable', 'email', 'max:120', 'unique:usuarios_sistema,email'],
            'rol'      => ['required', Rule::in(['trabajador', 'administrador'])],
            'puesto'   => ['nullable', Rule::in(['puerta', 'cocina']), 'required_if:rol,trabajador'],
            'password' => ['required', 'string', 'min:8', 'max:100'],
        ], [
            'puesto.required_if' => 'Elige el puesto del trabajador (puerta o cocina).',
            'username.unique' => 'Ese usuario ya existe.', 'email.unique' => 'Ese correo ya está registrado.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        ]);

        UsuarioSistema::create([
            'nombre' => $d['nombre'], 'username' => $d['username'], 'email' => $d['email'] ?? null,
            'rol' => $d['rol'], 'puesto' => $d['rol'] === 'trabajador' ? $d['puesto'] : null,
            'password' => $d['password'], 'activo' => true,
        ]);

        return redirect()->route('admin.usuarios')->with('ok', 'Cuenta creada: ' . $d['username']);
    }

    public function actualizar(Request $r, UsuarioSistema $usuario)
    {
        $yo = $r->user("web");
        $esYo = $usuario->id === $yo->id;

        $d = $r->validate([
            'nombre'   => ['required', 'string', 'max:120'],
            'email'    => ['nullable', 'email', 'max:120', Rule::unique('usuarios_sistema', 'email')->ignore($usuario->id)],
            'rol'      => ['required', Rule::in(['trabajador', 'administrador'])],
            'puesto'   => ['nullable', Rule::in(['puerta', 'cocina']), 'required_if:rol,trabajador'],
            'activo'   => ['nullable', 'boolean'],
            'password' => ['nullable', 'string', 'min:8', 'max:100'],
        ], ['puesto.required_if' => 'Elige el puesto del trabajador (puerta o cocina).', 'password.min' => 'La contraseña debe tener al menos 8 caracteres.']);

        $activo = (bool) ($d['activo'] ?? false);

        // no puedes quitarte el acceso a ti mismo
        if ($esYo && (!$activo || $d['rol'] !== 'administrador')) {
            return back()->with('error', 'No puedes desactivarte ni quitarte el rol de administrador a ti mismo.');
        }
        // siempre debe quedar al menos un administrador activo
        if ($usuario->esAdministrador() && ($d['rol'] !== 'administrador' || !$activo)
            && UsuarioSistema::where('rol', 'administrador')->where('activo', true)->where('id', '!=', $usuario->id)->doesntExist()) {
            return back()->with('error', 'Debe quedar al menos un administrador activo.');
        }

        $usuario->fill([
            'nombre' => $d['nombre'], 'email' => $d['email'] ?? null, 'rol' => $d['rol'],
            'puesto' => $d['rol'] === 'trabajador' ? $d['puesto'] : null, 'activo' => $activo,
        ]);
        if (!empty($d['password'])) {
            $usuario->password = $d['password'];
        }
        $usuario->save();

        return redirect()->route('admin.usuarios')->with('ok', 'Cuenta actualizada: ' . $usuario->username);
    }
}
