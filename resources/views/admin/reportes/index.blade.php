@extends('admin.layout')

@section('titulo', 'Reportes y estadísticas')
@section('subtitulo', 'Indicadores de asistencia, tickets y sanciones')
@section('activo', 'reportes')

@push('cabecera')@include('admin._assets')@endpush

@section('contenido')
@include('admin._avisos')

<section class="tarjeta">
    <form method="GET" action="{{ route('admin.reportes') }}" class="filtros">
        <div class="campo"><label>Período</label><span class="chip" style="padding:11px 18px">{{ $periodo }}</span></div>
        <div class="campo"><label for="rango">Rango</label>
            <select id="rango" name="rango">
                @foreach ($rangos as $k => $t)<option value="{{ $k }}" @selected($rango===$k)>{{ ucfirst($t) }}</option>@endforeach
            </select>
        </div>
        <div class="campo"><label for="desde">Desde</label><input id="desde" type="date" name="desde" value="{{ $desde->toDateString() }}"></div>
        <div class="campo"><label for="hasta">Hasta</label><input id="hasta" type="date" name="hasta" value="{{ $hasta->toDateString() }}"></div>
        <button class="btn btn-primario" type="submit">Aplicar</button>
        <a class="btn btn-suave" href="{{ route('admin.reportes.exportar', ['rango' => $rango, 'desde' => $desde->toDateString(), 'hasta' => $hasta->toDateString()]) }}">Exportar reporte</a>
    </form>
    <p class="nota" style="margin-top:12px">Mostrando del {{ $desde->format('d/m/Y') }} al {{ $hasta->format('d/m/Y') }}. Con «Personalizado» se usan las fechas de arriba.</p>
</section>

<div class="dos iguales">
    <section class="tarjeta">
        <h2>Asistencia por día</h2>
        <p class="sub">Recogidos ÷ tickets ya cerrados (recogidos, vencidos y no recogidos).</p>
        <div class="columnas" role="img" aria-label="Asistencia por día de la semana">
            @foreach ($porDia as $d)
            <div class="col">
                <small>{{ $d['valor'] !== null ? $d['valor'] . '%' : '—' }}</small>
                <i @class(['sin'=> $d['valor'] === null]) @style(['height: ' . ($d['valor'] ?? 2) . '%'])></i>
                <span>{{ $d['etiqueta'] }}</span>
            </div>
            @endforeach
        </div>
    </section>

    <section class="tarjeta">
        <h2>Estado de tickets</h2>
        <p class="sub">Porcentaje sobre {{ number_format($total) }} tickets.</p>
        <div class="barras" style="margin-top:8px">
            @foreach ($estados as [$nombre, $n, $pct, $color])
            <div class="fila-barra">
                <span class="nom">{{ $nombre }}</span>
                <div class="pista" role="img" aria-label="{{ $nombre }}: {{ $n }}">@if ($n > 0)<div @class(['relleno', str_replace('b-', 'c-' , $color)]) @style(['width: ' . max($pct, 1) . ' %'])></div>@endif</div>
                <span class="num">{{ $pct }}%</span>
            </div>
            @endforeach
        </div>
    </section>
</div>

<section class="tarjeta">
    <h2>Indicadores del rango</h2>
    <div class="indicadores">
        <div class="indicador"><span class="punto c-azul"></span>
            <div class="etq">Tickets generados</div>
            <div class="valor">{{ number_format($total) }}</div>
            <div class="detalle">{{ number_format($porDiaHabil) }} por día con servicio</div>
        </div>
        <div class="indicador"><span class="punto c-verde"></span>
            <div class="etq">Tickets recogidos</div>
            <div class="valor">{{ number_format($recogidos) }}</div>
            <div class="detalle">{{ $pRecogidos }}% del total</div>
        </div>
        <div class="indicador"><span class="punto c-naranja"></span>
            <div class="etq">Inasistencias</div>
            <div class="valor">{{ number_format($inasist) }}</div>
            <div class="detalle">{{ $total > 0 ? round($inasist * 100 / $total, 1) : 0 }}% del total</div>
        </div>
        <div class="indicador"><span class="punto c-rojo"></span>
            <div class="etq">Sanciones</div>
            <div class="valor">{{ number_format($sanciones) }}</div>
            <div class="detalle">generadas en el rango</div>
        </div>
    </div>
</section>

<section class="tarjeta">
    <h2>Por servicio</h2>
    <div class="tabla-caja" style="margin-top:12px">
        <table class="t">
            <thead>
                <tr>
                    <th>Servicio</th>
                    <th class="num">Generados</th>
                    <th class="num">Recogidos</th>
                    <th class="num">Asistencia</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($porServicio as $s)
                <tr>
                    <td class="fuerte">{{ $s->servicio }}</td>
                    <td class="num">{{ number_format($s->generados) }}</td>
                    <td class="num">{{ number_format($s->recogidos) }}</td>
                    <td class="num">{{ $s->asistencia !== null ? $s->asistencia . '%' : '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
@endsection