@php
$etiquetas = [
'disponible' => 'Disponible', 'reservable' => 'Puedes reservar', 'aun_no_abre' => 'Aún no abre',
'cerrado' => 'Cerrado', 'en_clase' => 'En clase', 'agotado' => 'Sin cupos', 'con_ticket' => 'Con ticket',
'cancelado' => 'Cancelado',
];
$estadosTicket = [
'generado' => 'Generado', 'validado' => 'Validado', 'recogido' => 'Entregado',
'vencido' => 'Vencido', 'no_recogido' => 'No recogido', 'cancelado' => 'Cancelado',
];
$fechaTxt = mb_strtoupper($fecha->copy()->locale('es')->translatedFormat('d M Y'));
@endphp

<h2 class="titulo-dia">{{ $titulo === 'Hoy' ? 'Ticket de hoy' : 'Reserva de mañana' }} <span>{{ $fechaTxt }}</span></h2>

@if (!$d['elegible'])
<p class="aviso">{{ $d['motivo'] }}</p>
@else
<div class="col">
    @foreach ($d['servicios'] as $servicio => $s)
    @php
    $t = $s['ticket'];
    $chipClase = $t ? $t->estado : $s['estado'];
    $chipTexto = $t ? ($estadosTicket[$t->estado] ?? $t->estado) : ($etiquetas[$s['estado']] ?? $s['estado']);
    $v = $t?->ventanaAtencion;
    @endphp
    <article class="tarjeta">
        <header>
            <h3>{{ ucfirst($servicio) }}</h3>
            <span class="estado-chip e-{{ $chipClase }}">{{ $chipTexto }}</span>
        </header>

        @if ($t && in_array($t->estado, ['generado', 'validado'], true))
        <div class="ticket">
            <dl>
                <div>
                    <dt>{{ $v ? 'Ventana de recojo' : 'Válido hasta' }}</dt>
                    <dd class="grande">
                        @if ($v){{ substr($v->hora_inicio, 0, 5) }} – {{ substr($v->hora_fin, 0, 5) }}@else{{ $t->vence_at?->format('H:i') }}@endif
                    </dd>
                </div>
                <!-- <div>
                    <dt>Código</dt>
                    <dd class="medio">{{ $t->codigo ?: \Illuminate\Support\Str::limit($t->codigo_qr, 22, '…') }}</dd>
                </div> -->
            </dl>
            <figure class="qr-caja">
                <div class="marco">{!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(168)->margin(0)->generate($t->codigo_qr) !!}</div>
                <figcaption>Presenta este QR</figcaption>
            </figure>
        </div>

        @if ($t->estado === 'validado')
        <p class="nota" style="margin-top:16px"><b>Ya pasaste por la puerta.</b> Muestra este código en cocina para recibir tu comida.</p>
        @else
        <form method="POST" action="{{ route('estudiante.tickets.cancelar', $t) }}"
            onsubmit="return confirm('¿Cancelar este ticket? No podrás pedir otro de este servicio hoy.')">
            @csrf
            <button class="btn btn-cancelar">Cancelar ticket</button>
        </form>
        @endif

        @elseif ($t && $t->estado === 'recogido')
        <p class="nota">Comida entregada{{ $t->fecha_hora_recojo ? ' a las ' . $t->fecha_hora_recojo->format('H:i') : '' }}. ¡Buen provecho!</p>
        @elseif ($t && $t->estado === 'vencido')
        <p class="nota">El ticket venció sin usarse (se registró una inasistencia).</p>
        @elseif ($t && $t->estado === 'no_recogido')
        <p class="nota">Pasaste por la puerta pero no recogiste la comida a tiempo.</p>
        @elseif ($t && $t->estado === 'cancelado')
        <p class="nota">Cancelaste este ticket. No puedes pedir otro de este servicio ese día.</p>

        @elseif ($s['estado'] === 'disponible')
        <p class="nota">Cupos para ti: <b>{{ $s['cupos'] }}</b>. El ticket vale unos minutos: pídelo cuando vayas al comedor.</p>
        <form method="POST" action="{{ route('estudiante.tickets.solicitar') }}">
            @csrf
            <input type="hidden" name="servicio" value="{{ $servicio }}">
            <input type="hidden" name="fecha" value="{{ $fecha->toDateString() }}">
            <button class="btn btn-primario">Generar ticket</button>
        </form>
        @elseif ($s['estado'] === 'reservable')
        <form method="POST" action="{{ route('estudiante.tickets.solicitar') }}">
            @csrf
            <input type="hidden" name="servicio" value="{{ $servicio }}">
            <input type="hidden" name="fecha" value="{{ $fecha->toDateString() }}">
            <label class="etq" for="v-{{ $servicio }}-{{ $fecha->format('md') }}">Elige tu franja</label>
            <select name="ventana_id" id="v-{{ $servicio }}-{{ $fecha->format('md') }}" required>
                @foreach ($s['franjas'] as $f)
                <option value="{{ $f['ventana_id'] }}" @disabled($f['cupos'] <=0)>{{ $f['inicio'] }} – {{ $f['fin'] }} ({{ $f['cupos'] }} cupos)</option>
                @endforeach
            </select>
            <button class="btn btn-primario">Reservar</button>
        </form>
        @elseif ($s['estado'] === 'aun_no_abre')
        <p class="nota">Podrás pedirlo desde las <b>{{ $s['abre'] }}</b>.</p>
        @elseif ($s['estado'] === 'cerrado')
        <p class="nota">Ya no se generan tickets de este servicio hoy.</p>
        @elseif ($s['estado'] === 'en_clase')
        <p class="nota">Estás en clase o por entrar a una. Pídelo cuando estés libre.</p>
        @elseif ($s['estado'] === 'agotado')
        <p class="nota">No quedan cupos{{ $d['tipo'] === 'no_becado' ? ' para no becados por ahora. Si sobran, se abren al final del servicio' : '' }}.</p>
        @endif
    </article>
    @endforeach
</div>
@endif