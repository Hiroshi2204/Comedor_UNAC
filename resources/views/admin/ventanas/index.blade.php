@extends('admin.layout')

@section('titulo', 'Ventanas de atención')
@section('subtitulo', 'Franjas de 15 minutos por servicio: capacidad y disponibilidad')
@section('activo', 'ventanas')

@push('cabecera')@include('admin._assets')@endpush

@section('contenido')
@include('admin._avisos')
<div class="aviso info">La suma de las capacidades activas de cada servicio debería igualar el stock de comida. Si la cambias, los tickets ya generados no se tocan.</div>

@foreach ($servicios as $s)
<section class="tarjeta">
    <div class="cab">
        <div>
            <h2>{{ $s->nombre }}</h2>
            <p class="nota" style="margin-top:4px">{{ $s->horario }} · stock {{ number_format($s->stock) }}</p>
        </div>
        <span class="chip {{ $s->cuadra ? 'e-recogido' : 'e-cancelado' }}">Capacidad {{ number_format($s->suma) }} / {{ number_format($s->stock) }}</span>
    </div>

    <form method="POST" action="{{ route('admin.ventanas.actualizar', $s->clave) }}" data-una-vez>
        @csrf
        <div class="tabla-caja">
            <table class="t">
                <thead>
                    <tr>
                        <th>Franja</th>
                        <th>Capacidad</th>
                        <th>Tickets hoy</th>
                        <th>Activa</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($s->ventanas as $v)
                    @php $o = (int) ($s->ocupadas[$v->id] ?? 0); $pct = $v->capacidad_maxima > 0 ? min(100, (int) round($o * 100 / $v->capacidad_maxima)) : 0; @endphp
                    <tr class="ventana-fila">
                        <td class="fuerte">{{ \App\Support\Fmt::franja($v) }}</td>
                        <td><input type="number" min="0" max="5000" name="capacidad[{{ $v->id }}]" value="{{ old('capacidad.' . $v->id, $v->capacidad_maxima) }}" aria-label="Capacidad {{ \App\Support\Fmt::franja($v) }}"></td>
                        <td class="ocupada"><span @class(['medidor', 'lleno'=> $pct >= 100])><i @style(['width: ' . $pct . ' %'])></i></span>{{ $o }}</td>
                        <td><label class="check"><input type="checkbox" name="activo[{{ $v->id }}]" value="1" @checked($v->activo)> Activa</label></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="vacio">No hay franjas. Ejecuta el seeder VentanaAtencionSeeder.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($s->ventanas->isNotEmpty())
        <div class="acciones"><button type="submit" class="btn btn-primario">Guardar {{ strtolower($s->nombre) }}</button></div>
        @endif
    </form>
</section>
@endforeach
@endsection