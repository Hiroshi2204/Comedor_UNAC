@extends('admin.layout')

@section('titulo', 'Tickets')
@section('subtitulo', 'Consulta, filtros y trazabilidad del ciclo de vida')
@section('activo', 'tickets')

@push('cabecera')@include('admin._assets')@endpush

@section('contenido')
    @include('admin._avisos')
    @if ($aviso)<div class="aviso info">{{ $aviso }}</div>@endif

    <section class="tarjeta">
        <form method="GET" action="{{ route('admin.tickets') }}" class="filtros">
            <div class="campo"><label for="desde">Desde</label><input id="desde" type="date" name="desde" value="{{ $f['desde'] }}"></div>
            <div class="campo"><label for="hasta">Hasta</label><input id="hasta" type="date" name="hasta" value="{{ $f['hasta'] }}"></div>
            <div class="campo"><label for="estado">Estado</label>
                <select id="estado" name="estado">
                    <option value="">Todos</option>
                    @foreach (\App\Http\Controllers\Admin\TicketsAdminController::ESTADOS as $e)
                        <option value="{{ $e }}" @selected(($f['estado'] ?? '') === $e)>{{ ucfirst(str_replace('_', ' ', $e)) }}</option>
                    @endforeach
                </select></div>
            <div class="campo"><label for="tipo">Tipo</label>
                <select id="tipo" name="tipo">
                    <option value="">Todos</option>
                    <option value="becado" @selected(($f['tipo'] ?? '') === 'becado')>Becado</option>
                    <option value="no_becado" @selected(($f['tipo'] ?? '') === 'no_becado')>No becado</option>
                </select></div>
            <div class="campo"><label for="servicio">Servicio</label>
                <select id="servicio" name="servicio">
                    <option value="">Todos</option>
                    @foreach (['desayuno', 'almuerzo', 'cena'] as $s)
                        <option value="{{ $s }}" @selected(($f['servicio'] ?? '') === $s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select></div>
            <div class="campo" style="flex:1; min-width:200px"><label for="q">Búsqueda</label>
                <input id="q" type="search" name="q" value="{{ $f['q'] ?? '' }}" placeholder="Código, nombre o TK-2026-00128" maxlength="60"></div>
            <button class="btn btn-primario" type="submit">Filtrar</button>
        </form>
    </section>

    <section class="tarjeta">
        <div class="resumen">
            <a class="btn btn-suave btn-mini" style="margin-left:auto; order:2" href="{{ route('admin.tickets.exportar', request()->query()) }}">Exportar CSV</a>
            <span class="chip">Total <b>{{ number_format($conteo->sum()) }}</b></span>
            @foreach (\App\Http\Controllers\Admin\TicketsAdminController::ESTADOS as $e)
                @if (($conteo[$e] ?? 0) > 0)
                    <span class="chip e-{{ $e }}">{{ ucfirst(str_replace('_', ' ', $e)) }} <b>{{ number_format($conteo[$e]) }}</b></span>
                @endif
            @endforeach
        </div>

        <div class="tabla-caja">
            <table class="t">
                <thead><tr>
                    <th>Ticket</th><th>Estudiante</th><th>Tipo</th><th>Servicio · franja</th><th>Estado</th>
                    <th>Generado</th><th>Validado</th><th>Recojo</th><th></th>
                </tr></thead>
                <tbody>
                @forelse ($tickets as $t)
                    <tr>
                        <td class="fuerte">{{ \App\Support\Fmt::ticket($t) }}<div class="tenue sm">{{ $t->fecha->format('d/m/Y') }}</div></td>
                        <td>
                            @if ($t->estudiante)
                                <a class="fuerte" href="{{ route('admin.estudiantes.show', $t->estudiante) }}">{{ $t->estudiante->codigo_universitario }}</a>
                                · {{ \App\Support\Fmt::nombre($t->estudiante->nombreCompleto()) }}
                            @else — @endif
                        </td>
                        <td><span class="chip e-{{ $t->tipo }}">{{ $t->tipo === 'becado' ? 'Becado' : 'No becado' }}</span></td>
                        <td>{{ ucfirst($t->servicio) }}<div class="tenue sm">{{ \App\Support\Fmt::franja($t->ventanaAtencion) }}</div></td>
                        <td>@include('admin._chip', ['e' => $t->estado])</td>
                        <td class="tenue">{{ \App\Support\Fmt::hora($t->created_at) }}</td>
                        <td class="tenue">{{ \App\Support\Fmt::hora($t->validado_at) }}</td>
                        <td class="tenue">{{ \App\Support\Fmt::hora($t->fecha_hora_recojo) }}</td>
                        <td><a class="enlace" href="{{ route('admin.tickets.show', $t) }}">Ver</a></td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="vacio">No hay tickets con esos filtros.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{ $tickets->links('admin._paginacion') }}
    </section>
@endsection
