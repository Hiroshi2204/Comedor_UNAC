@extends('admin.layout')

@section('titulo', 'Configuración')
@section('subtitulo', 'Parámetros operativos del servicio y períodos académicos')
@section('activo', 'configuracion')

@push('cabecera')@include('admin._assets')@endpush

@section('contenido')
    @include('admin._avisos')

    <div class="dos">
        <section class="tarjeta">
            <h2>Parámetros del sistema</h2>
            <p class="sub">Rigen para los tickets que se generen desde que guardas. Los valores en azul son personalizados; el resto usa config/comedor.php.</p>

            <form method="POST" action="{{ route('admin.configuracion.guardar') }}" data-una-vez>
                @csrf
                @foreach ($campos as $c)
                    <div class="param">
                        <div><strong>{{ $c->etiqueta }} @if ($c->personalizado)<span class="marca-pers">· personalizado</span>@endif</strong><small>{{ $c->ayuda }}</small></div>
                        <input type="number" name="ajuste[{{ $c->slug }}]" value="{{ old('ajuste.' . $c->slug, $c->valor) }}"
                               min="{{ $c->min }}" max="{{ $c->max }}" step="{{ $c->paso }}" aria-label="{{ $c->etiqueta }}">
                    </div>
                @endforeach
                <div class="acciones" style="justify-content:flex-end"><button type="submit" class="btn btn-primario">Guardar cambios</button></div>
            </form>

            <form method="POST" action="{{ route('admin.configuracion.restablecer') }}" data-una-vez style="margin-top:10px; text-align:right"
                  onsubmit="return confirm('¿Volver a los valores de config/comedor.php?')">
                @csrf <button type="submit" class="btn btn-suave btn-mini">Restablecer valores originales</button>
            </form>
        </section>

        <section class="tarjeta">
            <h2>Período activo</h2>
            <p class="sub">Se define con <code>SGA_PERIODO</code> en el .env y no se cambia desde aquí para no mezclar datos de períodos distintos.</p>
            <div class="estado-box"><span class="periodo">{{ $periodoEtiqueta }}</span></div>
            <p class="nota" style="margin-top:16px">Para abrir un período nuevo: crea su fila en <b>periodos_academicos</b>, cambia <b>SGA_PERIODO</b> y <b>SGA_CALENDARIO_ACADEMICO_ID</b>, ejecuta <code>php artisan config:clear</code> y vuelve a importar becados y horarios.</p>
        </section>
    </div>

    <section class="tarjeta">
        <h2>Períodos académicos</h2>
        <p class="sub">Los datos se aíslan por período para conservar el histórico.</p>
        <div class="tabla-caja"><table class="t">
            <thead><tr><th>Período</th><th>Inicio</th><th>Fin</th><th>Estado</th><th class="num">Estudiantes</th><th class="num">Tickets</th></tr></thead>
            <tbody>
            @forelse ($periodos as $p)
                <tr>
                    <td class="fuerte">{{ $p->etiqueta }}</td>
                    <td class="tenue">{{ $p->inicio ? \Carbon\Carbon::parse($p->inicio)->format('d/m/Y') : '—' }}</td>
                    <td class="tenue">{{ $p->fin ? \Carbon\Carbon::parse($p->fin)->format('d/m/Y') : '—' }}</td>
                    <td><span class="chip {{ $p->activo ? 'e-activo' : 'e-cerrado' }}">{{ $p->activo ? 'Activo' : 'Cerrado' }}</span></td>
                    <td class="num">{{ number_format($p->estudiantes) }}</td><td class="num">{{ number_format($p->tickets) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="vacio">No se pudo leer la tabla periodos_academicos.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </section>
@endsection
