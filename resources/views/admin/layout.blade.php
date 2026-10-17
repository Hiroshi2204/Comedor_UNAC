@php
    $usuario = $usuario ?? auth('web')->user();
    $activo = trim($__env->yieldContent('activo'));
    $inicial = mb_strtoupper(mb_substr($usuario->nombre ?? 'A', 0, 1));

    // El WhatsApp del modelo se reemplazó por "Notificaciones" (el sistema ya no envía por WhatsApp).
    // Un ítem se activa solo cuando existe su ruta; mientras tanto aparece atenuado.
    $menu = [
        ['dashboard',      'Dashboard',            'dashboard'],
        ['tickets',        'Tickets',              'admin.tickets'],
        ['estudiantes',    'Estudiantes',          'admin.estudiantes'],
        ['becados',        'Becados OBU',          'admin.becados'],
        ['horarios',       'Horarios',             'admin.horarios'],
        ['ventanas',       'Ventanas de atención', 'admin.ventanas'],
        ['sanciones',      'Sanciones',            'admin.sanciones'],
        ['reportes',       'Reportes',             'admin.reportes'],
        ['notificaciones', 'Notificaciones',       'admin.notificaciones'],
        ['usuarios',       'Usuarios',             'admin.usuarios'],
        ['configuracion',  'Configuración',        'admin.configuracion'],
    ];
@endphp
<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('titulo') — Comedor UNAC</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    @stack('cabecera')
</head>

<body class="admin">
    <aside class="lateral">
        <div class="marca">
            <div class="siglas">UNAC</div>
            <div class="nombre">COMEDOR UNIVERSITARIO</div>
        </div>

        <nav aria-label="Menú principal">
            @foreach ($menu as [$clave, $texto, $ruta])
                @if (Route::has($ruta))
                    <a href="{{ route($ruta) }}" class="item {{ $activo === $clave ? 'activo' : '' }}"
                        @if ($activo === $clave) aria-current="page" @endif>
                        @include('admin._icono', ['n' => $clave]) <span>{{ $texto }}</span>
                    </a>
                @else
                    <span class="item deshabilitado" aria-disabled="true" title="Próximamente">
                        @include('admin._icono', ['n' => $clave]) <span>{{ $texto }}</span>
                    </span>
                @endif
            @endforeach
        </nav>

        <div class="usuario">
            <div class="fila">
                <span class="avatar">{{ $inicial }}</span>
                <div>
                    <strong>{{ $usuario->nombre ?? 'Administrador' }}</strong>
                    <small>{{ $usuario->email ?? '' }}</small>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="salir">Cerrar sesión</button>
            </form>
        </div>
    </aside>

    <div class="contenido">
        <header class="barra">
            <div>
                <h1>@yield('titulo')</h1>
                <p>@yield('subtitulo')</p>
            </div>
            <div class="derecha">
                <span class="etiqueta-rol">ADMINISTRADOR</span>
                <span class="avatar azul" title="{{ $usuario->nombre ?? '' }}">{{ $inicial }}</span>
            </div>
        </header>

        <main>
            @yield('contenido')
        </main>
    </div>
</body>

</html>