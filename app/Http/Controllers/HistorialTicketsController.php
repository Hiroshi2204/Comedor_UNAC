<?php

namespace App\Http\Controllers;

use App\Models\Sancion;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HistorialTicketsController extends Controller
{
    private const ESTADOS = ['generado', 'validado', 'recogido', 'vencido', 'no_recogido', 'cancelado'];

    public function index(Request $r)
    {
        $e = $r->attributes->get('estudiante');   // sale de la sesión, nunca de la URL

        $v = Validator::make($r->query(), [
            'servicio' => ['nullable', 'in:desayuno,almuerzo,cena'],
            'estado'   => ['nullable', 'in:' . implode(',', self::ESTADOS)],
            'desde'    => ['nullable', 'date_format:Y-m-d'],
            'hasta'    => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
        ]);

        // filtros inválidos se ignoran (con un aviso) en vez de romper la pantalla
        $aviso   = $v->fails() ? 'Algún filtro no es válido y se ignoró. Revisa las fechas.' : null;
        $filtros = $v->fails() ? [] : $v->validated();

        $tickets = Ticket::with('ventanaAtencion')
            ->where('estudiante_id', $e->id)
            ->when($filtros['servicio'] ?? null, fn($q, $x) => $q->where('servicio', $x))
            ->when($filtros['estado'] ?? null,   fn($q, $x) => $q->where('estado', $x))
            ->when($filtros['desde'] ?? null,    fn($q, $x) => $q->where('fecha', '>=', $x))
            ->when($filtros['hasta'] ?? null,    fn($q, $x) => $q->where('fecha', '<=', $x))
            ->orderByDesc('fecha')->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        // resumen de toda su historia (no depende de los filtros)
        $conteo = Ticket::where('estudiante_id', $e->id)
            ->selectRaw('estado, COUNT(*) AS n')->groupBy('estado')->pluck('n', 'estado');

        $entregados = (int) ($conteo['recogido'] ?? 0);
        $perdidos   = (int) ($conteo['vencido'] ?? 0) + (int) ($conteo['no_recogido'] ?? 0);
        $aprovechamiento = ($entregados + $perdidos) > 0
            ? (int) round($entregados * 100 / ($entregados + $perdidos))
            : null;

        return view('estudiante.historial', [
            'estudiante'      => $e,
            'tickets'         => $tickets,
            'filtros'         => $filtros,
            'aviso'           => $aviso,
            'conteo'          => $conteo,
            'aprovechamiento' => $aprovechamiento,
            'sanciones'       => Sancion::where('estudiante_id', $e->id)->latest('id')->get(),
        ]);
    }
}
