@extends('trabajador.layouts.app')

@section('titulo', 'Mi actividad')
@section('subtitulo', 'Escaneos que registraste hoy, aceptados y rechazados')
@section('pagina', 'actividad')

@push('head')
<style>
    .tabs {
        display: flex;
        gap: 8px;
        margin-bottom: 16px;
        flex-wrap: wrap;
    }

    .tabs a {
        padding: 8px 16px;
        border-radius: 999px;
        font-size: .78rem;
        font-weight: 700;
        color: var(--gris);
        background: #fff;
        border: 1px solid var(--borde);
    }

    .tabs a.on {
        background: var(--azul-2);
        border-color: var(--azul-2);
        color: #fff;
    }

    .tabla {
        background: #fff;
        border: 1px solid var(--borde);
        border-radius: 14px;
        overflow: hidden;
    }

    table.act {
        width: 100%;
        border-collapse: collapse;
    }

    table.act th {
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

    table.act td {
        padding: 13px 18px;
        font-size: .84rem;
        border-top: 1px solid #EEF2F8;
        vertical-align: middle;
    }

    table.act tbody tr:first-child td {
        border-top: 0;
    }

    table.act td small {
        display: block;
        margin-top: 2px;
        font-size: .7rem;
        color: var(--gris);
    }

    .vacio {
        padding: 34px 20px;
        text-align: center;
        color: var(--gris);
        font-size: .88rem;
    }

    .paginas {
        display: flex;
        justify-content: space-between;
        align-items: center;
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

    @media (max-width: 720px) {
        table.act thead {
            display: none;
        }

        table.act,
        table.act tbody,
        table.act tr,
        table.act td {
            display: block;
            width: 100%;
        }

        table.act tr {
            padding: 12px 16px;
            border-top: 1px solid #EEF2F8;
        }

        table.act tbody tr:first-child {
            border-top: 0;
        }

        table.act td {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 4px 0;
            border: 0;
            text-align: right;
        }

        table.act td::before {
            content: attr(data-label);
            font-size: .66rem;
            font-weight: 700;
            letter-spacing: .05em;
            text-transform: uppercase;
            color: var(--gris);
            text-align: left;
        }
    }
</style>
@endpush

@section('contenido')
@php
$txt = [
'ok' => 'Registrado', 'no_existe' => 'Código desconocido', 'cancelado' => 'Ticket cancelado', 'vencido' => 'Ticket vencido',
'ya_recogido' => 'Ya entregado', 'fuera_de_fecha' => 'Ticket de otro día', 'ya_validado' => 'Ya había ingresado',
'falta_puerta' => 'Sin ingreso en puerta', 'muy_temprano' => 'Antes de su ventana', 'sin_permiso' => 'Sin permiso',
];
@endphp

<nav class="tabs" aria-label="Filtro">
    <a href="{{ route('trabajador.actividad') }}" @class(['on'=> !$filtro])>Todos ({{ $totales['todos'] }})</a>
    <a href="{{ route('trabajador.actividad', ['ver' => 'aceptados']) }}" @class(['on'=> $filtro === 'aceptados'])>Aceptados ({{ $totales['aceptados'] }})</a>
    <a href="{{ route('trabajador.actividad', ['ver' => 'rechazados']) }}" @class(['on'=> $filtro === 'rechazados'])>Rechazados ({{ $totales['rechazados'] }})</a>
</nav>

<div class="tabla">
    @if ($escaneos->isEmpty())
    <div class="vacio">No hay escaneos para mostrar hoy.</div>
    @else
    <table class="act">
        <thead>
            <tr>
                <th>Hora</th>
                <th>Estudiante</th>
                <!-- <th>Ticket</th> -->
                <th>Resultado</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($escaneos as $s)
            <tr>
                <td data-label="Hora"><b>{{ $s->created_at->format('H:i:s') }}</b></td>
                <td data-label="Estudiante">
                    @if ($s->ticket?->estudiante)
                    <span>{{ \Illuminate\Support\Str::title(mb_strtolower($s->ticket->estudiante->nombreCompleto())) }}<small>{{ $s->ticket->estudiante->codigo_universitario }}</small></span>
                    @else — @endif
                </td>
                <!-- <td data-label="Ticket">{{ $s->ticket ? $s->ticket->codigo : '—' }}</td> -->
                <td data-label="Resultado"><span class="estado-chip {{ $s->exitoso ? 'e-recogido' : 'e-vencido' }}">{{ $txt[$s->resultado] ?? $s->resultado }}</span></td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>

@if ($escaneos->hasPages())
<nav class="paginas" aria-label="Paginación">
    @if ($escaneos->onFirstPage()) <span class="off">← Anterior</span>@else<a href="{{ $escaneos->previousPageUrl() }}">← Anterior</a>@endif
    <span>Página {{ $escaneos->currentPage() }} de {{ $escaneos->lastPage() }}</span>
    @if ($escaneos->hasMorePages())<a href="{{ $escaneos->nextPageUrl() }}">Siguiente →</a>@else<span class="off">Siguiente →</span>@endif
</nav>
@endif
@endsection