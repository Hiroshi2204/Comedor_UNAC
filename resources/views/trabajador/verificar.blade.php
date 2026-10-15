@extends('trabajador.layouts.app')

@section('titulo', 'Verificar ticket')
@section('subtitulo', 'Confirma identidad, vigencia y ventana antes de ' . ($u->esDePuerta() ? 'dejar pasar' : 'entregar'))
@section('pagina', 'verificar')

@push('head')
<style>
    .ficha-cab {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 20px;
    }

    .ficha-cab h2 {
        margin: 0;
        font-size: 1.5rem;
    }

    .persona {
        display: flex;
        align-items: center;
        gap: 24px;
        padding-bottom: 22px;
        border-bottom: 1px solid var(--borde);
    }

    .iniciales {
        width: 84px;
        height: 84px;
        border-radius: 50%;
        flex: none;
        display: grid;
        place-items: center;
        background: var(--chip);
        color: var(--azul);
        font-size: 1.6rem;
        font-weight: 700;
    }

    .persona h3 {
        margin: 0 0 8px;
        font-size: 1.5rem;
    }

    .persona p {
        margin: 3px 0;
        font-size: .85rem;
        color: var(--gris);
    }

    .cuerpo {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 32px;
        margin-top: 24px;
        align-items: start;
    }

    .ficha-datos {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px 32px;
    }

    .ficha-datos dt {
        font-size: .7rem;
        font-weight: 600;
        color: var(--gris);
    }

    .ficha-datos dd {
        margin: 6px 0 0;
        font-size: 1.1rem;
        font-weight: 700;
    }

    .verifs {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 26px;
    }

    .verif {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 11px 22px;
        border-radius: 999px;
        font-size: .8rem;
        font-weight: 700;
        background: var(--verde-bg);
        color: #1F9D55;
    }

    .verif.mal {
        background: var(--rojo-bg);
        color: var(--rojo);
    }

    .qr-lado {
        padding: 16px 20px 20px;
        border: 1px solid var(--borde);
        border-radius: 14px;
        background: var(--campo);
        text-align: center;
    }

    .qr-lado small {
        display: block;
        margin-bottom: 10px;
        font-size: .9rem;
        font-weight: 700;
        color: var(--gris);
    }

    .qr-lado .marco {
        display: inline-block;
        padding: 10px;
        border: 1px solid var(--borde);
        border-radius: 10px;
        background: #fff;
        line-height: 0;
    }

    .qr-lado svg {
        width: 140px;
        height: 140px;
    }

    .confirmar {
        margin-top: 28px;
    }

    .confirmar h4 {
        margin: 0 0 14px;
        font-size: 1rem;
    }

    .acciones {
        display: flex;
        flex-wrap: wrap;
        gap: 14px;
    }

    .acciones .btn {
        width: auto;
        height: 50px;
        padding: 0 34px;
        margin: 0;
    }

    .btn-verde {
        background: #27A95F;
        color: #fff;
    }

    .btn-verde:hover {
        filter: brightness(.95);
    }

    .btn-gris {
        background: #EEF1F7;
        color: var(--tinta);
    }

    .btn-gris:hover {
        background: #E3E8F1;
    }

    .rechazo {
        display: flex;
        gap: 12px;
        padding: 14px 16px;
        margin-top: 24px;
        border-radius: 10px;
        background: var(--rojo-bg);
        border: 1px solid #F4B4B4;
        color: var(--rojo);
        font-size: .9rem;
        font-weight: 600;
        line-height: 1.4;
    }

    @media (max-width: 860px) {
        .cuerpo {
            grid-template-columns: 1fr;
        }

        .qr-lado {
            justify-self: start;
        }

        .persona {
            gap: 16px;
        }

        .iniciales {
            width: 64px;
            height: 64px;
            font-size: 1.2rem;
        }

        .persona h3,
        .ficha-cab h2 {
            font-size: 1.2rem;
        }
    }

    @media (max-width: 520px) {
        .ficha-datos {
            grid-template-columns: 1fr;
            gap: 16px;
        }

        .acciones .btn {
            width: 100%;
        }

        .qr-lado {
            justify-self: stretch;
        }
    }
</style>
@endpush

@section('contenido')
@php
$f = $ficha;
$partes = preg_split('/\s+/', trim($f['estudiante']));
$iniciales = mb_strtoupper(mb_substr($partes[0] ?? '', 0, 1) . mb_substr($partes[1] ?? '', 0, 1));
$estados = ['generado' => 'Generado', 'validado' => 'Ingresó', 'recogido' => 'Entregado', 'vencido' => 'Vencido', 'no_recogido' => 'No recogido', 'cancelado' => 'Cancelado'];
$f_fecha = $ticket->fecha->format('d/m/Y');
$origen = $ticket->created_at && $ticket->created_at->toDateString() < $ticket->fecha->toDateString() ? 'Reserva anticipada' : 'Al momento';
    $puedeConfirmar = !$rechazo;
    @endphp

    @error('ticket')
    <div class="alerta error" role="alert">{{ $message }}</div>
    @enderror

    <section class="tarjeta">
        <div class="ficha-cab">
            <h2>Ticket encontrado</h2>
            <span class="estado-chip e-{{ $f['estado'] }}">{{ $estados[$f['estado']] ?? $f['estado'] }}</span>
        </div>

        <div class="persona">
            <div class="iniciales" aria-hidden="true">{{ $iniciales }}</div>
            <div>
                <h3>{{ \Illuminate\Support\Str::title(mb_strtolower($f['estudiante'])) }}</h3>
                <p>Código: {{ $f['codigo'] }}</p>
                <p>Tipo: {{ $f['tipo'] === 'becado' ? 'Becado' : 'No becado' }} · {{ $f['escuela'] }}</p>
            </div>
        </div>

        <div class="cuerpo">
            <div>
                <dl class="ficha-datos">
                    <div>
                        <dt>Fecha</dt>
                        <dd>{{ $f_fecha }}</dd>
                    </div>
                    <div>
                        <dt>Ventana</dt>
                        <dd>{{ $f['franja'] ? str_replace('-', ' – ', $f['franja']) : '—' }}</dd>
                    </div>
                    <div>
                        <dt>Código del ticket</dt>
                        <dd>{{ $ticket->codigo }}</dd>
                    </div>
                    <div>
                        <dt>Servicio · origen</dt>
                        <dd>{{ ucfirst($f['servicio']) }} · {{ $origen }}</dd>
                    </div>
                </dl>

                <div class="verifs" aria-label="Verificaciones">
                    @if ($matriculado)<span class="verif">✓ Matrícula verificada</span>@else<span class="verif mal">✕ Sin matrícula vigente</span>@endif
                    @if ($rechazo && in_array($rechazo[0], ['muy_temprano', 'vencido', 'fuera_de_fecha'], true))
                    <span class="verif mal">✕ Fuera de ventana</span>
                    @elseif (!$rechazo)
                    <span class="verif">✓ Ventana válida</span>
                    @endif
                    @if ($u->esDeCocina())
                    @if ($f['estado'] === 'validado')<span class="verif">✓ Ingreso validado</span>@else<span class="verif mal">✕ Sin ingreso validado</span>@endif
                    @endif
                </div>
            </div>

            <figure class="qr-lado" style="margin:0">
                <small>QR</small>
                <div class="marco">{!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(140)->margin(0)->generate($ticket->codigo_qr) !!}</div>
            </figure>
        </div>

        @if ($rechazo)
        <div class="rechazo" role="alert"><span aria-hidden="true">⚠</span><span>{{ $rechazo[1] }}</span></div>
        @endif

        <div class="confirmar">
            <h4>{{ $puedeConfirmar ? ($u->esDePuerta() ? '¿Confirmar ingreso del estudiante?' : '¿Confirmar entrega del ticket?') : 'No se puede registrar este ticket' }}</h4>
            <div class="acciones">
                @if ($puedeConfirmar)
                <form method="POST" action="{{ route('trabajador.confirmar', $ticket) }}" id="form-confirmar">
                    @csrf
                    <button class="btn btn-verde" id="btn-confirmar">{{ $u->esDePuerta() ? 'Confirmar ingreso' : 'Confirmar recojo' }}</button>
                </form>
                @endif
                <a class="btn btn-gris" href="{{ route('trabajador.inicio') }}">{{ $puedeConfirmar ? 'Cancelar' : 'Volver' }}</a>
            </div>
        </div>
    </section>
    @endsection

    @push('scripts')
    <script>
        (function() {
            var f = document.getElementById('form-confirmar'),
                b = document.getElementById('btn-confirmar');
            if (f) f.addEventListener('submit', function() {
                b.disabled = true;
                b.textContent = 'Registrando…';
            }); // evita el doble toque
        })();
    </script>
    @endpush