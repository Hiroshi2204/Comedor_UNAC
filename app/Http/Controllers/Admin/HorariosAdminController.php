<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use App\Support\Periodo;
use Illuminate\Support\Facades\Artisan;
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
            'conCola'      => config('queue.default') !== 'sync',
        ]);
    }

    /** Solo con una cola real (database/redis): con "sync" la petición web se quedaría esperando minutos. */
    public function sincronizar()
    {
        if (config('queue.default') === 'sync') {
            return back()->with('error', 'La sincronización es larga: ejecútala por consola o configura una cola (QUEUE_CONNECTION=database).');
        }
        if (!Periodo::id()) {
            return back()->with('error', 'No se encontró el período académico activo.');
        }

        Artisan::queue('sga:sincronizar-horarios', ['periodo' => Periodo::id()]);

        return back()->with('ok', 'Sincronización en cola. Se ejecutará en cuanto el worker de colas la tome; actualiza esta pantalla en unos minutos.');
    }
}
