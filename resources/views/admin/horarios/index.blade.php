@extends('admin.layout')

@section('titulo', 'Horarios')
@section('subtitulo', 'Sincronización de los horarios de clase con el SGA')
@section('activo', 'horarios')

@push('cabecera')@include('admin._assets')@endpush

@section('contenido')
    @include('admin._avisos')

    @php $sin = max(0, $matriculados - $conHorario); @endphp
    <div class="kpis">
        <article class="kpi"><div class="etq">Matriculados</div><div class="valor">{{ number_format($matriculados) }}</div><div class="detalle">Período {{ $periodo }}</div></article>
        <article class="kpi k-verde"><div class="etq">Con horario cargado</div><div class="valor">{{ number_format($conHorario) }}</div><div class="detalle">{{ number_format($filas) }} bloques de clase</div></article>
        <article class="kpi {{ $sin > 0 ? 'k-naranja' : 'k-verde' }}"><div class="etq">Sin horario</div><div class="valor">{{ number_format($sin) }}</div><div class="detalle">Sin clases registradas en el SGA</div></article>
        <article class="kpi"><div class="etq">Última sincronización</div><div class="valor" style="font-size:1.35rem; padding-top:8px">{{ $ultima ? $ultima->format('d/m H:i') : '—' }}</div><div class="detalle">{{ $ultima ? $ultima->diffForHumans() : 'Aún no hay horarios' }}</div></article>
    </div>

    <div class="dos">
        <section class="tarjeta">
            <h2>Sincronización con el SGA</h2>
            <p class="sub">Descarga la programación horaria y los matriculados de {{ $escuelas ?: 'las' }} escuela(s) configuradas y reconstruye el horario de cada estudiante.</p>
            <dl class="ficha">
                <div><dt>Período</dt><dd>{{ $periodo }}</dd></div>
                <div><dt>Escuelas configuradas</dt><dd>{{ $escuelas }}</dd></div>
                <div><dt>Automática</dt><dd>Todos los días 03:00</dd></div>
            </dl>

            <div class="acciones">
                @if ($conCola && $periodoId)
                    <form method="POST" action="{{ route('admin.horarios.sincronizar') }}" data-una-vez
                          onsubmit="return confirm('Se encolará una sincronización completa con el SGA. ¿Continuar?')">
                        @csrf <button type="submit" class="btn btn-primario">Sincronizar ahora</button>
                    </form>
                @endif
            </div>
            @unless ($conCola)
                <div class="aviso info" style="margin:16px 0 0">
                    La sincronización tarda varios minutos, por eso no se lanza desde el navegador mientras <b>QUEUE_CONNECTION=sync</b>.
                    Para forzarla, ejecuta en la consola: <code>php artisan sga:sincronizar-horarios {{ $periodoId }}</code>.
                    Si configuras <code>QUEUE_CONNECTION=database</code> y un worker, aquí aparecerá el botón «Sincronizar ahora».
                </div>
            @endunless
        </section>

        <section class="tarjeta">
            <h2>Matriculados sin horario</h2>
            <p class="sub">Primeros 15. Suelen ser alumnos sin carga académica registrada en el SGA.</p>
            <div class="tabla-caja"><table class="t">
                <tbody>
                @forelse ($sinHorario as $e)
                    <tr><td class="fuerte"><a class="enlace" href="{{ route('admin.estudiantes.show', $e) }}">{{ $e->codigo_universitario }}</a></td>
                        <td>{{ \App\Support\Fmt::nombre($e->nombreCompleto()) }}<div class="tenue sm">{{ $e->escuela_profesional }}</div></td></tr>
                @empty
                    <tr><td class="vacio">Todos los matriculados tienen horario.</td></tr>
                @endforelse
                </tbody>
            </table></div>
        </section>
    </div>
@endsection
