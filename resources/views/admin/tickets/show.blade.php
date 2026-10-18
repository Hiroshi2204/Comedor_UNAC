@php
    use App\Support\Fmt;
    $e = $ticket->estudiante;
    $ev = collect();
    $ev->push(['ts' => $ticket->created_at, 'cls' => '', 'tit' => 'Ticket generado',
        'det' => ucfirst($ticket->servicio) . ' · ' . Fmt::franja($ticket->ventanaAtencion) . ' · ' . ($ticket->tipo === 'becado' ? 'becado' : 'no becado')
            . ($ticket->ticketOrigen ? ' · ocupó el cupo liberado de ' . Fmt::ticket($ticket->ticketOrigen) : '')]);
    if ($ticket->validado_at) {
        $ev->push(['ts' => $ticket->validado_at, 'cls' => 'ok', 'tit' => 'Ingreso validado en puerta', 'det' => $ticket->validadoPor ? 'Por ' . Fmt::nombre($ticket->validadoPor->nombre) : null]);
    }
    if ($ticket->fecha_hora_recojo) {
        $ev->push(['ts' => $ticket->fecha_hora_recojo, 'cls' => 'ok', 'tit' => 'Comida entregada en cocina', 'det' => $ticket->trabajador ? 'Por ' . Fmt::nombre($ticket->trabajador->nombre) : null]);
    }
    if ($ticket->cancelacion) {
        $ev->push(['ts' => $ticket->cancelacion->created_at, 'cls' => 'aviso', 'tit' => 'Cancelado por el estudiante',
            'det' => $ticket->ticketReasignado ? 'El cupo pasó a ' . Fmt::ticket($ticket->ticketReasignado) : 'El cupo quedó libre']);
    }
    if ($ticket->inasistencia) {
        $ev->push(['ts' => $ticket->inasistencia->created_at, 'cls' => 'mal', 'tit' => 'Inasistencia registrada', 'det' => 'Cuenta para la sanción por ' . config('comedor.inasistencias.limite') . ' inasistencias']);
    } elseif (in_array($ticket->estado, ['vencido', 'no_recogido'], true)) {
        $ev->push(['ts' => $ticket->vence_at ?? $ticket->updated_at, 'cls' => 'mal',
            'tit' => $ticket->estado === 'vencido' ? 'Ticket vencido sin usar' : 'Pasó por la puerta pero no recogió', 'det' => null]);
    }
    $ev = $ev->filter(fn ($x) => $x['ts'])->sortBy('ts')->values();
@endphp
@extends('admin.layout')

@section('titulo', Fmt::ticket($ticket))
@section('subtitulo', 'Trazabilidad del ticket')
@section('activo', 'tickets')

@push('cabecera')@include('admin._assets')@endpush

@section('contenido')
    <a class="volver" href="{{ url()->previous() === url()->current() ? route('admin.tickets') : url()->previous() }}">← Volver a tickets</a>
    @include('admin._avisos')

    <div class="dos iguales">
        <section class="tarjeta">
            <div class="cab"><h2>Datos del ticket</h2>@include('admin._chip', ['e' => $ticket->estado])</div>
            <dl class="ficha">
                <div><dt>Estudiante</dt><dd>@if ($e)<a class="enlace" href="{{ route('admin.estudiantes.show', $e) }}">{{ $e->codigo_universitario }}</a> · {{ Fmt::nombre($e->nombreCompleto()) }}@else — @endif</dd></div>
                <div><dt>Escuela</dt><dd>{{ $e?->escuela_profesional ?? '—' }}</dd></div>
                <div><dt>Fecha</dt><dd>{{ $ticket->fecha->format('d/m/Y') }}</dd></div>
                <div><dt>Servicio</dt><dd>{{ ucfirst($ticket->servicio) }}</dd></div>
                <div><dt>Franja</dt><dd>{{ Fmt::franja($ticket->ventanaAtencion) }}</dd></div>
                <div><dt>Tipo</dt><dd>{{ $ticket->tipo === 'becado' ? 'Becado' : 'No becado' }}</dd></div>
                <div><dt>Vence</dt><dd>{{ $ticket->vence_at ? $ticket->vence_at->format('H:i') : '—' }}</dd></div>
            </dl>
        </section>

        <section class="tarjeta">
            <h2>Ciclo de vida</h2>
            <ul class="linea" style="margin-top:16px">
                @foreach ($ev as $x)
                    <li class="{{ $x['cls'] }}"><b>{{ $x['tit'] }}</b>
                        <small>{{ $x['ts']->format('d/m/Y H:i:s') }}@if ($x['det']) · {{ $x['det'] }}@endif</small></li>
                @endforeach
            </ul>
        </section>
    </div>

    <section class="tarjeta">
        <h2>Escaneos registrados</h2>
        <p class="sub">Cada intento en puerta o cocina, también los rechazados.</p>
        <div class="tabla-caja">
            <table class="t">
                <thead><tr><th>Hora</th><th>Puesto</th><th>Trabajador</th><th>Resultado</th></tr></thead>
                <tbody>
                @forelse ($escaneos as $s)
                    <tr>
                        <td>{{ $s->created_at->format('d/m/Y H:i:s') }}</td>
                        <td>{{ $s->puesto === 'puerta' ? 'Puerta (ingreso)' : 'Cocina (entrega)' }}</td>
                        <td>{{ isset($nombres[$s->trabajador_id]) ? Fmt::nombre($nombres[$s->trabajador_id]) : '—' }}</td>
                        <td><span class="chip {{ $s->exitoso ? 'e-recogido' : 'e-cancelado' }}">{{ $s->exitoso ? 'Correcto' : str_replace('_', ' ', $s->resultado) }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="vacio">Este ticket aún no fue escaneado.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
