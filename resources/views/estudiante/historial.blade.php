@extends('estudiante.layouts.app')

@section('titulo', 'Mis tickets')
@section('subtitulo', 'Historial de tus tickets y reservas')
@section('pagina', 'historial')

@push('head')
<style>
    .stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
        margin-bottom: 20px;
    }

    .stat {
        background: #fff;
        border: 1px solid var(--borde);
        border-radius: 14px;
        padding: 16px 18px;
    }

    .stat small {
        display: block;
        font-size: .7rem;
        font-weight: 600;
        color: var(--gris);
    }

    .stat b {
        display: block;
        margin-top: 6px;
        font-size: 1.7rem;
        font-weight: 700;
        line-height: 1;
    }

    .stat em {
        display: block;
        margin-top: 6px;
        font-style: normal;
        font-size: .68rem;
        color: var(--gris);
    }

    .stat.v b {
        color: var(--verde);
    }

    .stat.a b {
        color: var(--ambar);
    }

    .stat.g b {
        color: var(--gris);
    }

    .stat.z b {
        color: var(--azul-2);
    }

    .filtros {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr)) auto;
        gap: 12px;
        align-items: end;
    }

    .filtros label {
        display: block;
        margin: 0 0 6px;
        font-size: .7rem;
        font-weight: 700;
        color: var(--gris);
    }

    .filtros input[type=date],
    .filtros select {
        width: 100%;
        height: 42px;
        padding: 0 12px;
        font: inherit;
        font-size: .85rem;
        color: var(--tinta);
        background: var(--campo);
        border: 1px solid var(--borde);
        border-radius: 8px;
    }

    .filtros .acciones {
        display: flex;
        gap: 8px;
    }

    .filtros .btn {
        width: auto;
        height: 42px;
        padding: 0 18px;
        margin: 0;
    }

    .btn-sec {
        background: #fff;
        color: var(--azul-2);
        border: 1px solid var(--borde);
    }

    .btn-sec:hover {
        background: var(--fondo);
    }

    .lista-cab {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        gap: 12px;
        margin: 22px 0 10px;
    }

    .lista-cab h2 {
        margin: 0;
        font-size: 1rem;
    }

    .lista-cab span {
        font-size: .75rem;
        color: var(--gris);
    }

    .tabla {
        background: #fff;
        border: 1px solid var(--borde);
        border-radius: 14px;
        overflow: hidden;
    }

    table.hist {
        width: 100%;
        border-collapse: collapse;
    }

    table.hist th {
        text-align: left;
        padding: 12px 18px;
        font-size: .66rem;
        font-weight: 700;
        letter-spacing: .05em;
        text-transform: uppercase;
        color: var(--gris);
        background: var(--campo);
        border-bottom: 1px solid var(--borde);
    }

    table.hist td {
        padding: 14px 18px;
        font-size: .84rem;
        vertical-align: middle;
        border-top: 1px solid #EEF2F8;
    }

    table.hist tbody tr:first-child td {
        border-top: 0;
    }

    table.hist td b {
        display: block;
        font-weight: 600;
    }

    table.hist td small {
        display: block;
        margin-top: 2px;
        font-size: .7rem;
        color: var(--gris);
        text-transform: capitalize;
    }

    table.hist code {
        font: 600 .78rem/1 ui-monospace, 'SF Mono', Consolas, monospace;
        color: var(--tinta);
    }

    .detalle {
        font-size: .78rem;
        color: var(--gris);
    }

    .detalle.mal {
        color: #B45F00;
        font-weight: 600;
    }

    .enlace {
        font-size: .78rem;
        font-weight: 700;
        color: var(--azul-2);
    }

    .enlace:hover {
        text-decoration: underline;
    }

    .vacio {
        padding: 36px 20px;
        text-align: center;
        color: var(--gris);
        font-size: .88rem;
    }

    .vacio b {
        display: block;
        margin-bottom: 4px;
        color: var(--tinta);
        font-size: .95rem;
    }

    .paginas {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        margin-top: 14px;
        font-size: .78rem;
        color: var(--gris);
    }

    .paginas a,
    .paginas span.off {
        padding: 8px 14px;
        border: 1px solid var(--borde);
        border-radius: 8px;
        background: #fff;
        font-weight: 600;
        color: var(--azul-2);
    }

    .paginas span.off {
        color: #A9B4C6;
        background: var(--campo);
    }

    .paginas a:hover {
        background: var(--fondo);
    }

    .sanciones {
        margin-top: 20px;
    }

    .sancion+.sancion {
        margin-top: 14px;
        padding-top: 14px;
        border-top: 1px solid #EEF2F8;
    }

    .sancion .fila {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
    }

    .sancion b {
        font-size: .88rem;
    }

    .sancion p {
        margin: 6px 0 0;
        font-size: .8rem;
        color: var(--gris);
        line-height: 1.45;
    }

    @media (max-width: 1024px) {
        .filtros {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .filtros .acciones {
            grid-column: 1 / -1;
        }
    }

    @media (max-width: 860px) {
        .stats {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 720px) {
        table.hist thead {
            display: none;
        }

        table.hist,
        table.hist tbody,
        table.hist tr,
        table.hist td {
            display: block;
            width: 100%;
        }

        table.hist tr {
            padding: 14px 16px;
            border-top: 1px solid #EEF2F8;
        }

        table.hist tbody tr:first-child {
            border-top: 0;
        }

        table.hist td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 5px 0;
            border: 0;
            text-align: right;
        }

        table.hist td::before {
            content: attr(data-label);
            flex: none;
            font-size: .66rem;
            font-weight: 700;
            letter-spacing: .05em;
            text-transform: uppercase;
            color: var(--gris);
            text-align: left;
        }

        table.hist td small {
            display: inline;
            margin-left: 6px;
        }
    }

    @media (max-width: 420px) {
        .filtros {
            grid-template-columns: 1fr;
        }

        .stat b {
            font-size: 1.4rem;
        }
    }
</style>
@endpush

@section('contenido')
@php
$estadosTxt = [
'generado' => 'Generado', 'validado' => 'Validado', 'recogido' => 'Entregado',
'vencido' => 'Vencido', 'no_recogido' => 'No recogido', 'cancelado' => 'Cancelado',
];
$hayFiltros = collect($filtros)->filter()->isNotEmpty();
@endphp

@if ($aviso)
<div class="alerta error" role="alert">{{ $aviso }}</div>
@endif

<section class="stats" aria-label="Resumen">
    <div class="stat v"><small>Entregados</small><b>{{ $conteo['recogido'] ?? 0 }}</b><em>Comidas recibidas</em></div>
    <div class="stat g"><small>Cancelados</small><b>{{ $conteo['cancelado'] ?? 0 }}</b><em>No cuentan como inasistencia</em></div>
    <div class="stat a"><small>Vencidos</small><b>{{ $conteo['vencido'] ?? 0 }}</b><em>Cuentan como inasistencia</em></div>
    <div class="stat z">
        <small>Aprovechamiento</small>
        <b>{{ $aprovechamiento === null ? '—' : $aprovechamiento . '%' }}</b>
        <em>Entregados ÷ (entregados + vencidos + no recogidos)</em>
    </div>
</section>

<form method="GET" action="{{ route('estudiante.tickets.historial') }}" class="tarjeta filtros" aria-label="Filtrar tickets">
    <div>
        <label for="f-servicio">Servicio</label>
        <select id="f-servicio" name="servicio">
            <option value="">Todos</option>
            @foreach (['desayuno', 'almuerzo', 'cena'] as $s)
            <option value="{{ $s }}" @selected(($filtros['servicio'] ?? '' )===$s)>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="f-estado">Estado</label>
        <select id="f-estado" name="estado">
            <option value="">Todos</option>
            @foreach ($estadosTxt as $k => $txt)
            <option value="{{ $k }}" @selected(($filtros['estado'] ?? '' )===$k)>{{ $txt }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="f-desde">Desde</label>
        <input id="f-desde" type="date" name="desde" value="{{ $filtros['desde'] ?? '' }}">
    </div>
    <div>
        <label for="f-hasta">Hasta</label>
        <input id="f-hasta" type="date" name="hasta" value="{{ $filtros['hasta'] ?? '' }}">
    </div>
    <div class="acciones">
        <button class="btn btn-primario">Filtrar</button>
        @if ($hayFiltros)
        <a class="btn btn-sec" href="{{ route('estudiante.tickets.historial') }}">Limpiar</a>
        @endif
    </div>
</form>

<div class="lista-cab">
    <h2>{{ $hayFiltros ? 'Resultados' : 'Todos tus tickets' }}</h2>
    <span>{{ $tickets->total() }} {{ $tickets->total() === 1 ? 'ticket' : 'tickets' }}</span>
</div>

<div class="tabla">
    @if ($tickets->isEmpty())
    <div class="vacio">
        <b>{{ $hayFiltros ? 'No hay tickets con esos filtros' : 'Aún no tienes tickets' }}</b>
        {{ $hayFiltros ? 'Prueba con otro rango de fechas o quita algún filtro.' : 'Cuando generes tu primer ticket aparecerá aquí.' }}
    </div>
    @else
    <table class="hist">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Servicio</th>
                <th>Horario</th>
                <!-- <th>Código</th> -->
                <th>Estado</th>
                <th>Detalle</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($tickets as $t)
            @php
            $f = \Carbon\Carbon::parse($t->fecha)->locale('es');
            $v = $t->ventanaAtencion;
            $esHoy = $f->isToday();
            @endphp
            <tr>
                <td data-label="Fecha"><span><b>{{ $f->translatedFormat('d M Y') }}</b><small>{{ $f->isoFormat('dddd') }}</small></span></td>
                <td data-label="Servicio">{{ ucfirst($t->servicio) }}</td>
                <td data-label="Horario">
                    @if ($v){{ substr($v->hora_inicio, 0, 5) }} – {{ substr($v->hora_fin, 0, 5) }}
                    @elseif ($t->vence_at)hasta {{ $t->vence_at->format('H:i') }}
                    @else — @endif
                </td>
                <!-- <td data-label="Código"><code>{{ $t->codigo ?: \Illuminate\Support\Str::limit($t->codigo_qr, 14, '…') }}</code></td> -->
                <td data-label="Estado"><span class="estado-chip e-{{ $t->estado }}">{{ $estadosTxt[$t->estado] ?? $t->estado }}</span></td>
                <td data-label="Detalle">
                    @if ($t->estado === 'recogido')
                    <span class="detalle">{{ $t->fecha_hora_recojo ? 'Entregado a las ' . $t->fecha_hora_recojo->format('H:i') : 'Entregado' }}</span>
                    @elseif ($t->estado === 'vencido')
                    <span class="detalle mal">Cuenta como inasistencia</span>
                    @elseif ($t->estado === 'no_recogido')
                    <span class="detalle">Pasó por la puerta, no recogió</span>
                    @elseif ($t->estado === 'cancelado')
                    <span class="detalle">Cancelado por ti</span>
                    @elseif ($esHoy && in_array($t->estado, ['generado', 'validado'], true))
                    <a class="enlace" href="{{ route('estudiante.inicio') }}#tickets">Ver QR →</a>
                    @else
                    <span class="detalle">—</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>

@if ($tickets->hasPages())
<nav class="paginas" aria-label="Paginación">
    @if ($tickets->onFirstPage()) <span class="off">← Anterior</span>
    @else <a href="{{ $tickets->previousPageUrl() }}" rel="prev">← Anterior</a> @endif
    <span>Página {{ $tickets->currentPage() }} de {{ $tickets->lastPage() }}</span>
    @if ($tickets->hasMorePages()) <a href="{{ $tickets->nextPageUrl() }}" rel="next">Siguiente →</a>
    @else <span class="off">Siguiente →</span> @endif
</nav>
@endif

@if ($sanciones->isNotEmpty())
<section class="tarjeta sanciones" aria-labelledby="t-sanc">
    <header>
        <h3 id="t-sanc">Mis sanciones</h3>
    </header>
    @foreach ($sanciones as $s)
    <div class="sancion">
        <div class="fila">
            <b>Desde el {{ \Carbon\Carbon::parse($s->fecha_inicio)->locale('es')->translatedFormat('d M Y') }}</b>
            <span class="estado-chip {{ $s->estado === 'activa' ? 'e-vencido' : 'e-recogido' }}">{{ $s->estado === 'activa' ? 'Activa' : 'Levantada' }}</span>
        </div>
        <p>
            {{ $s->motivo ?: 'Sanción por inasistencias acumuladas.' }}
            @if ($s->estado === 'activa')
            Comunícate con el administrador del comedor para que la levante.
            @elseif ($s->fecha_fin)
            Levantada el {{ \Carbon\Carbon::parse($s->fecha_fin)->locale('es')->translatedFormat('d M Y') }}.
            @endif
        </p>
    </div>
    @endforeach
</section>
@endif
@endsection