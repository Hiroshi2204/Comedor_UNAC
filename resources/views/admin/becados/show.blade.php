@extends('admin.layout')

@section('titulo', 'Importación de becados')
@section('subtitulo', $importacion->archivo)
@section('activo', 'becados')

@push('cabecera')@include('admin._assets')@endpush

@section('contenido')
    <a class="volver" href="{{ route('admin.becados') }}">← Volver a becados</a>
    @include('admin._avisos')

    <section class="tarjeta">
        <div class="cab">
            <h2>Resumen del archivo</h2>
            @include('admin._chip', ['e' => $esUltima ? $importacion->estado : 'historico'])
        </div>
        <dl class="ficha">
            <div><dt>Archivo</dt><dd>{{ $importacion->archivo }}</dd></div>
            <div><dt>Cargado</dt><dd>{{ $importacion->created_at->format('d/m/Y H:i') }}</dd></div>
            <div><dt>Registros</dt><dd>{{ number_format($importacion->registros) }}</dd></div>
            <div><dt>Becados creados o actualizados</dt><dd>{{ $importacion->procesados !== null ? number_format($importacion->procesados) : '—' }}</dd></div>
            <div><dt>Hojas</dt><dd>{{ count($importacion->resumen['hojas'] ?? []) }}</dd></div>
        </dl>

        @if (!empty($importacion->resumen['sin_encabezado']))
            <div class="aviso info" style="margin-top:16px">Hojas ignoradas por no tener la columna «DNI»: {{ implode(', ', $importacion->resumen['sin_encabezado']) }}.</div>
        @endif

        @if ($esUltima && $importacion->estado === 'cargado')
            <form method="POST" action="{{ route('admin.becados.procesar', $importacion) }}" data-una-vez
                  onsubmit="return confirm('Se cruzará el listado con el padrón y se crearán o actualizarán los becados. ¿Continuar?')" class="acciones">
                @csrf
                <button type="submit" class="btn btn-primario">Procesar y crear becados</button>
                <span class="nota" style="align-self:center">Revisa antes las filas «observado» y «duplicado»; las duplicadas se omiten.</span>
            </form>
        @elseif (!$esUltima)
            <p class="nota" style="margin-top:14px">Este archivo es histórico: ya hay una carga más reciente para el período.</p>
        @endif
    </section>

    <section class="tarjeta">
        <h2>Filas del archivo</h2>
        <div class="resumen" style="margin-top:12px">
            <a class="chip {{ !$estado ? 'sel' : '' }}" href="{{ route('admin.becados.show', $importacion) }}">Todas <b>{{ number_format($conteo->sum()) }}</b></a>
            @foreach ($conteo as $e => $n)
                <a class="chip e-{{ $e }} {{ $estado === $e ? 'sel' : '' }}" href="{{ route('admin.becados.show', [$importacion, 'estado' => $e]) }}">{{ ucfirst(str_replace('_', ' ', $e)) }} <b>{{ number_format($n) }}</b></a>
            @endforeach
        </div>

        <div class="tabla-caja">
            <table class="t">
                <thead><tr><th>Hoja</th><th>Fila</th><th>Nombre</th><th>DNI</th><th>Código</th><th>Estado</th><th>Observación</th></tr></thead>
                <tbody>
                @forelse ($filas as $f)
                    <tr>
                        <td>{{ $f->hoja }}</td><td class="tenue">{{ $f->fila_excel }}</td>
                        <td class="fuerte">{{ \App\Support\Fmt::nombre($f->nombre_completo) }}</td>
                        <td>{{ $f->dni ?? $f->dni_raw }}</td><td>{{ $f->codigo ?? $f->codigo_raw }}</td>
                        <td><span class="chip e-{{ $f->estado }}">{{ ucfirst(str_replace('_', ' ', $f->estado)) }}</span></td>
                        <td class="sm tenue">{{ $f->observacion }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="vacio">Sin filas para ese filtro.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $filas->links('admin._paginacion') }}
    </section>
@endsection
