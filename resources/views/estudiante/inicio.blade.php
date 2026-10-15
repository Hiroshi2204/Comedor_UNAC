@extends('estudiante.layouts.app')

@section('titulo', 'Mi comedor')
@section('subtitulo', 'Tu ticket, horario y estado del beneficio')

@push('head')
    @if ($refrescar)
        <meta http-equiv="refresh" content="30">
    @endif
@endpush

@section('contenido')
    @php
        $limite    = (int) config('comedor.inasistencias.limite', 3);
        $enLimite  = $inasistencias >= $limite;
        $nombreTit = \Illuminate\Support\Str::title(mb_strtolower($estudiante->nombreCompleto()));
    @endphp

    @if (session('ok'))
        <div class="alerta ok" role="status">{{ session('ok') }}</div>
    @endif
    @error('ticket')
        <div class="alerta error" role="alert">{{ $message }}</div>
    @enderror

    <section class="banner" aria-label="Resumen del estudiante">
        <div>
            <small>Hola, estudiante</small>
            <h2>{{ $nombreTit }}</h2>
            <div class="meta">{{ $estudiante->codigo_universitario }} · {{ $estudiante->escuela_profesional }}</div>
        </div>
        <div class="estado">
            @if ($esBecado)
                <span class="insignia">✓ Beneficio activo</span>
            @else
                <span class="insignia neutra">Estudiante regular</span>
            @endif
            @if ($periodo)
                <span class="periodo">Período {{ $periodo }}</span>
            @endif
        </div>
    </section>

    <div class="rejilla">
        <div class="col">
            <section id="tickets">
                @include('estudiante._dia', ['d' => $dHoy, 'fecha' => $hoy, 'titulo' => 'Hoy'])
            </section>
            <section id="manana">
                @include('estudiante._dia', ['d' => $dManana, 'fecha' => $manana, 'titulo' => 'Mañana'])
            </section>
        </div>

        <div class="col">
            <section class="tarjeta" id="horario" aria-labelledby="t-horario">
                <header><h3 id="t-horario">Tu horario de hoy</h3></header>
                @forelse ($clases as $c)
                    <div class="clase">
                        <b>{{ $c['inicio'] }} – {{ $c['fin'] }}</b>
                        <span>{{ $c['curso'] }}@if ($c['aula']) · {{ $c['aula'] }}@endif</span>
                    </div>
                @empty
                    <p class="nota">Hoy no tienes clases.</p>
                @endforelse
            </section>

            <section class="tarjeta asistencia" id="asistencia" aria-labelledby="t-asist">
                <header><h3 id="t-asist">Asistencia</h3></header>
                <small>Inasistencias acumuladas</small>
                <div class="cifra {{ $inasistencias === 0 ? 'cero' : ($enLimite ? 'limite' : '') }}">{{ min($inasistencias, $limite) }} de {{ $limite }}</div>
                <div class="barra {{ $enLimite ? 'limite' : '' }}" aria-hidden="true">
                    @for ($i = 1; $i <= $limite; $i++)<i class="{{ $i <= $inasistencias ? 'on' : '' }}"></i>@endfor
                </div>
                @if ($sancionActiva)
                    <div class="sit mal">Sanción activa · comunícate con el administrador</div>
                @else
                    <div class="sit">Sin sanción activa</div>
                @endif
                <small style="margin-top:10px">Una inasistencia es pedir un ticket y no usarlo.</small>
            </section>
        </div>
    </div>
@endsection