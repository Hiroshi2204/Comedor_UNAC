<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use App\Support\Periodo;
use App\Support\LanzadorSincronizacion;
use App\Support\SincronizacionProgreso;
use Illuminate\Support\Facades\DB;

/** Estado de la sincronización de horarios con el SGA. */
class HorariosAdminController extends Controller
{
    public function index()
    {
        $seguro = function (callable $f, $defecto = null) {
            try { return $f(); } catch (\Throwable $e) { report($e); return $defecto; }
        };

        $matriculados = $seguro(fn () => Estudiante::where('matriculado', true)->count(), 0);
        $conHorario   = $seguro(fn () => DB::table('horarios_clase')->distinct()->count('estudiante_id'), 0);
        $filas        = $seguro(fn () => DB::table('horarios_clase')->count(), 0);
        $ultima       = $seguro(fn () => DB::table('horarios_clase')->max('updated_at') ?: DB::table('horarios_clase')->max('created_at'));

        $sinHorario = $seguro(fn () => Estudiante::where('matriculado', true)
            ->whereNotExists(fn ($s) => $s->selectRaw('1')->from('horarios_clase')->whereColumn('horarios_clase.estudiante_id', 'estudiantes.id'))
            ->orderBy('apellidos')->limit(15)->get(), collect());

        $escuelas = $seguro(function () {
            $ruta = config('sga.escuelas_json');
            return is_file($ruta) ? count(json_decode(file_get_contents($ruta), true) ?: []) : 0;
        }, 0);

        return view('admin.horarios.index', [
            'matriculados' => $matriculados, 'conHorario' => $conHorario, 'filas' => $filas,
            'ultima'       => $ultima ? \Carbon\Carbon::parse($ultima) : null,
            'sinHorario'   => $sinHorario, 'escuelas' => $escuelas,
            'periodo'      => Periodo::etiqueta(), 'periodoId' => Periodo::id(),
            'enCurso'      => Periodo::id() ? SincronizacionProgreso::activa((int) Periodo::id()) : false,
        ]);
    }

    /** JSON con el avance, consultado por la pantalla cada pocos segundos. */
    public function progreso()
    {
        $id = (int) Periodo::id();
        $e  = $id ? SincronizacionProgreso::obtener($id) : null;

        if (!$e) {
            return response()->json(['estado' => 'ninguna']);
        }

        $colgada = SincronizacionProgreso::colgada($e);
        $fase = $e['fase'];
        if ($colgada) {
            $fase = $e['estado'] === 'en_cola'
                ? 'El proceso no arrancó. Revisa storage/logs/sincronizacion-horarios.log o ejecuta el comando por consola.'
                : 'No hay avances desde hace más de 15 minutos. Revisa storage/logs/sincronizacion-horarios.log.';
        }
        $total = (int) ($e['total'] ?? 0);

        return response()->json([
            'estado'     => $colgada ? 'detenida' : $e['estado'],
            'fase'       => $fase,
            'origen'     => $e['origen'] ?? null,
            'total'      => $total,
            'hechas'     => (int) ($e['hechas'] ?? 0),
            'porcentaje' => $total > 0 ? min(100, (int) round(($e['hechas'] ?? 0) * 100 / $total)) : 0,
            'lineas'     => $e['lineas'] ?? [],
            'resultado'  => $e['resultado'] ?? null,
            'extra'      => $e['extra'] ?? null,
            'error'      => $e['error'] ?? null,
            'inicio'     => $e['inicio'] ?? null,
            'fin'        => $e['fin'] ?? null,
            'ahora'      => time(),
        ])->header('Cache-Control', 'no-store');
    }

    /** Lanza el comando en segundo plano (sin worker de colas) y deja el avance en caché. */
    public function sincronizar()
    {
        if (!Periodo::id()) {
            return back()->with('error', 'No se encontró el período académico activo.');
        }

        if (SincronizacionProgreso::activa((int) Periodo::id())) {
            return back()->with('error', 'Ya hay una sincronización en curso. Espera a que termine.');
        }

        SincronizacionProgreso::encolar((int) Periodo::id(), auth('web')->id());

        if ($error = LanzadorSincronizacion::lanzar((int) Periodo::id())) {
            SincronizacionProgreso::fallar((int) Periodo::id(), $error);
            return back()->with('error', $error);
        }

        return back()->with('ok', 'Sincronización iniciada. Abajo verás el avance en vivo.');
    }
}
