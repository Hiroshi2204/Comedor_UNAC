@extends('admin.layout')

@section('titulo', 'Dashboard')
@section('subtitulo', 'Visión general del servicio y operación diaria')
@section('activo', 'dashboard')

@push('cabecera')
<meta http-equiv="refresh" content="60">
@endpush

@section('contenido')
<div class="kpis">
    @foreach ($d['kpis'] as $k)
    <article class="kpi k-{{ $k['color'] }}">
        <div class="etq">{{ $k['titulo'] }}</div>
        <div class="valor">{{ $k['valor'] }}</div>
        <div class="detalle">{{ $k['detalle'] }}</div>
    </article>
    @endforeach
</div>

<div class="dos">
    <section class="tarjeta">
        <h2>Estado de tickets — hoy</h2>
        <div class="barras">
            @foreach ($d['barras'] as $b)
            <div class="fila-barra">
                <span class="nom">{{ $b['nombre'] }}</span>
                <div class="pista" role="img" aria-label="{{ $b['nombre'] }}: {{ $b['valor'] }}">
                    @if ($b['valor'] > 0)
                    <div class="relleno c-{{ $b['color'] }}" @style(['width: ' . $b['ancho'] . '%'])></div>
                    @endif
                </div>
                <span class="num">{{ $b['valor'] }}</span>
            </div>
            @endforeach
        </div>
    </section>

    <section class="tarjeta">
        <h2>Próximas ventanas de atención</h2>
        @forelse ($d['ventanas'] as $v)
        <div class="ventana">
            <span class="rango">{{ $v['rango'] }}</span>
            <span class="grupo">{{ $v['servicio'] }}<small>{{ $v['ocupacion'] }} tickets</small></span>
            <span class="punto c-{{ $v['color'] }}" aria-hidden="true"></span>
        </div>
        @empty
        <p class="vacio">No hay más ventanas de atención hoy.</p>
        @endforelse
    </section>
</div>

<section class="tarjeta">
    <h2>Actividad reciente</h2>
    @forelse ($d['actividad'] as $a)
    <div class="evento">
        <span class="punto c-{{ $a['color'] }}" aria-hidden="true"></span>
        <div class="texto">
            <strong>{{ $a['titulo'] }}</strong>
            <small>{{ $a['detalle'] }}</small>
        </div>
        <span class="hora">{{ $a['hora'] }}</span>
    </div>
    @empty
    <p class="vacio">Aún no hay actividad hoy.</p>
    @endforelse
</section>

<p class="actualizado">Actualizado a las {{ $d['actualizado'] }} · la página se refresca cada minuto</p>
@endsection