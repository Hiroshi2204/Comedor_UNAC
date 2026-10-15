@php
$etiquetas = [
'disponible' => 'Disponible',
'reservable' => 'Puedes reservar',
'aun_no_abre' => 'Aún no abre',
'cerrado' => 'Cerrado',
'en_clase' => 'En clase',
'agotado' => 'Sin cupos',
'con_ticket' => 'Con ticket',
'cancelado' => 'Cancelado',
];
@endphp

<section class="dia">
    <h2>{{ $titulo }} <small>{{ $fecha->format('d/m/Y') }}</small></h2>

    @if (!$d['elegible'])
    <p class="aviso">{{ $d['motivo'] }}</p>
    @else
    @foreach ($d['servicios'] as $servicio => $s)
    @php $t = $s['ticket']; @endphp
    <article class="servicio">
        <header>
            <h3>{{ ucfirst($servicio) }}</h3>
            <span class="estado e-{{ $s['estado'] }}">{{ $etiquetas[$s['estado']] ?? $s['estado'] }}</span>
        </header>

        @if ($t && $t->estado === 'generado')
        <div class="qr">{!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(200)->margin(1)->generate($t->codigo_qr) !!}</div>
        <p class="muted">Preséntalo en la puerta. Vence a las {{ $t->vence_at?->format('H:i') }}.</p>
        <form method="POST" action="{{ route('estudiante.tickets.cancelar', $t) }}"
            onsubmit="return confirm('¿Cancelar este ticket? No podrás pedir otro de este servicio hoy.')">
            @csrf
            <button class="sec">Cancelar ticket</button>
        </form>
        @elseif ($t && $t->estado === 'validado')
        <div class="qr">{!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(200)->margin(1)->generate($t->codigo_qr) !!}</div>
        <p>Ya pasaste por la puerta. Muestra este código en cocina para recibir tu comida.</p>
        @elseif ($t && $t->estado === 'recogido')
        <p>Comida entregada{{ $t->fecha_hora_recojo ? ' a las ' . $t->fecha_hora_recojo->format('H:i') : '' }}. ¡Buen provecho!</p>
        @elseif ($t && $t->estado === 'vencido')
        <p>El ticket venció sin usarse (se registró una inasistencia).</p>
        @elseif ($t && $t->estado === 'no_recogido')
        <p>Pasaste por la puerta pero no recogiste la comida a tiempo.</p>
        @elseif ($t && $t->estado === 'cancelado')
        <p>Cancelaste este ticket. No puedes pedir otro de este servicio ese día.</p>

        @elseif ($s['estado'] === 'disponible')
        <p class="muted">Cupos para ti: {{ $s['cupos'] }}. El ticket vale unos minutos: pídelo cuando vayas al comedor.</p>
        <form method="POST" action="{{ route('estudiante.tickets.solicitar') }}">
            @csrf
            <input type="hidden" name="servicio" value="{{ $servicio }}">
            <input type="hidden" name="fecha" value="{{ $fecha->toDateString() }}">
            <button class="primario">Generar ticket</button>
        </form>
        @elseif ($s['estado'] === 'reservable')
        <form method="POST" action="{{ route('estudiante.tickets.solicitar') }}">
            @csrf
            <input type="hidden" name="servicio" value="{{ $servicio }}">
            <input type="hidden" name="fecha" value="{{ $fecha->toDateString() }}">
            <label class="muted" for="v-{{ $servicio }}-{{ $fecha->format('md') }}">Elige tu franja</label>
            <select name="ventana_id" id="v-{{ $servicio }}-{{ $fecha->format('md') }}" required>
                @foreach ($s['franjas'] as $f)
                <option value="{{ $f['ventana_id'] }}" @disabled($f['cupos'] <=0)>
                    {{ $f['inicio'] }} – {{ $f['fin'] }} ({{ $f['cupos'] }} cupos)
                </option>
                @endforeach
            </select>
            <button class="primario">Reservar</button>
        </form>
        @elseif ($s['estado'] === 'aun_no_abre')
        <p class="muted">Podrás pedirlo desde las {{ $s['abre'] }}.</p>
        @elseif ($s['estado'] === 'cerrado')
        <p class="muted">Ya no se generan tickets de este servicio hoy.</p>
        @elseif ($s['estado'] === 'en_clase')
        <p class="muted">Estás en clase o por entrar a una. Pídelo cuando estés libre.</p>
        @elseif ($s['estado'] === 'agotado')
        <p class="muted">No quedan cupos{{ $d['tipo'] === 'no_becado' ? ' para no becados por ahora. Si sobran, se abren al final del servicio' : '' }}.</p>
        @endif
    </article>
    @endforeach
    @endif
</section>