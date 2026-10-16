@php
$nombre = \Illuminate\Support\Str::title(mb_strtolower($u->nombre));
$inicial = mb_strtoupper(mb_substr($nombre, 0, 1));
$puestoTxt = $u->esDePuerta() ? 'Puerta · Ingreso' : 'Cocina · Entrega';
@endphp
<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="no-referrer">
    <title>@yield('titulo', 'Panel') — Comedor UNAC</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @stack('head')
    <style>
        :root {
            --azul: #12356B;
            --azul-2: #1E73BE;
            --azul-act: #2B5BA0;
            --tinta: #0F2447;
            --gris: #64748B;
            --borde: #D9E2EF;
            --campo: #FBFCFE;
            --fondo: #F5F7FB;
            --claro: #CFE0F4;
            --verde: #15803D;
            --verde-bg: #E7F6EC;
            --ambar: #E8820C;
            --ambar-bg: #FFF4E0;
            --rojo: #D9272E;
            --rojo-bg: #FDECEC;
            --chip: #E6EEFB;
            --lateral: 240px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif;
            background: var(--fondo);
            color: var(--tinta);
            -webkit-font-smoothing: antialiased;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        button {
            font: inherit;
            cursor: pointer;
        }

        :focus-visible {
            outline: 2px solid var(--azul-2);
            outline-offset: 2px;
        }

        /* ---------- Barra lateral ---------- */
        .lateral {
            position: fixed;
            inset: 0 auto 0 0;
            width: var(--lateral);
            background: var(--azul);
            color: #fff;
            display: flex;
            flex-direction: column;
            padding: 24px 12px 16px;
            z-index: 30;
            transition: transform .2s ease;
        }

        .marca {
            padding: 0 8px 18px;
            border-bottom: 1px solid rgba(255, 255, 255, .14);
        }

        .marca b {
            display: block;
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1;
        }

        .marca span {
            display: block;
            margin-top: 6px;
            font-size: .62rem;
            font-weight: 700;
            letter-spacing: .08em;
            color: var(--claro);
        }

        .menu {
            display: flex;
            flex-direction: column;
            gap: 4px;
            margin-top: 18px;
        }

        .menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            border-radius: 8px;
            font-size: .82rem;
            font-weight: 500;
            color: #E3ECF9;
        }

        .menu a:hover {
            background: rgba(255, 255, 255, .08);
        }

        .menu a.activo {
            background: var(--azul-act);
            color: #fff;
            font-weight: 600;
        }

        .menu svg {
            width: 16px;
            height: 16px;
            flex: none;
            stroke: currentColor;
            fill: none;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .usuario {
            margin-top: auto;
            padding: 14px 8px 0;
            border-top: 1px solid rgba(255, 255, 255, .14);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .usuario .datos {
            min-width: 0;
            flex: 1;
        }

        .usuario b {
            display: block;
            font-size: .78rem;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .usuario small {
            display: block;
            font-size: .68rem;
            color: var(--claro);
        }

        .usuario form {
            margin: 0;
        }

        .salir {
            background: transparent;
            border: 1px solid rgba(255, 255, 255, .3);
            color: #fff;
            border-radius: 6px;
            padding: 5px 9px;
            font-size: .7rem;
            font-weight: 600;
        }

        .salir:hover {
            background: rgba(255, 255, 255, .12);
        }

        .avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            flex: none;
            background: var(--azul-2);
            color: #fff;
            font-size: .8rem;
            font-weight: 700;
        }

        .velo {
            display: none;
        }

        /* ---------- Contenido ---------- */
        .contenido {
            margin-left: var(--lateral);
            min-height: 100vh;
            padding: 28px clamp(16px, 3vw, 40px) 48px;
        }

        .cabecera {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 22px;
        }

        .cabecera h1 {
            margin: 0;
            font-size: 1.6rem;
            font-weight: 700;
        }

        .cabecera p {
            margin: 4px 0 0;
            font-size: .8rem;
            color: var(--gris);
        }

        .cabecera .der {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .hamb {
            display: none;
            width: 40px;
            height: 40px;
            border: 1px solid var(--borde);
            background: #fff;
            border-radius: 8px;
            color: var(--tinta);
            align-items: center;
            justify-content: center;
            flex: none;
        }

        .hamb svg {
            width: 20px;
            height: 20px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
            stroke-linecap: round;
        }

        .chip {
            display: inline-block;
            padding: 5px 12px;
            font-size: .66rem;
            font-weight: 700;
            letter-spacing: .04em;
            border-radius: 999px;
            background: var(--chip);
            color: var(--azul);
            white-space: nowrap;
        }

        /* ---------- Banner ---------- */
        .banner {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            padding: 24px 28px;
            border-radius: 14px;
            color: #fff;
            background: linear-gradient(100deg, #12356B 0%, #1E73BE 100%);
            margin-bottom: 20px;
        }

        .banner small {
            font-size: .72rem;
            color: var(--claro);
        }

        .banner h2 {
            margin: 2px 0 6px;
            font-size: 1.7rem;
            font-weight: 700;
            line-height: 1.15;
        }

        .banner .meta {
            font-size: .74rem;
            color: var(--claro);
        }

        .banner .estado {
            text-align: right;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: flex-end;
            gap: 14px;
        }

        .banner .periodo {
            font-size: .76rem;
            font-weight: 600;
        }

        .insignia {
            padding: 6px 18px;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 700;
            background: #E7F6EC;
            color: var(--verde);
        }

        .insignia.neutra {
            background: rgba(255, 255, 255, .16);
            color: #fff;
        }

        /* ---------- Rejilla y tarjetas ---------- */
        .rejilla {
            display: grid;
            grid-template-columns: minmax(0, 1.9fr) minmax(260px, 1fr);
            gap: 20px;
            align-items: start;
        }

        .col {
            display: flex;
            flex-direction: column;
            gap: 20px;
            min-width: 0;
        }

        .tarjeta {
            background: #fff;
            border: 1px solid var(--borde);
            border-radius: 14px;
            padding: 22px 24px;
        }

        .tarjeta>header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 16px;
        }

        .tarjeta h3 {
            margin: 0;
            font-size: 1rem;
            font-weight: 700;
        }

        .titulo-dia {
            display: flex;
            align-items: baseline;
            gap: 10px;
            margin: 0 0 12px;
            font-size: .78rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: var(--gris);
        }

        .titulo-dia span {
            font-weight: 500;
            text-transform: none;
            letter-spacing: 0;
        }

        .estado-chip {
            padding: 5px 12px;
            border-radius: 999px;
            font-size: .66rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            background: var(--chip);
            color: var(--azul);
            white-space: nowrap;
        }

        .e-disponible,
        .e-reservable,
        .e-validado,
        .e-recogido {
            background: var(--verde-bg);
            color: var(--verde);
        }

        .e-agotado,
        .e-cerrado,
        .e-vencido {
            background: var(--rojo-bg);
            color: var(--rojo);
        }

        .e-cancelado,
        .e-no_recogido {
            background: #EEF1F7;
            color: var(--gris);
        }

        .e-aun_no_abre,
        .e-en_clase {
            background: var(--ambar-bg);
            color: #B45F00;
        }

        .ticket {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 24px;
            align-items: center;
        }

        .ticket dl {
            margin: 0;
            display: grid;
            gap: 18px;
        }

        .ticket dt {
            font-size: .68rem;
            color: var(--gris);
            font-weight: 500;
        }

        .ticket dd {
            margin: 4px 0 0;
            font-weight: 700;
            color: var(--tinta);
        }

        .ticket dd.grande {
            font-size: 1.7rem;
            letter-spacing: -.01em;
        }

        .ticket dd.medio {
            font-size: 1.05rem;
            word-break: break-all;
        }

        .qr-caja {
            margin: 0;
            text-align: center;
        }

        .qr-caja .marco {
            display: inline-block;
            padding: 14px;
            border: 1px solid var(--borde);
            border-radius: 14px;
            background: #fff;
            line-height: 0;
        }

        .qr-caja svg {
            width: 168px;
            height: 168px;
        }

        .qr-caja figcaption {
            margin-top: 10px;
            font-size: .68rem;
            font-weight: 600;
            color: var(--gris);
        }

        .btn {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            height: 44px;
            border-radius: 8px;
            border: 0;
            font-size: .85rem;
            font-weight: 700;
        }

        .btn-primario {
            background: var(--azul-2);
            color: #fff;
            margin-top: 12px;
        }

        .btn-primario:hover {
            filter: brightness(.95);
        }

        .btn-cancelar {
            background: var(--rojo-bg);
            color: var(--rojo);
            width: auto;
            padding: 0 28px;
            margin-top: 20px;
        }

        .btn-cancelar:hover {
            background: #FAD9D9;
        }

        .nota {
            margin: 0;
            font-size: .85rem;
            line-height: 1.5;
            color: var(--gris);
        }

        .nota b {
            color: var(--tinta);
        }

        .aviso {
            margin: 0;
            padding: 12px 14px;
            font-size: .85rem;
            line-height: 1.45;
            background: var(--ambar-bg);
            border: 1px solid #F5D79A;
            border-radius: 10px;
            color: #7A4A00;
        }

        .alerta {
            padding: 12px 14px;
            margin-bottom: 16px;
            border-radius: 10px;
            font-size: .85rem;
            font-weight: 600;
        }

        .alerta.ok {
            background: var(--verde-bg);
            border: 1px solid #A7E3BB;
            color: var(--verde);
        }

        .alerta.error {
            background: var(--rojo-bg);
            border: 1px solid #F4B4B4;
            color: var(--rojo);
        }

        label.etq {
            display: block;
            margin: 12px 0 6px;
            font-size: .72rem;
            font-weight: 700;
            color: var(--gris);
        }

        select {
            width: 100%;
            height: 44px;
            padding: 0 12px;
            font: inherit;
            font-size: .88rem;
            color: var(--tinta);
            background: var(--campo);
            border: 1px solid var(--borde);
            border-radius: 8px;
        }

        /* ---------- Columna derecha ---------- */
        .clase+.clase {
            margin-top: 16px;
        }

        .clase b {
            display: block;
            font-size: .92rem;
        }

        .clase span {
            display: block;
            margin-top: 3px;
            font-size: .72rem;
            color: var(--gris);
        }

        .asistencia .cifra {
            margin: 6px 0 4px;
            font-size: 1.9rem;
            font-weight: 700;
            color: var(--ambar);
        }

        .asistencia .cifra.cero {
            color: var(--verde);
        }

        .asistencia .cifra.limite {
            color: var(--rojo);
        }

        .asistencia small {
            display: block;
            font-size: .72rem;
            color: var(--gris);
        }

        .asistencia .sit {
            margin-top: 10px;
            font-size: .74rem;
            font-weight: 700;
            color: var(--verde);
        }

        .asistencia .sit.mal {
            color: var(--rojo);
        }

        .barra {
            display: flex;
            gap: 4px;
            margin-top: 10px;
        }

        .barra i {
            flex: 1;
            height: 6px;
            border-radius: 3px;
            background: var(--borde);
        }

        .barra i.on {
            background: var(--ambar);
        }

        .barra.limite i.on {
            background: var(--rojo);
        }

        section[id] {
            scroll-margin-top: 16px;
        }

        /* ---------- Tablet y móvil ---------- */
        @media (max-width: 1024px) {
            .rejilla {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 860px) {
            .lateral {
                transform: translateX(-100%);
            }

            body.menu-abierto .lateral {
                transform: none;
                box-shadow: 0 0 32px rgba(0, 0, 0, .25);
            }

            body.menu-abierto .velo {
                display: block;
                position: fixed;
                inset: 0;
                background: rgba(15, 36, 71, .45);
                z-index: 20;
            }

            .contenido {
                margin-left: 0;
                padding: 16px 16px 40px;
            }

            .hamb {
                display: inline-flex;
            }

            .cabecera {
                align-items: center;
            }

            .cabecera .titulos {
                flex: 1;
                min-width: 0;
            }

            .cabecera h1 {
                font-size: 1.3rem;
            }

            .cabecera .der .chip {
                display: none;
            }

            .banner {
                flex-direction: column;
                padding: 20px;
            }

            .banner .estado {
                flex-direction: row;
                align-items: center;
                text-align: left;
                justify-content: space-between;
            }

            .banner h2 {
                font-size: 1.4rem;
            }
        }

        @media (max-width: 520px) {
            .tarjeta {
                padding: 18px 16px;
            }

            .ticket {
                grid-template-columns: 1fr;
            }

            .qr-caja {
                order: -1;
            }

            .btn-cancelar {
                width: 100%;
            }
        }

        .estado-chip.e-ingreso {
            background: var(--verde-bg);
            color: var(--verde);
        }

        .puesto {
            display: block;
            margin-top: 2px;
            font-size: .68rem;
            color: var(--claro);
        }
    </style>
</head>

<body data-pagina="@yield('pagina', 'inicio')">
    <aside class="lateral" id="lateral" aria-label="Menú del trabajador">
        <div class="marca"><b>UNAC</b><span>COMEDOR UNIVERSITARIO</span></div>

        <nav class="menu" id="menu">
            <a href="{{ route('trabajador.inicio') }}" @class(['activo'=> request()->routeIs('trabajador.inicio', 'trabajador.ticket')])>
                <svg viewBox="0 0 24 24">
                    <path d="M3 11l9-8 9 8" />
                    <path d="M5 10v10h14V10" />
                </svg>Dashboard</a>
            <a href="{{ route('trabajador.inicio', ['escanear' => 1]) }}">
                <svg viewBox="0 0 24 24">
                    <path d="M4 8V5a1 1 0 0 1 1-1h3M16 4h3a1 1 0 0 1 1 1v3M20 16v3a1 1 0 0 1-1 1h-3M8 20H5a1 1 0 0 1-1-1v-3" />
                    <path d="M4 12h16" />
                </svg>Escanear QR</a>
            <a href="{{ route('trabajador.actividad') }}" @class(['activo'=> request()->routeIs('trabajador.actividad')])>
                <svg viewBox="0 0 24 24">
                    <path d="M4 6h16M4 12h16M4 18h10" />
                </svg>Mi actividad</a>
        </nav>

        <div class="usuario">
            <span class="avatar" aria-hidden="true">{{ $inicial }}</span>
            <div class="datos"><b>{{ $nombre }}</b><small>{{ $puestoTxt }}</small></div>
            <form method="POST" action="{{ route('trabajador.salir') }}">@csrf <button class="salir">Salir</button></form>
        </div>
    </aside>
    <div class="velo" id="velo"></div>

    <div class="contenido">
        <header class="cabecera">
            <button class="hamb" id="abrir-menu" aria-controls="lateral" aria-expanded="false" aria-label="Abrir menú">
                <svg viewBox="0 0 24 24">
                    <path d="M4 7h16M4 12h16M4 17h16" />
                </svg>
            </button>
            <div class="titulos">
                <h1>@yield('titulo')</h1>
                <p>@yield('subtitulo')</p>
            </div>
            <div class="der"><span class="chip">TRABAJADOR</span><span class="avatar" aria-hidden="true">{{ $inicial }}</span></div>
        </header>

        <main>@yield('contenido')</main>
    </div>

    <script>
        (function() {
            var body = document.body,
                boton = document.getElementById('abrir-menu');

            function menu(abrir) {
                body.classList.toggle('menu-abierto', abrir);
                boton.setAttribute('aria-expanded', abrir ? 'true' : 'false');
            }
            boton.addEventListener('click', function() {
                menu(!body.classList.contains('menu-abierto'));
            });
            document.getElementById('velo').addEventListener('click', function() {
                menu(false);
            });
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') menu(false);
            });
        })();
    </script>
    @stack('scripts')
</body>

</html>