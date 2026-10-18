@extends('admin.layout')

@section('titulo', 'Sanciones')
@section('subtitulo', 'Seguimiento de inasistencias y suspensión del beneficio')
@section('activo', 'sanciones')

@push('cabecera')@include('admin._assets')@endpush

@section('contenido')
    @include('admin._avisos')

    <div class="kpis">
        <article class="kpi k-naranja"><div class="etq">Inasistencias hoy</div><div class="valor">{{ $inasistenciasHoy }}</div><div class="detalle">Tickets vencidos sin usar</div></article>
        <article class="kpi k-rojo"><div class="etq">Sanciones activas</div><div class="valor">{{ $activas }}</div><div class="detalle">Esperan que las levantes</div></article>
        <article class="kpi k-verde"><div class="etq">Beneficios levantados</div><div class="valor">{{ $levantadas }}</div><div class="detalle">Histórico</div></article>
        <article class="kpi"><div class="etq">Regla de sanción</div><div class="valor" style="color:var(--c-rojo); font-size:1.5rem">{{ $limite }} inasistencias</div><div class="detalle">→ suspensión hasta que el administrador la levante</div></article>
    </div>

    <section class="tarjeta">
        <form method="GET" action="{{ route('admin.sanciones') }}" class="filtros" style="margin-bottom:16px">
            <div class="campo" style="flex:1; min-width:200px"><label for="q">Estudiante</label>
                <input id="q" type="search" name="q" value="{{ $q }}" placeholder="Código, apellidos o nombres" maxlength="60"></div>
            <div class="campo"><label for="estado">Estado</label>
                <select id="estado" name="estado">
                    <option value="">Todas</option>
                    <option value="activa" @selected($estado === 'activa')>Activas</option>
                    <option value="levantada" @selected($estado === 'levantada')>Levantadas</option>
                </select></div>
            <button class="btn btn-primario" type="submit">Filtrar</button>
        </form>

        <div class="tabla-caja">
            <table class="t">
                <thead><tr><th>Estudiante</th><th class="num">Inasistencias</th><th>Estado</th><th>Inicio</th><th>Fin</th><th>Motivo</th><th>Acción</th></tr></thead>
                <tbody>
                @forelse ($sanciones as $s)
                    <tr>
                        <td>@if ($s->estudiante)
                            <a class="fuerte" href="{{ route('admin.estudiantes.show', $s->estudiante) }}">{{ $s->estudiante->codigo_universitario }}</a>
                            · {{ \App\Support\Fmt::nombre($s->estudiante->nombreCompleto()) }}@else — @endif</td>
                        <td class="num">{{ $s->numero_inasistencias_acumuladas }}</td>
                        <td>@include('admin._chip', ['e' => $s->estado])</td>
                        <td class="tenue">{{ $s->fecha_inicio->format('d/m/Y') }}</td>
                        <td class="tenue">{{ $s->fecha_fin?->format('d/m/Y') ?? '—' }}</td>
                        <td class="sm">{{ $conAuditoria && $s->nota_levantamiento ? $s->nota_levantamiento : $s->motivo }}</td>
                        <td>
                            @if ($s->estado === 'activa')
                                @include('admin._levantar', ['s' => $s])
                            @elseif ($s->estudiante)
                                <a class="enlace" href="{{ route('admin.estudiantes.show', $s->estudiante) }}">Ver</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="vacio">No hay sanciones con ese filtro.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $sanciones->links('admin._paginacion') }}
    </section>
@endsection
