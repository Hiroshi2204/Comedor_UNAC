@php
    $estadoImp = !$ultima ? ['neutro', 'Sin importar'] : ($ultima->estado === 'procesado' ? ['listo', 'Listo'] : ['cargado', 'Por procesar']);
@endphp
@extends('admin.layout')

@section('titulo', 'Becados OBU')
@section('subtitulo', 'Importación, validación y trazabilidad del listado de OBU')
@section('activo', 'becados')

@push('cabecera')@include('admin._assets')@endpush

@section('contenido')
    @include('admin._avisos')

    <div class="dos">
        <section class="tarjeta">
            <h2>Importar listado de becados</h2>
            <p class="sub">El archivo se conserva en staging antes de transformar los datos: primero lo revisas y luego lo procesas.</p>

            <form method="POST" action="{{ route('admin.becados.importar') }}" enctype="multipart/form-data" data-una-vez id="form-import">
                @csrf
                <div class="zona" id="zona">
                    <b>Arrastra tu Excel aquí o selecciónalo</b>
                    <small>XLSX · XLS · CSV · máximo 10 MB · una hoja por escuela, con la columna «DNI»</small>
                    <input type="file" name="archivo" id="archivo" accept=".xlsx,.xls,.csv" required>
                </div>
                @error('archivo')<p class="err">{{ $message }}</p>@enderror
                <div class="acciones" style="justify-content:flex-end">
                    <button type="submit" class="btn btn-primario" id="btn-import" @disabled(!$periodoId)>Cargar archivo</button>
                </div>
                @unless ($periodoId)<p class="err">No se encontró el período activo ({{ $periodo }}). Revisa SGA_PERIODO.</p>@endunless
            </form>
        </section>

        <section class="tarjeta">
            <h2>Período académico</h2>
            <div class="estado-box" style="margin-top:14px">
                <span class="periodo">{{ $periodo }}</span>
                <div><div class="etq-campo" style="margin-bottom:6px">Estado de importación</div>
                    <span class="chip e-{{ $estadoImp[0] }}">{{ $estadoImp[1] }}</span></div>
                <div><div class="etq-campo" style="margin-bottom:4px">Becados activos</div>
                    <b style="font-size:1.4rem">{{ number_format($becadosActivos) }}</b></div>
                @if ($ultima)
                    <a class="enlace" href="{{ route('admin.becados.show', $ultima) }}">Ver último archivo</a>
                @endif
            </div>
        </section>
    </div>

    <section class="tarjeta">
        <h2>Últimas importaciones</h2>
        <div class="tabla-caja" style="margin-top:12px">
            <table class="t">
                <thead><tr><th>Archivo</th><th>Registros</th><th>Estado</th><th>Fecha</th><th>Cargó</th><th>Acción</th></tr></thead>
                <tbody>
                @forelse ($importaciones as $i)
                    @php $est = in_array($i->id, $vigentes, true) ? $i->estado : 'historico'; @endphp
                    <tr>
                        <td class="fuerte">{{ $i->archivo }}</td>
                        <td>{{ number_format($i->registros) }}@if ($i->procesados !== null)<div class="tenue sm">{{ number_format($i->procesados) }} becados</div>@endif</td>
                        <td>@include('admin._chip', ['e' => $est])</td>
                        <td class="tenue">{{ $i->created_at->format('d/m/Y H:i') }}</td>
                        <td class="tenue">{{ $i->usuario ? \App\Support\Fmt::nombre($i->usuario->nombre) : '—' }}</td>
                        <td><a class="enlace" href="{{ route('admin.becados.show', $i) }}">Ver detalle</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="vacio">Aún no se cargó ningún listado desde el panel.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $importaciones->links('admin._paginacion') }}
    </section>
@endsection

