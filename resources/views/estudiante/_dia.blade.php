{{-- Estilos propios de este bloque (selector de franjas). El resto usa las clases de estudiante.layouts.app --}}
@once
@push('head')
<style>
    .fr-pila {
        display: flex;
        flex-direction: column;
        gap: 16px;
        margin-bottom: 26px;
    }

    .fr-sub {
        display: block;
        margin-top: 3px;
        font-size: .72rem;
        font-weight: 500;
        color: var(--gris);
    }

    .fr-ayuda {
        margin: 0 0 14px;
    }

    .fr-grupo {
        border: 0;
        margin: 4px 0 0;
        padding: 0;
        min-width: 0;
    }

    .fr-leyenda {
        padding: 0;
        margin: 0 0 10px;
        font-size: .74rem;
        font-weight: 700;
        color: var(--gris);
    }

    .fr-rejilla {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 8px;
    }

    .fr-opcion {
        position: relative;
        display: block;
    }

    .fr-opcion input {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        margin: 0;
        opacity: 0;
        cursor: pointer;
    }

    .fr-caja {
        display: block;
        padding: 10px 12px;
        border: 1px solid var(--borde);
        border-radius: 10px;
        background: var(--campo);
        transition: border-color .12s, background .12s, box-shadow .12s;
    }

    .fr-caja b {
        display: block;
        font-size: .88rem;
        font-weight: 700;
        color: var(--tinta);
    }

    .fr-caja small {
        display: block;
        margin-top: 2px;
        font-size: .7rem;
        color: var(--gris);
    }

    .fr-caja.poco small {
        color: #B45F00;
        font-weight: 600;
    }

    .fr-opcion:hover .fr-caja {
        border-color: #B9CBE6;
    }

    .fr-opcion input:checked+.fr-caja {
        border-color: var(--azul-2);
        background: #EAF2FC;
        box-shadow: 0 0 0 1px var(--azul-2);
    }

    .fr-opcion input:focus-visible+.fr-caja {
        outline: 2px solid var(--azul-2);
        outline-offset: 2px;
    }

    .fr-opcion input:disabled {
        cursor: not-allowed;
    }

    .fr-opcion input:disabled+.fr-caja {
        opacity: .45;
        background: #EEF1F7;
    }

    .fr-aviso {
        margin-top: 16px;
    }

    @media (max-width: 520px) {
        .fr-rejilla {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>
@endpush
@endonce

@php
$etiquetasServicio = [
'disponible' => 'Disponible',
'aun_no_abre' => 'Aún no abre',
'cerrado' => 'Cerrado',
'en_clase' => 'En clase',
'agotado' => 'Sin cupos',
'cancelado' => 'Cancelado',
];
$etiquetasTicket = [
'validado' => 'Validado',
'recogido' => 'Recogido',
'vencido' => 'Vencido',
'no_recogido' => 'No recogido',
'cancelado' => 'Cancelado',
];
$hayFranjas = collect($d['servicios'] ?? [])->contains(fn ($x) => $x['estado'] === 'reservable');
@endphp

<h2 class="titulo-dia">{{ $titulo }} <span>{{ $fecha->format('d/m/Y') }}</span></h2>

@if (!$d['elegible'])
<p class="aviso">{{ $d['motivo'] }}</p>
@else
@if ($hayFranjas)
@once
<p class="nota fr-ayuda">
    Elige la <b>franja</b> en la que irás al comedor. Si no llegas a tiempo, te pasamos a la
    <b>última franja</b> del servicio como última oportunidad; si tampoco llegas, se registra una inasistencia.
</p>
@endonce
@endif

<div class="fr-pila">
    @foreach ($d['servicios'] as $servicio => $s)
    @php
    $t = $s['ticket'];
    $franja = $t?->ventanaAtencion;
    $atencion = config("comedor.servicios.{$servicio}");
    $codigoTk = $t ? 'TK-' . $t->fecha->format('Y') . '-' . str_pad((string) $t->id, 5, '0', STR_PAD_LEFT) : null;

    if ($t) {
    $claveChip = $t->estado;
    $textoChip = $t->estado === 'generado'
    ? ($d['modo'] === 'reserva' ? 'Reservado' : 'Generado')
    : ($etiquetasTicket[$t->estado] ?? $t->estado);
    } else {
    $claveChip = $s['estado'];
    $textoChip = $s['estado'] === 'reservable'
    ? ($d['modo'] === 'ahora' ? 'Puedes pedir' : 'Puedes reservar')
    : ($etiquetasServicio[$s['estado']] ?? $s['estado']);
    }

    $primeraLibre = collect($s['franjas'])->first(fn ($f) => $f['cupos'] > 0)['ventana_id'] ?? null;
    @endphp

    <article class="tarjeta" aria-label="{{ ucfirst($servicio) }}">
        <header>
            <div>
                <h3>{{ ucfirst($servicio) }}</h3>
                @if ($atencion)
                <span class="fr-sub">Atención de {{ $atencion['inicio'] }} a {{ $atencion['fin'] }}</span>
                @endif
            </div>
            <span class="estado-chip e-{{ $claveChip }}">{{ $textoChip }}</span>
        </header>

        @if ($t && in_array($t->estado, ['generado', 'validado'], true))
        <div class="ticket">
            <dl>
                @if ($franja)
                <div>
                    <dt>{{ $t->estado === 'validado' ? 'Tu franja' : 'Ventana de recojo' }}</dt>
                    <dd class="grande">{{ substr($franja->hora_inicio, 0, 5) }} – {{ substr($franja->hora_fin, 0, 5) }}</dd>
                </div>
                @endif
                <div>
                    <dt>Vale hasta</dt>
                    <dd class="medio">{{ $t->vence_at?->format('H:i') }}</dd>
                </div>
                <!-- <div>
                    <dt>Código</dt>
                    <dd class="medio">{{ $codigoTk }}</dd>
                </div> -->
            </dl>

            @if ($t->estado === 'validado' || $d['modo'] === 'ahora')
            <figure class="qr-caja">
                <div class="marco">{!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(168)->margin(0)->color(18, 53, 107)->generate($t->codigo_qr) !!}</div>
                <figcaption>Presenta este QR</figcaption>
            </figure>
            @endif
        </div>

        @if ($t->estado === 'validado')
        <p class="nota" style="margin-top:16px">Ya pasaste por la puerta. Muestra este código en cocina para recibir tu comida.</p>
        @else
        @if ($t->ultima_oportunidad_at)
        <p class="aviso fr-aviso">
            No llegaste a tu franja y te pasamos a la <b>última</b>. Es tu <b>última oportunidad</b>:
            si no llegas, se registra una inasistencia.
        </p>
        @elseif ($d['modo'] === 'ahora')
        <p class="nota" style="margin-top:16px">Preséntalo en la puerta dentro de tu franja.</p>
        @endif

        <form method="POST" action="{{ route('estudiante.tickets.cancelar', $t) }}"
            onsubmit="return confirm('¿Cancelar este ticket? No podrás pedir otro de este servicio ese día.')">
            @csrf
            <button type="submit" class="btn btn-cancelar">Cancelar ticket</button>
        </form>
        @endif

        @elseif ($t && $t->estado === 'recogido')
        <p class="nota">Comida entregada{{ $t->fecha_hora_recojo ? ' a las ' . $t->fecha_hora_recojo->format('H:i') : '' }}. ¡Buen provecho!</p>
        @elseif ($t && $t->estado === 'vencido')
        <p class="nota">No llegaste a tiempo, ni siquiera en tu última oportunidad. Se registró una inasistencia.</p>
        @elseif ($t && $t->estado === 'no_recogido')
        <p class="nota">Pasaste por la puerta pero no recogiste la comida a tiempo.</p>
        @elseif ($t && $t->estado === 'cancelado')
        <p class="nota">Cancelaste este ticket. No puedes pedir otro de este servicio ese día.</p>

        @elseif ($s['estado'] === 'reservable')
        <form method="POST" action="{{ route('estudiante.tickets.solicitar') }}">
            @csrf
            <input type="hidden" name="servicio" value="{{ $servicio }}">
            <input type="hidden" name="fecha" value="{{ $fecha->toDateString() }}">

            <fieldset class="fr-grupo">
                <legend class="fr-leyenda">Franja para ir al comedor</legend>
                <div class="fr-rejilla">
                    @foreach ($s['franjas'] as $f)
                    <label class="fr-opcion">
                        <input type="radio" name="ventana_id" value="{{ $f['ventana_id'] }}"
                            @checked($f['ventana_id']===$primeraLibre) @disabled($f['cupos'] <=0) required>
                        <span @class(['fr-caja', 'poco'=> $f['cupos'] > 0 && $f['cupos'] <= 5])>
                                <b>{{ $f['inicio'] }} – {{ $f['fin'] }}</b>
                                <small>
                                    @if ($f['cupos'] <= 0) Sin cupos
                                        @elseif ($f['cupos'] <=5) Quedan {{ $f['cupos'] }}
                                        @else {{ $f['cupos'] }} cupos
                                        @endif
                                        </small>
                        </span>
                    </label>
                    @endforeach
                </div>
            </fieldset>

            <button type="submit" class="btn btn-primario">{{ $d['modo'] === 'ahora' ? 'Pedir ticket' : 'Reservar franja' }}</button>
        </form>

        @elseif ($s['estado'] === 'disponible')
        <p class="nota">Cupos para ti: <b>{{ $s['cupos'] }}</b>. El ticket vale unos minutos: pídelo cuando vayas al comedor.</p>
        <form method="POST" action="{{ route('estudiante.tickets.solicitar') }}">
            @csrf
            <input type="hidden" name="servicio" value="{{ $servicio }}">
            <input type="hidden" name="fecha" value="{{ $fecha->toDateString() }}">
            <button type="submit" class="btn btn-primario">Generar ticket</button>
        </form>
        @elseif ($s['estado'] === 'aun_no_abre')
        <p class="nota">Podrás pedirlo desde las <b>{{ $s['abre'] }}</b>.</p>
        @elseif ($s['estado'] === 'cerrado')
        <p class="nota">{{ $d['tipo'] === 'becado' ? 'Ya no quedan franjas disponibles de este servicio.' : 'Ya no se generan tickets de este servicio hoy.' }}</p>
        @elseif ($s['estado'] === 'en_clase')
        <p class="nota">Estás en clase o por entrar a una. Pídelo cuando estés libre.</p>
        @elseif ($s['estado'] === 'agotado')
        <p class="nota">No quedan cupos{{ $d['tipo'] === 'no_becado' ? ' para no becados por ahora. Si sobran, se abren al final del servicio' : ' en las franjas de este servicio' }}.</p>
        @endif
    </article>
    @endforeach
</div>
@endif