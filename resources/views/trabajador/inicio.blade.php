@extends('trabajador.layouts.app')

@section('titulo', $u->esDePuerta() ? 'Ingreso de estudiantes' : 'Entrega de tickets')
@section('subtitulo', 'Búsqueda rápida por código universitario o QR')
@section('pagina', 'inicio')

@push('head')
<style>
    .buscar {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto auto auto;
        gap: 12px;
        align-items: center;
    }

    .buscar input[type=text] {
        width: 100%;
        height: 48px;
        padding: 0 16px;
        font: inherit;
        font-size: .92rem;
        color: var(--tinta);
        background: var(--campo);
        border: 1px solid var(--borde);
        border-radius: 8px;
    }

    .buscar input::placeholder {
        color: #94A3B8;
    }

    .buscar .btn {
        width: auto;
        height: 48px;
        padding: 0 26px;
        margin: 0;
        white-space: nowrap;
    }

    .btn-azul-claro {
        background: var(--chip);
        color: var(--tinta);
    }

    .btn-azul-claro:hover {
        background: #D7E4F8;
    }

    .operativo {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        height: 48px;
        padding: 0 22px;
        border-radius: 8px;
        font-size: .8rem;
        font-weight: 700;
        background: var(--verde-bg);
        color: var(--verde);
        white-space: nowrap;
    }

    .operativo::before {
        content: '';
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: currentColor;
    }

    .operativo.cerrado {
        background: #EEF1F7;
        color: var(--gris);
    }

    .dos {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-top: 20px;
        align-items: stretch;
    }

    .filtro-serv {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
    }

    .filtro-serv a {
        padding: 4px 10px;
        border-radius: 999px;
        font-size: .68rem;
        font-weight: 700;
        color: var(--gris);
        background: #EEF1F7;
    }

    .filtro-serv a.on {
        background: var(--azul-2);
        color: #fff;
    }

    .metricas {
        display: grid;
        gap: 4px;
    }

    .metrica {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 11px 6px;
    }

    .metrica+.metrica {
        border-top: 1px solid #F0F3F9;
    }

    .metrica span {
        font-size: .78rem;
        font-weight: 700;
        color: var(--gris);
    }

    .metrica b {
        font-size: 1.5rem;
        font-weight: 700;
    }

    .m-azul {
        color: var(--azul-2);
    }

    .m-verde {
        color: #1F9D55;
    }

    .m-ambar {
        color: var(--ambar);
    }

    .m-rojo {
        color: var(--rojo);
    }

    .ventana .rango {
        display: inline-block;
        padding: 12px 38px;
        border-radius: 999px;
        background: var(--chip);
        font-size: .9rem;
        font-weight: 700;
    }

    .ventana h4 {
        margin: 14px 0 4px;
        font-size: 1.15rem;
    }

    .ventana p {
        margin: 0 0 14px;
        font-size: .85rem;
        color: var(--gris);
    }

    .progreso {
        height: 12px;
        border-radius: 999px;
        background: #EEF1F7;
        overflow: hidden;
    }

    .progreso i {
        display: block;
        height: 100%;
        border-radius: 999px;
        background: #27A95F;
    }

    .progreso.lleno i {
        background: var(--ambar);
    }

    .subtitulo {
        margin: 28px 0 12px;
        font-size: 1.15rem;
        font-weight: 700;
    }

    .atendidos {
        background: #fff;
        border: 1px solid var(--borde);
        border-radius: 14px;
        padding: 8px 24px;
    }

    .atendido {
        display: grid;
        grid-template-columns: minmax(0, 2fr) 1fr auto 60px;
        gap: 16px;
        align-items: center;
        padding: 14px 0;
    }

    .atendido+.atendido {
        border-top: 1px solid #F0F3F9;
    }

    .atendido b {
        font-size: .86rem;
    }

    .atendido .v {
        font-size: .82rem;
        color: var(--gris);
    }

    .atendido .h {
        text-align: right;
        font-size: .82rem;
        font-weight: 600;
    }

    .vacio {
        padding: 26px 0;
        text-align: center;
        font-size: .86rem;
        color: var(--gris);
    }

    /* escáner de cámara */
    .escaner {
        position: fixed;
        inset: 0;
        z-index: 60;
        display: flex;
        flex-direction: column;
        background: #0B1B38;
        color: #fff;
    }

    .escaner[hidden] {
        display: none;
    }

    .escaner header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px 18px;
    }

    .escaner header b {
        font-size: 1rem;
    }

    .escaner .cerrar {
        background: rgba(255, 255, 255, .14);
        border: 0;
        color: #fff;
        border-radius: 8px;
        padding: 9px 16px;
        font-weight: 700;
    }

    .escaner .zona {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 0 16px 24px;
        gap: 16px;
    }

    #lector {
        width: min(92vw, 460px);
        border-radius: 16px;
        overflow: hidden;
        background: #000;
    }

    .escaner p {
        margin: 0;
        max-width: 40ch;
        text-align: center;
        font-size: .85rem;
        color: var(--claro);
        line-height: 1.5;
    }

    .escaner p.err {
        color: #FFB4B4;
        font-weight: 600;
    }

    @media (max-width: 1024px) {
        .dos {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 720px) {
        .buscar {
            grid-template-columns: 1fr 1fr;
        }

        .buscar input[type=text] {
            grid-column: 1 / -1;
        }

        .buscar .operativo {
            grid-column: 1 / -1;
        }

        .atendido {
            grid-template-columns: 1fr auto;
            row-gap: 4px;
        }

        .atendido .v {
            grid-column: 1;
        }

        .atendido .h {
            grid-row: 1;
            grid-column: 2;
        }
    }
</style>
@endpush

@section('contenido')
@if (session('ok'))
<div class="alerta ok" role="status">✓ {{ session('ok') }}</div>
@endif
@error('codigo')
<div class="alerta error" role="alert">{{ $message }}</div>
@enderror

<section class="tarjeta" aria-labelledby="t-buscar">
    <header>
        <h3 id="t-buscar" style="font-size:1.15rem">Buscar ticket</h3>
    </header>
    <form class="buscar" method="POST" action="{{ route('trabajador.buscar') }}" id="form-buscar" autocomplete="off">
        @csrf
        <input type="text" name="codigo" id="codigo" placeholder="Código del estudiante" maxlength="100"
            autocapitalize="none" spellcheck="false" inputmode="text" required @if(!request('escanear')) autofocus @endif aria-label="Código del estudiante">
        <button class="btn btn-primario" style="margin-top:0">Buscar</button>
        <button type="button" class="btn btn-azul-claro" id="abrir-escaner">Escanear QR</button>
        @if ($operativo)
        <span class="operativo">Operativo · {{ ucfirst($operativo) }}</span>
        @else
        <span class="operativo cerrado">Fuera de horario</span>
        @endif
    </form>
</section>

<div class="dos">
    <section class="tarjeta" aria-labelledby="t-hoy">
        <header>
            <h3 id="t-hoy" style="font-size:1.15rem">Atención de hoy</h3>
            <nav class="filtro-serv" aria-label="Servicio">
                <a href="{{ route('trabajador.inicio') }}" @class(['on'=> !$servicio])>Todos</a>
                @foreach (['desayuno', 'almuerzo', 'cena'] as $s)
                <a href="{{ route('trabajador.inicio', ['servicio' => $s]) }}" @class(['on'=> $servicio === $s])>{{ ucfirst($s) }}</a>
                @endforeach
            </nav>
        </header>
        <div class="metricas">
            @foreach ($metricas as $m)
            <div class="metrica"><span>{{ $m['etiqueta'] }}</span><b class="m-{{ $m['color'] }}">{{ $m['valor'] }}</b></div>
            @endforeach
        </div>
    </section>

    <section class="tarjeta ventana" aria-labelledby="t-vent">
        <header>
            <h3 id="t-vent" style="font-size:1.15rem">{{ $ventana && $ventana['en_curso'] ? 'Ventana actual' : 'Próxima ventana' }}</h3>
        </header>
        @if ($ventana)
        <span class="rango">{{ $ventana['inicio'] }} – {{ $ventana['fin'] }}</span>
        <h4>{{ ucfirst($ventana['servicio']) }}</h4>
        <p>{{ $ventana['asignados'] }} de {{ $ventana['capacidad'] }} tickets asignados</p>
        <div class="progreso {{ $ventana['porcentaje'] >= 100 ? 'lleno' : '' }}" role="progressbar" aria-valuenow="{{ $ventana['porcentaje'] }}" aria-valuemin="0" aria-valuemax="100">
            <i @style(['width' => min(100, $ventana['porcentaje']) . '%'])></i>
        </div>
        @else
        <p class="nota">No quedan ventanas de atención para hoy.</p>
        @endif
    </section>
</div>

<h2 class="subtitulo">Últimos tickets atendidos</h2>
<div class="atendidos">
    @forelse ($atendidos as $a)
    <div class="atendido">
        <b>{{ $a['codigo'] }} · {{ $a['nombre'] }}</b>
        <span class="v">{{ $a['ventana'] ?? '—' }}</span>
        <span class="estado-chip e-recogido">{{ $u->esDePuerta() ? 'Ingresó' : 'Recogido' }}</span>
        <span class="h">{{ $a['hora'] }}</span>
    </div>
    @empty
    <div class="vacio">Aún no has atendido tickets hoy.</div>
    @endforelse
</div>

<div class="escaner" id="escaner" hidden role="dialog" aria-modal="true" aria-label="Escanear código QR">
    <header><b>{{ $u->esDePuerta() ? 'Registrar ingreso' : 'Registrar entrega' }}</b><button type="button" class="cerrar" id="cerrar-escaner">Cerrar</button></header>
    <div class="zona">
        <div id="lector"></div>
        <p id="msg-escaner">Apunta la cámara al QR del estudiante. Se leerá solo.</p>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/html5-qrcode.min.js') }}"></script>
<script>
    (function() {
        var form = document.getElementById('form-buscar'),
            campo = document.getElementById('codigo');
        var modal = document.getElementById('escaner'),
            msg = document.getElementById('msg-escaner');
        var lector = null,
            enviado = false;

        function cerrar() {
            modal.hidden = true;
            var l = lector;
            lector = null;
            return l ? l.stop().catch(function() {}).then(function() {
                try {
                    l.clear();
                } catch (e) {}
            }) : Promise.resolve();
        }

        function abrir() {
            enviado = false;
            modal.hidden = false;
            msg.className = '';
            msg.textContent = 'Apunta la cámara al QR del estudiante. Se leerá solo.';

            if (typeof Html5Qrcode === 'undefined') {
                msg.className = 'err';
                msg.textContent = 'No se cargó el lector de QR. Escribe el código en el cuadro de búsqueda.';
                return;
            }
            lector = new Html5Qrcode('lector');
            lector.start({
                    facingMode: 'environment'
                }, {
                    fps: 10,
                    qrbox: {
                        width: 240,
                        height: 240
                    }
                },
                function(texto) {
                    if (enviado) return;
                    enviado = true;
                    if (navigator.vibrate) navigator.vibrate(60);
                    cerrar().then(function() {
                        campo.value = texto;
                        form.submit();
                    });
                },
                function() {
                    /* sin QR en este cuadro: se sigue intentando */ }
            ).catch(function() {
                msg.className = 'err';
                msg.textContent = 'No se pudo abrir la cámara. Permite el acceso a la cámara y verifica que la página use HTTPS.';
            });
        }

        document.getElementById('abrir-escaner').addEventListener('click', abrir);
        document.getElementById('cerrar-escaner').addEventListener('click', cerrar);
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') cerrar();
        });
        if (new URLSearchParams(location.search).has('escanear')) abrir();

        // los contadores se actualizan solos si no estás escribiendo ni escaneando
        setTimeout(function() {
            if (!campo.value && modal.hidden) location.reload();
        }, 30000);
    })();
</script>
@endpush