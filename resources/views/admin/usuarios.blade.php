@extends('admin.layout')

@section('titulo', 'Usuarios')
@section('subtitulo', 'Cuentas locales del personal: administradores y trabajadores')
@section('activo', 'usuarios')

@push('cabecera')@include('admin._assets')@endpush

@section('contenido')
    @include('admin._avisos')
    @php $yo = auth('web')->id(); $abrir = $errors->any() && old('_form') === 'nuevo'; @endphp

    <section class="tarjeta">
        <div class="cab">
            <h2>Usuarios del sistema</h2>
        </div>

        <details class="nuevo editar" @if($abrir) open @endif style="margin-bottom:18px">
            <summary><span class="btn btn-primario">+ Nueva cuenta</span></summary>
            <form method="POST" action="{{ route('admin.usuarios.guardar') }}" data-una-vez>
                @csrf <input type="hidden" name="_form" value="nuevo">
                <div class="rejilla-form">
                    <div class="campo"><label for="n-nombre">Nombre</label><input id="n-nombre" name="nombre" type="text" maxlength="120" value="{{ old('nombre') }}" required></div>
                    <div class="campo"><label for="n-user">Usuario</label><input id="n-user" name="username" type="text" maxlength="60" value="{{ old('username') }}" autocapitalize="none" required></div>
                    <div class="campo"><label for="n-mail">Correo (opcional)</label><input id="n-mail" name="email" type="email" maxlength="120" value="{{ old('email') }}"></div>
                    <div class="campo"><label for="n-rol">Rol</label>
                        <select id="n-rol" name="rol"><option value="trabajador" @selected(old('rol', 'trabajador') === 'trabajador')>Trabajador</option><option value="administrador" @selected(old('rol') === 'administrador')>Administrador</option></select></div>
                    <div class="campo"><label for="n-puesto">Puesto (trabajador)</label>
                        <select id="n-puesto" name="puesto"><option value="">—</option><option value="puerta" @selected(old('puesto') === 'puerta')>Puerta · ingreso</option><option value="cocina" @selected(old('puesto') === 'cocina')>Cocina · entrega</option></select></div>
                    <div class="campo"><label for="n-pass">Contraseña (mín. 8)</label><input id="n-pass" name="password" type="password" minlength="8" maxlength="100" autocomplete="new-password" required></div>
                </div>
                <div class="acciones"><button class="btn btn-primario" type="submit">Crear cuenta</button></div>
            </form>
        </details>

        @foreach ($usuarios as $u)
            <div class="usuario-item">
                <div class="u-fila">
                    <span class="avatar">{{ mb_strtoupper(mb_substr($u->nombre, 0, 1)) }}</span>
                    <div><strong>{{ \App\Support\Fmt::nombre($u->nombre) }}@if ($u->id === $yo) <span class="tenue">(tú)</span>@endif</strong><small>{{ $u->username }}@if ($u->email) · {{ $u->email }}@endif</small></div>
                    <div><span class="chip {{ $u->esAdministrador() ? 'e-validado' : 'e-no_becado' }}">{{ $u->esAdministrador() ? 'Administrador' : 'Trabajador' }}</span>
                        @if ($u->puesto)<div class="puesto">{{ $u->puesto === 'puerta' ? 'Puerta · ingreso' : 'Cocina · entrega' }}</div>@endif</div>
                    <span class="chip {{ $u->activo ? 'e-activo' : 'e-inactivo' }}">{{ $u->activo ? 'Activo' : 'Inactivo' }}</span>
                    <details class="editar" @if ($errors->any() && old('_form') === 'u' . $u->id) open @endif>
                        <summary>Editar</summary>
                        <form method="POST" action="{{ route('admin.usuarios.actualizar', $u) }}" data-una-vez>
                            @csrf @method('PUT') <input type="hidden" name="_form" value="u{{ $u->id }}">
                            <div class="rejilla-form">
                                <div class="campo"><label for="e{{ $u->id }}-n">Nombre</label><input id="e{{ $u->id }}-n" name="nombre" type="text" maxlength="120" value="{{ $u->nombre }}" required></div>
                                <div class="campo"><label for="e{{ $u->id }}-m">Correo</label><input id="e{{ $u->id }}-m" name="email" type="email" maxlength="120" value="{{ $u->email }}"></div>
                                <div class="campo"><label for="e{{ $u->id }}-r">Rol</label>
                                    <select id="e{{ $u->id }}-r" name="rol" @disabled($u->id === $yo)><option value="trabajador" @selected(!$u->esAdministrador())>Trabajador</option><option value="administrador" @selected($u->esAdministrador())>Administrador</option></select>
                                    @if ($u->id === $yo)<input type="hidden" name="rol" value="administrador">@endif</div>
                                <div class="campo"><label for="e{{ $u->id }}-p">Puesto</label>
                                    <select id="e{{ $u->id }}-p" name="puesto"><option value="">—</option><option value="puerta" @selected($u->puesto === 'puerta')>Puerta · ingreso</option><option value="cocina" @selected($u->puesto === 'cocina')>Cocina · entrega</option></select></div>
                                <div class="campo"><label for="e{{ $u->id }}-c">Nueva contraseña (opcional)</label><input id="e{{ $u->id }}-c" name="password" type="password" minlength="8" maxlength="100" autocomplete="new-password" placeholder="Dejar vacío para no cambiarla"></div>
                                <div class="campo" style="justify-content:flex-end">
                                    @if ($u->id === $yo)<input type="hidden" name="activo" value="1"><span class="nota">No puedes desactivar tu propia cuenta.</span>
                                    @else<label class="check"><input type="checkbox" name="activo" value="1" @checked($u->activo)> Cuenta activa</label>@endif</div>
                            </div>
                            <div class="acciones"><button class="btn btn-primario btn-mini" type="submit">Guardar cambios</button></div>
                        </form>
                    </details>
                </div>
            </div>
        @endforeach
    </section>
@endsection
