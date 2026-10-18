<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Becado;
use App\Models\Estudiante;
use App\Models\Inasistencia;
use App\Models\Sancion;
use App\Models\Ticket;
use Illuminate\Http\Request;

/** Búsqueda de estudiantes y su ficha en el comedor. */
class EstudiantesAdminController extends Controller
{
    public function index(Request $r)
    {
        $d = $r->validate([
            'q'      => ['nullable', 'string', 'max:60'],
            'filtro' => ['nullable', 'in:becados,no_becados,sancionados'],
        ]);
        $q = trim($d['q'] ?? '');

        $becaSub = Becado::query()->selectRaw('1')->whereColumn('becados.estudiante_id', 'estudiantes.id')
            ->where('estado', 'activo')->limit(1);
        $sancSub = Sancion::query()->selectRaw('1')->whereColumn('sanciones.estudiante_id', 'estudiantes.id')
            ->where('estado', 'activa')->limit(1);

        $estudiantes = Estudiante::query()
            ->select('estudiantes.*')
            ->selectSub($becaSub, 'es_becado')
            ->selectSub($sancSub, 'sancion_activa')
            ->selectSub(Ticket::query()->selectRaw('COUNT(*)')->whereColumn('tickets.estudiante_id', 'estudiantes.id'), 'n_tickets')
            ->selectSub(Inasistencia::query()->selectRaw('COUNT(*)')->whereColumn('inasistencias.estudiante_id', 'estudiantes.id'), 'n_inasistencias')
            ->when($q !== '', fn ($w) => $w->where(function ($x) use ($q) {
                $x->where('codigo_universitario', 'like', $q . '%')
                  ->orWhere('dni', $q)
                  ->orWhere('apellidos', 'like', '%' . $q . '%')
                  ->orWhere('nombres', 'like', '%' . $q . '%');
            }))
            ->when(($d['filtro'] ?? null) === 'becados',     fn ($w) => $w->whereExists(fn ($s) => $s->selectRaw('1')->from('becados')->whereColumn('becados.estudiante_id', 'estudiantes.id')->where('becados.estado', 'activo')))
            ->when(($d['filtro'] ?? null) === 'no_becados',  fn ($w) => $w->whereNotExists(fn ($s) => $s->selectRaw('1')->from('becados')->whereColumn('becados.estudiante_id', 'estudiantes.id')->where('becados.estado', 'activo')))
            ->when(($d['filtro'] ?? null) === 'sancionados', fn ($w) => $w->whereExists(fn ($s) => $s->selectRaw('1')->from('sanciones')->whereColumn('sanciones.estudiante_id', 'estudiantes.id')->where('sanciones.estado', 'activa')))
            ->orderBy('apellidos')->orderBy('nombres')
            ->paginate(20)->withQueryString();

        return view('admin.estudiantes.index', ['estudiantes' => $estudiantes, 'q' => $q, 'filtro' => $d['filtro'] ?? '']);
    }

    public function show(Estudiante $estudiante)
    {
        $conteo = Ticket::where('estudiante_id', $estudiante->id)
            ->selectRaw('estado, COUNT(*) AS n')->groupBy('estado')->pluck('n', 'estado');

        $entregados = (int) ($conteo['recogido'] ?? 0);
        $perdidos   = (int) ($conteo['vencido'] ?? 0) + (int) ($conteo['no_recogido'] ?? 0);

        return view('admin.estudiantes.show', [
            'e'               => $estudiante,
            'becas'           => Becado::where('estudiante_id', $estudiante->id)->orderByDesc('id')->get(),
            'sanciones'       => Sancion::where('estudiante_id', $estudiante->id)->orderByDesc('id')->get(),
            'tickets'         => Ticket::with('ventanaAtencion')->where('estudiante_id', $estudiante->id)->orderByDesc('fecha')->orderByDesc('id')->limit(15)->get(),
            'inasistencias'   => Inasistencia::where('estudiante_id', $estudiante->id)->count(),
            'conteo'          => $conteo,
            'aprovechamiento' => ($entregados + $perdidos) > 0 ? (int) round($entregados * 100 / ($entregados + $perdidos)) : null,
            'limite'          => (int) config('comedor.inasistencias.limite'),
        ]);
    }
}
