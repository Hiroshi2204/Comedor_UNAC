<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\RelojDemo;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;

/** Reloj de demostración: simula la fecha y hora para mostrar el sistema fuera de los horarios de servicio. */
class DemoAdminController extends Controller
{
    public function __construct()
    {
        abort_unless(RelojDemo::habilitado(), 404);
    }

    public function index()
    {
        $base = now(); // ya simulada si está activo
        $presets = [];
        foreach ([['Desayuno', '06:55'], ['Almuerzo', '11:55'], ['Cena', '17:55']] as [$nombre, $hm]) {
            $presets[] = ['nombre' => $nombre, 'hora' => $hm, 'valor' => $base->copy()->setTimeFromTimeString($hm)->format('Y-m-d\TH:i')];
        }
        $comando = config('comedor.demo.comando_cierre', 'tickets:cerrar');

        return view('admin.demo', [
            'activo'   => RelojDemo::activo(),
            'real'     => RelojDemo::real(),
            'simulada' => $base,
            'presets'  => $presets,
            'comando'  => $comando,
            'hayCierre' => array_key_exists($comando, Artisan::all()),
        ]);
    }

    public function fijar(Request $r)
    {
        $data = $r->validate(['fecha_hora' => ['required', 'date_format:Y-m-d\TH:i']]);
        RelojDemo::fijar(Carbon::createFromFormat('Y-m-d\TH:i', $data['fecha_hora'], config('app.timezone')));

        return back()->with('ok', 'Reloj simulado: ' . now()->format('d/m/Y H:i') . '. Sigue avanzando desde esa hora.');
    }

    public function avanzar(Request $r)
    {
        $data = $r->validate(['minutos' => ['required', 'integer', 'min:1', 'max:1440']]);
        RelojDemo::avanzar((int) $data['minutos']);

        return back()->with('ok', 'Reloj adelantado a ' . now()->format('d/m/Y H:i') . '.');
    }

    public function desactivar()
    {
        RelojDemo::desactivar();

        return back()->with('ok', 'Reloj real restaurado: ' . now()->format('d/m/Y H:i') . '.');
    }

    /** Ejecuta el comando de cierre para que los tickets vencidos se registren como inasistencia. */
    public function cerrar()
    {
        $cmd = config('comedor.demo.comando_cierre', 'tickets:cerrar');
        if (!array_key_exists($cmd, Artisan::all())) {
            return back()->with('error', "No existe el comando {$cmd}. Defínelo en DEMO_COMANDO_CIERRE.");
        }
        Artisan::call($cmd);
        $salida = trim(Artisan::output());

        return back()->with('ok', 'Cierre ejecutado' . ($salida !== '' ? ': ' . mb_substr($salida, 0, 300) : '.'));
    }
}
