@extends('admin.layout')

@section('titulo', 'Modo demostración')
@section('subtitulo', 'Simula la fecha y hora del sistema para mostrarlo fuera de los horarios de servicio')
@section('activo', 'demo')

@push('cabecera')@include('admin._assets')@endpush

@section('contenido')
    @include('admin._avisos')

    <div class="kpis">
        <article class="kpi"><div class="etq">Hora real del servidor</div><div class="valor" style="font-size:1.35rem; padding-top:8px">{{ $real->format('d/m/Y H:i') }}</div><div class="detalle">{{ $real->translatedFormat('l') }}</div></article>
        <article class="kpi {{ $activo ? 'k-naranja' : 'k-verde' }}"><div class="etq">Hora que usa el sistema</div><div class="valor" style="font-size:1.35rem; padding-top:8px">{{ $simulada->format('d/m/Y H:i') }}</div><div class="detalle">{{ $activo ? 'Simulada (sigue avanzando)' : 'Real' }}</div></article>
    </div>

    <div class="aviso info">
        Solo para pruebas y demostraciones. Con el reloj simulado, los tickets que se creen quedan con esa fecha y hora.
        Recuerda que el estudiante debe tener clases ese día en su horario y que el comedor atiende de lunes a sábado.
    </div>

    <div class="dos">
        <section class="tarjeta">
            <h2>Elegir fecha y hora</h2>
            <p class="sub">Atajos para hoy (según la fecha que usa el sistema):</p>
            <div class="acciones" style="flex-wrap:wrap">
                @foreach ($presets as $p)
                    <form method="POST" action="{{ route('admin.demo.fijar') }}" data-una-vez>
                        @csrf <input type="hidden" name="fecha_hora" value="{{ $p['valor'] }}">
                        <button type="submit" class="btn btn-suave">{{ $p['nombre'] }} · {{ $p['hora'] }}</button>
                    </form>
                @endforeach
            </div>

            <form method="POST" action="{{ route('admin.demo.fijar') }}" class="filtros" style="margin-top:16px" data-una-vez>
                @csrf
                <div class="campo"><label for="fecha_hora">Fecha y hora personalizadas</label>
                    <input id="fecha_hora" type="datetime-local" name="fecha_hora" value="{{ old('fecha_hora', $simulada->format('Y-m-d\TH:i')) }}" required></div>
                <button class="btn btn-primario" type="submit">Aplicar</button>
            </form>
        </section>

        <section class="tarjeta">
            <h2>Avanzar el reloj</h2>
            <p class="sub">Para mostrar que un ticket vence o que se cierra el servicio.</p>
            <div class="acciones" style="flex-wrap:wrap">
                @foreach ([15 => '+15 min', 30 => '+30 min', 60 => '+1 hora', 180 => '+3 horas'] as $min => $t)
                    <form method="POST" action="{{ route('admin.demo.avanzar') }}" data-una-vez>
                        @csrf <input type="hidden" name="minutos" value="{{ $min }}">
                        <button type="submit" class="btn btn-suave">{{ $t }}</button>
                    </form>
                @endforeach
            </div>

            @if ($hayCierre)
                <form method="POST" action="{{ route('admin.demo.cerrar') }}" style="margin-top:16px" data-una-vez>
                    @csrf <button type="submit" class="btn btn-suave">Ejecutar cierre de tickets ahora</button>
                </form>
                <p class="nota" style="margin-top:8px">Registra como inasistencia los tickets que ya vencieron (<code>{{ $comando }}</code>).</p>
            @endif

            @if ($activo)
                <form method="POST" action="{{ route('admin.demo.desactivar') }}" style="margin-top:16px" data-una-vez>
                    @csrf <button type="submit" class="btn btn-primario">Volver a la hora real</button>
                </form>
            @endif
        </section>
    </div>
@endsection
