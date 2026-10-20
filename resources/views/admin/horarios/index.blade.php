@extends('admin.layout')

@section('titulo', 'Horarios')
@section('subtitulo', 'Sincronización de los horarios de clase con el SGA')
@section('activo', 'horarios')

@push('cabecera')@include('admin._assets')@endpush

@section('contenido')
    @include('admin._avisos')

    @php $sin = max(0, $matriculados - $conHorario); @endphp
    <div class="kpis">
        <article class="kpi"><div class="etq">Matriculados</div><div class="valor">{{ number_format($matriculados) }}</div><div class="detalle">Período {{ $periodo }}</div></article>
        <article class="kpi k-verde"><div class="etq">Con horario cargado</div><div class="valor">{{ number_format($conHorario) }}</div><div class="detalle">{{ number_format($filas) }} bloques de clase</div></article>
        <article class="kpi {{ $sin > 0 ? 'k-naranja' : 'k-verde' }}"><div class="etq">Sin horario</div><div class="valor">{{ number_format($sin) }}</div><div class="detalle">Sin clases registradas en el SGA</div></article>
        <article class="kpi"><div class="etq">Última sincronización</div><div class="valor" style="font-size:1.35rem; padding-top:8px">{{ $ultima ? $ultima->format('d/m H:i') : '—' }}</div><div class="detalle">{{ $ultima ? $ultima->diffForHumans() : 'Aún no hay horarios' }}</div></article>
    </div>

    <div class="dos">
        <section class="tarjeta">
            <h2>Sincronización con el SGA</h2>
            <p class="sub">Descarga la programación horaria y los matriculados de {{ $escuelas ?: 'las' }} escuela(s) configuradas y reconstruye el horario de cada estudiante.</p>
            <dl class="ficha">
                <div><dt>Período</dt><dd>{{ $periodo }}</dd></div>
                <div><dt>Escuelas configuradas</dt><dd>{{ $escuelas }}</dd></div>
                <div><dt>Automática</dt><dd>Todos los días 03:00</dd></div>
            </dl>

            <div class="acciones">
                @if ($periodoId && !$enCurso)
                    <form method="POST" action="{{ route('admin.horarios.sincronizar') }}" data-una-vez
                          onsubmit="return confirm('Se iniciará una sincronización completa con el SGA. ¿Continuar?')">
                        @csrf <button type="submit" class="btn btn-primario">Sincronizar ahora</button>
                    </form>
                @endif
            </div>
        </section>

        <section class="tarjeta">
            <h2>Matriculados sin horario</h2>
            <p class="sub">Primeros 15. Suelen ser alumnos sin carga académica registrada en el SGA.</p>
            <div class="tabla-caja"><table class="t">
                <tbody>
                @forelse ($sinHorario as $e)
                    <tr><td class="fuerte"><a class="enlace" href="{{ route('admin.estudiantes.show', $e) }}">{{ $e->codigo_universitario }}</a></td>
                        <td>{{ \App\Support\Fmt::nombre($e->nombreCompleto()) }}<div class="tenue sm">{{ $e->escuela_profesional }}</div></td></tr>
                @empty
                    <tr><td class="vacio">Todos los matriculados tienen horario.</td></tr>
                @endforelse
                </tbody>
            </table></div>
        </section>
    </div>

    <section class="tarjeta" id="sync" data-url="{{ route('admin.horarios.progreso') }}" data-activa="{{ $enCurso ? '1' : '0' }}" hidden>
        <div class="cab">
            <div><h2>Progreso de la sincronización</h2><p class="nota" id="sync-fase" style="margin-top:4px">—</p></div>
            <span class="chip" id="sync-chip">—</span>
        </div>
        <div class="barras" style="margin:14px 0 6px">
            <div class="fila-barra">
                <span class="nom" id="sync-conteo">0 / 0</span>
                <div class="pista" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" id="sync-pista"><div class="relleno c-azul" id="sync-relleno" style="width:0%"></div></div>
                <span class="num" id="sync-pct">0%</span>
            </div>
        </div>
        <p class="nota" id="sync-tiempo"></p>
        <pre class="sync-log" id="sync-log" aria-live="polite"></pre>
        <div id="sync-resultado" hidden>
            <div class="tabla-caja"><table class="t"><tbody id="sync-tabla"></tbody></table></div>
        </div>
    </section>

    <script>
    (function () {
        var raiz = document.getElementById('sync');
        if (!raiz) return;
        var url = raiz.dataset.url, venia = raiz.dataset.activa === '1', ciclo = null;
        var $ = function (id) { return document.getElementById(id); };
        var rotulo = {en_cola: 'Iniciando', corriendo: 'En curso', terminado: 'Completada', error: 'Con error', detenida: 'Sin respuesta'};
        var clase  = {en_cola: 'e-generado', corriendo: 'e-generado', terminado: 'e-recogido', error: 'e-cancelado', detenida: 'e-cancelado'};

        function dur(seg) { var m = Math.floor(seg / 60), s = seg % 60; return m + ' min ' + (s < 10 ? '0' : '') + s + ' s'; }

        function pintar(d) {
            if (d.estado === 'ninguna') { raiz.hidden = true; return false; }
            raiz.hidden = false;
            $('sync-fase').textContent = d.fase;
            var chip = $('sync-chip'); chip.textContent = rotulo[d.estado] || d.estado; chip.className = 'chip ' + (clase[d.estado] || '');
            $('sync-conteo').textContent = d.total ? (d.hechas + ' / ' + d.total + ' escuelas') : 'Preparando…';
            $('sync-relleno').style.width = d.porcentaje + '%'; $('sync-pct').textContent = d.porcentaje + '%';
            $('sync-pista').setAttribute('aria-valuenow', d.porcentaje);
            var fin = d.fin || d.ahora;
            $('sync-tiempo').textContent = d.inicio ? ((d.fin ? 'Duró ' : 'Transcurrido: ') + dur(Math.max(0, fin - d.inicio)) + (d.origen === 'consola' ? ' · lanzada por consola o automática' : '')) : '';

            var log = $('sync-log'), pegado = log.scrollTop + log.clientHeight >= log.scrollHeight - 20;
            log.textContent = d.lineas.map(function (l) { return l.h + '  ' + l.t; }).join('\n') || 'Esperando datos…';
            if (pegado) log.scrollTop = log.scrollHeight;

            var res = $('sync-resultado');
            if (d.estado === 'terminado' && d.resultado) {
                var t = $('sync-tabla'); t.textContent = '';
                Object.keys(d.resultado).forEach(function (k) {
                    var tr = document.createElement('tr'), a = document.createElement('td'), b = document.createElement('td');
                    a.textContent = k; b.textContent = Number(d.resultado[k]).toLocaleString('es-PE'); b.className = 'num fuerte';
                    tr.appendChild(a); tr.appendChild(b); t.appendChild(tr);
                });
                if (d.extra && d.extra.becados_sin_horario !== undefined) {
                    var tr2 = document.createElement('tr'), a2 = document.createElement('td'), b2 = document.createElement('td');
                    a2.textContent = 'Becados activos sin horario (no podrán sacar ticket)'; b2.textContent = d.extra.becados_sin_horario; b2.className = 'num fuerte';
                    tr2.appendChild(a2); tr2.appendChild(b2); t.appendChild(tr2);
                }
                res.hidden = false;
            } else { res.hidden = true; }
            return d.estado === 'en_cola' || d.estado === 'corriendo';
        }

        function consultar() {
            fetch(url, {headers: {'Accept': 'application/json'}, credentials: 'same-origin'})
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    var viva = pintar(d);
                    if (viva) { venia = true; if (!ciclo) ciclo = setInterval(consultar, 2000); }
                    else {
                        if (ciclo) { clearInterval(ciclo); ciclo = null; }
                        if (venia && d.estado === 'terminado') setTimeout(function () { location.reload(); }, 4000); // refresca los indicadores
                    }
                })
                .catch(function () { /* red intermitente: reintenta en el siguiente ciclo */ });
        }
        consultar();
    })();
    </script>
@endsection
