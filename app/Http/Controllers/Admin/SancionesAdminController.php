<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inasistencia;
use App\Models\Sancion;
use App\Services\SancionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SancionesAdminController extends Controller
{
    public function index(Request $r)
    {
        $d = $r->validate([
            'estado' => ['nullable', 'in:activa,levantada'],
            'q'      => ['nullable', 'string', 'max:60'],
        ]);
        $q = trim($d['q'] ?? '');

        $sanciones = Sancion::with('estudiante')
            ->when($d['estado'] ?? null, fn ($w, $x) => $w->where('estado', $x))
            ->when($q !== '', fn ($w) => $w->whereHas('estudiante', fn ($e) => $e
                ->where('codigo_universitario', 'like', $q . '%')
                ->orWhere('apellidos', 'like', '%' . $q . '%')
                ->orWhere('nombres', 'like', '%' . $q . '%')))
            // las activas primero
            ->orderByRaw("estado = 'activa' DESC")->orderByDesc('id')
            ->paginate(20)->withQueryString();

        return view('admin.sanciones.index', [
            'sanciones'       => $sanciones,
            'estado'          => $d['estado'] ?? '',
            'q'               => $q,
            'inasistenciasHoy' => Inasistencia::whereDate('fecha', today())->count(),
            'activas'         => Sancion::where('estado', 'activa')->count(),
            'levantadas'      => Sancion::where('estado', 'levantada')->count(),
            'limite'          => (int) config('comedor.inasistencias.limite'),
            'conAuditoria'    => Schema::hasColumn('sanciones', 'levantada_por'),
        ]);
    }

    public function levantar(Request $r, Sancion $sancion, SancionService $servicio)
    {
        $d = $r->validate(['nota' => ['nullable', 'string', 'max:250']]);
        $admin = $r->user("web");

        try {
            $ok = $servicio->levantar($sancion, $admin);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        if (!$ok) {
            return back()->with('error', 'Esa sanción ya no estaba activa.');
        }

        // trazabilidad: quién la levantó y por qué (query builder: no toca updated_at)
        if (Schema::hasColumn('sanciones', 'levantada_por')) {
            DB::table('sanciones')->where('id', $sancion->id)->update([
                'levantada_por'      => $admin->id,
                'nota_levantamiento' => ($d['nota'] ?? null) ?: null,
            ]);
        }

        return back()->with('ok', 'Sanción levantada. El conteo de inasistencias del estudiante vuelve a cero.');
    }
}
