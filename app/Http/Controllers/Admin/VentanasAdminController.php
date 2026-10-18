<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\VentanaAtencion;
use App\Support\Fmt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Franjas de atención: capacidad y activación. */
class VentanasAdminController extends Controller
{
    private const SERVICIOS = ['desayuno', 'almuerzo', 'cena'];

    public function index()
    {
        $ocupadas = Ticket::whereDate('fecha', today())->where('estado', '!=', 'cancelado')
            ->selectRaw('ventana_atencion_id, COUNT(*) AS n')->groupBy('ventana_atencion_id')->pluck('n', 'ventana_atencion_id');

        $grupos = VentanaAtencion::orderBy('hora_inicio')->get()->groupBy('servicio');

        $servicios = collect(self::SERVICIOS)->map(function ($s) use ($grupos, $ocupadas) {
            $v = $grupos->get($s, collect());
            $stock = (int) config("comedor.servicios.$s.stock");
            $suma = (int) $v->where('activo', true)->sum('capacidad_maxima');
            return (object) [
                'clave' => $s, 'nombre' => ucfirst($s), 'ventanas' => $v, 'stock' => $stock, 'suma' => $suma,
                'cuadra' => $suma === $stock, 'ocupadas' => $ocupadas,
                'horario' => config("comedor.servicios.$s.inicio") . ' – ' . config("comedor.servicios.$s.fin"),
            ];
        });

        return view('admin.ventanas.index', compact('servicios'));
    }

    public function actualizar(Request $r, string $servicio)
    {
        abort_unless(in_array($servicio, self::SERVICIOS, true), 404);

        $d = $r->validate([
            'capacidad'   => ['required', 'array'],
            'capacidad.*' => ['required', 'integer', 'min:0', 'max:5000'],
            'activo'      => ['nullable', 'array'],
            'activo.*'    => ['integer'],
        ]);

        $ids = VentanaAtencion::where('servicio', $servicio)->pluck('id')->all();
        $activos = array_map('intval', array_keys($d['activo'] ?? []));

        DB::transaction(function () use ($d, $ids, $activos) {
            foreach ($d['capacidad'] as $id => $cap) {
                if (!in_array((int) $id, $ids, true)) {
                    continue;   // ignora ids ajenos a este servicio
                }
                VentanaAtencion::where('id', $id)->update([
                    'capacidad_maxima' => (int) $cap,
                    'activo'           => in_array((int) $id, $activos, true),
                ]);
            }
        });

        $suma = (int) VentanaAtencion::where('servicio', $servicio)->where('activo', true)->sum('capacidad_maxima');
        $stock = (int) config("comedor.servicios.$servicio.stock");

        return back()->with('ok', ucfirst($servicio) . ' actualizado.' . ($suma !== $stock ? " Ojo: la capacidad suma {$suma} y el stock configurado es {$stock}." : ''));
    }
}
