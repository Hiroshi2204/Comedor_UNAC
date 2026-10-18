@extends('admin.layout')

@section('titulo', 'Estudiantes')
@section('subtitulo', 'Búsqueda y estado de cada estudiante en el comedor')
@section('activo', 'estudiantes')

@push('cabecera')@include('admin._assets')@endpush

@section('contenido')
    @include('admin._avisos')

    <section class="tarjeta">
        <form method="GET" action="{{ route('admin.estudiantes') }}" class="filtros">
            <div class="campo" style="flex:1; min-width:220px"><label for="q">Búsqueda</label>
                <input id="q" type="search" name="q" value="{{ $q }}" placeholder="Código, DNI, apellidos o nombres" maxlength="60"></div>
            <div class="campo"><label for="filtro">Mostrar</label>
                <select id="filtro" name="filtro">
                    <option value="">Todos</option>
                    <option value="becados" @selected($filtro === 'becados')>Becados</option>
                    <option value="no_becados" @selected($filtro === 'no_becados')>No becados</option>
                    <option value="sancionados" @selected($filtro === 'sancionados')>Con sanción activa</option>
                </select></div>
            <button class="btn btn-primario" type="submit">Buscar</button>
        </form>
    </section>

    <section class="tarjeta">
        <div class="tabla-caja">
            <table class="t">
                <thead><tr><th>Código</th><th>Estudiante</th><th>Beca</th><th>Sanción</th><th class="num">Tickets</th><th class="num">Inasistencias</th><th></th></tr></thead>
                <tbody>
                @forelse ($estudiantes as $e)
                    <tr>
                        <td class="fuerte">{{ $e->codigo_universitario }}</td>
                        <td>{{ \App\Support\Fmt::nombre($e->nombreCompleto()) }}<div class="tenue sm">{{ $e->escuela_profesional }}</div></td>
                        <td><span class="chip {{ $e->es_becado ? 'e-becado' : 'e-no_becado' }}">{{ $e->es_becado ? 'Becado' : 'No becado' }}</span></td>
                        <td>@if ($e->sancion_activa)<span class="chip e-activa">Sancionado</span>@else<span class="tenue">—</span>@endif</td>
                        <td class="num">{{ number_format($e->n_tickets) }}</td>
                        <td class="num">{{ number_format($e->n_inasistencias) }}</td>
                        <td><a class="enlace" href="{{ route('admin.estudiantes.show', $e) }}">Ver</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="vacio">No se encontraron estudiantes.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $estudiantes->links('admin._paginacion') }}
    </section>
@endsection
