@php use App\Support\Fmt; $activa = $sanciones->firstWhere('estado', 'activa'); $beca = $becas->firstWhere('estado', 'activo'); @endphp
@extends('admin.layout')

@section('titulo', Fmt::nombre($e->nombreCompleto()))
@section('subtitulo', $e->codigo_universitario . ' · ' . $e->escuela_profesional)
@section('activo', 'estudiantes')

@push('cabecera')@include('admin._assets')@endpush

@section('contenido')
    <a class="volver" href="{{ route('admin.estudiantes') }}">← Volver a estudiantes</a>
    @include('admin._avisos')

    @if ($activa)
        <div class="aviso error" style="display:flex; justify-content:space-between; gap:16px; flex-wrap:wrap; align-items:center">
            <span>Sanción activa desde el {{ $activa->fecha_inicio->format('d/m/Y') }}: {{ $activa->motivo }}. No puede generar tickets.</span>
            @include('admin._levantar', ['s' => $activa])
        </div>
    @endif

    <div class="kpis">
        <article class="kpi"><div class="etq">Tickets en total</div><div class="valor">{{ number_format($conteo->sum()) }}</div><div class="detalle">Historial completo</div></article>
        <article class="kpi k-verde"><div class="etq">Recogidos</div><div class="valor">{{ (int) ($conteo['recogido'] ?? 0) }}</div><div class="detalle">{{ $aprovechamiento !== null ? $aprovechamiento . '% de aprovechamiento' : 'Sin tickets cerrados' }}</div></article>
        <article class="kpi k-naranja"><div class="etq">Inasistencias</div><div class="valor">{{ $inasistencias }}</div><div class="detalle">Total histórico · la sanción se activa al acumular {{ $limite }}</div></article>
        <article class="kpi {{ $activa ? 'k-rojo' : 'k-verde' }}"><div class="etq">Estado</div><div class="valor" style="font-size:1.3rem; padding-top:8px">{{ $activa ? 'Sancionado' : 'Habilitado' }}</div><div class="detalle">{{ $beca ? 'Becado · ' . ($beca->tipo_beca ?? 'beca') : 'No becado' }}</div></article>
    </div>

    <section class="tarjeta">
        <h2>Datos</h2>
        <dl class="ficha" style="margin-top:14px">
            <div><dt>Código</dt><dd>{{ $e->codigo_universitario }}</dd></div>
            <div><dt>DNI</dt><dd>{{ $e->dni ?? '—' }}</dd></div>
            <div><dt>Escuela</dt><dd>{{ $e->escuela_profesional ?? '—' }}</dd></div>
            <div><dt>Correo institucional</dt><dd>{{ $e->email_institucional ?? '—' }}</dd></div>
            <div><dt>Matriculado</dt><dd>{{ ($e->matriculado ?? false) ? 'Sí' : 'No' }}</dd></div>
        </dl>
    </section>

    <div class="dos iguales">
        <section class="tarjeta">
            <h2>Becas</h2>
            <div class="tabla-caja"><table class="t">
                <thead><tr><th>Registro</th><th>Tipo</th><th>Estado</th></tr></thead>
                <tbody>
                @forelse ($becas as $b)
                    <tr><td>{{ $b->fecha_registro ? \Carbon\Carbon::parse($b->fecha_registro)->format('d/m/Y') : '—' }}</td><td>{{ $b->tipo_beca }}</td>
                        <td><span class="chip {{ $b->estado === 'activo' ? 'e-activo' : 'e-neutro' }}">{{ ucfirst($b->estado) }}</span></td></tr>
                @empty
                    <tr><td colspan="3" class="vacio">Sin beca registrada.</td></tr>
                @endforelse
                </tbody>
            </table></div>
        </section>

        <section class="tarjeta">
            <h2>Sanciones</h2>
            <div class="tabla-caja"><table class="t">
                <thead><tr><th>Inicio</th><th>Fin</th><th>Estado</th><th>Nota</th></tr></thead>
                <tbody>
                @forelse ($sanciones as $s)
                    <tr><td>{{ $s->fecha_inicio->format('d/m/Y') }}</td><td>{{ $s->fecha_fin?->format('d/m/Y') ?? '—' }}</td>
                        <td>@include('admin._chip', ['e' => $s->estado])</td><td class="tenue sm">{{ $s->nota_levantamiento ?? $s->motivo }}</td></tr>
                @empty
                    <tr><td colspan="4" class="vacio">Sin sanciones.</td></tr>
                @endforelse
                </tbody>
            </table></div>
        </section>
    </div>

    <section class="tarjeta">
        <div class="cab"><h2>Últimos tickets</h2>
            <a class="enlace" href="{{ route('admin.tickets', ['q' => $e->codigo_universitario, 'desde' => '2020-01-01', 'hasta' => today()->toDateString()]) }}">Ver todos</a></div>
        <div class="tabla-caja"><table class="t">
            <thead><tr><th>Ticket</th><th>Servicio</th><th>Franja</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            @forelse ($tickets as $t)
                <tr><td class="fuerte">{{ Fmt::ticket($t) }}<div class="tenue sm">{{ $t->fecha->format('d/m/Y') }}</div></td><td>{{ ucfirst($t->servicio) }}</td>
                    <td>{{ Fmt::franja($t->ventanaAtencion) }}</td><td>@include('admin._chip', ['e' => $t->estado])</td>
                    <td><a class="enlace" href="{{ route('admin.tickets.show', $t) }}">Ver</a></td></tr>
            @empty
                <tr><td colspan="5" class="vacio">Aún no generó tickets.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </section>
@endsection
