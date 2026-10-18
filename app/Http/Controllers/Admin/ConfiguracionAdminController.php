<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use App\Models\Ticket;
use App\Support\Ajustes;
use App\Support\Periodo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Parámetros operativos editables y vista de períodos académicos (el activo sale de SGA_PERIODO). */
class ConfiguracionAdminController extends Controller
{
    public function index()
    {
        $guardados = Ajustes::guardados();
        $campos = collect(Ajustes::CAMPOS)->map(fn ($c, $clave) => (object) [
            'clave' => $clave, 'slug' => str_replace('.', '__', $clave),
            'etiqueta' => $c[0], 'ayuda' => $c[1], 'tipo' => $c[2], 'min' => $c[3], 'max' => $c[4], 'paso' => $c[5],
            'valor' => config($clave), 'personalizado' => array_key_exists($clave, $guardados),
        ])->values();

        $activo = Periodo::id();
        $estu = Estudiante::selectRaw('periodo_academico_id, COUNT(*) AS n')->groupBy('periodo_academico_id')->pluck('n', 'periodo_academico_id');
        $tick = Ticket::selectRaw('periodo_academico_id, COUNT(*) AS n')->groupBy('periodo_academico_id')->pluck('n', 'periodo_academico_id');

        $periodos = collect();
        try {
            $periodos = DB::table('periodos_academicos')->orderByDesc('id')->get()->map(function ($p) use ($activo, $estu, $tick) {
                $f = (array) $p;
                return (object) [
                    'etiqueta'    => Periodo::campo($f, ['codigo', 'nombre', 'periodo', 'descripcion', 'anio_ciclo']) ?? ('#' . $f['id']),
                    'inicio'      => Periodo::campo($f, ['fecha_inicio', 'inicio']),
                    'fin'         => Periodo::campo($f, ['fecha_fin', 'fin']),
                    'activo'      => (int) $f['id'] === $activo,
                    'estudiantes' => (int) ($estu[$f['id']] ?? 0),
                    'tickets'     => (int) ($tick[$f['id']] ?? 0),
                ];
            });
        } catch (\Throwable $e) { report($e); }

        return view('admin.configuracion', ['campos' => $campos, 'periodos' => $periodos, 'periodoEtiqueta' => Periodo::etiqueta()]);
    }

    public function guardar(Request $r)
    {
        $valores = [];
        $errores = [];

        foreach (Ajustes::CAMPOS as $clave => [$etiqueta, , $tipo, $min, $max]) {
            $campo = 'ajuste.' . str_replace('.', '__', $clave);
            $bruto = $r->input($campo);
            if ($bruto === null || $bruto === '') {
                continue;
            }
            $bruto = trim((string) $bruto);
            $entero = $tipo === 'int';
            if (!is_numeric($bruto) || $bruto < $min || $bruto > $max || ($entero && (string) (int) $bruto !== $bruto)) {
                $errores[$campo] = "{$etiqueta}: debe ser " . ($entero ? 'un entero' : 'un número') . " entre {$min} y {$max}.";
                continue;
            }
            $valores[$clave] = $bruto;
        }

        if ($errores) {
            return back()->withErrors($errores)->withInput();
        }

        Ajustes::guardar($valores);

        return redirect()->route('admin.configuracion')->with('ok', 'Parámetros guardados. Rigen desde ahora para los nuevos tickets.');
    }

    public function restablecer()
    {
        Ajustes::restablecer();

        return redirect()->route('admin.configuracion')->with('ok', 'Parámetros restablecidos a los valores de config/comedor.php.');
    }
}
