<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Becado;
use App\Models\ImportacionObu;
use App\Models\StgBecadoObu;
use App\Services\BecadosCruceService;
use App\Services\BecadosObuImportService;
use App\Support\Periodo;
use Illuminate\Http\Request;

/** Carga del Excel de OBU a staging y cruce con el padrón para crear los becados. */
class BecadosAdminController extends Controller
{
    public function index()
    {
        $pid = Periodo::id();

        $importaciones = ImportacionObu::with('usuario')->orderByDesc('id')->paginate(10);
        // solo el último archivo de cada período está vigente; los demás son histórico
        $vigentes = ImportacionObu::selectRaw('MAX(id) AS id')->groupBy('periodo_id')->pluck('id')->all();

        $ultima = $pid ? ImportacionObu::where('periodo_id', $pid)->orderByDesc('id')->first() : null;

        return view('admin.becados.index', [
            'importaciones' => $importaciones,
            'vigentes'      => $vigentes,
            'ultima'        => $ultima,
            'periodo'       => Periodo::etiqueta(),
            'periodoId'     => $pid,
            'becadosActivos' => $pid ? Becado::where('periodo_academico_id', $pid)->where('estado', 'activo')->count() : 0,
        ]);
    }

    public function importar(Request $r, BecadosObuImportService $servicio)
    {
        $r->validate(
            ['archivo' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240']],
            ['archivo.required' => 'Selecciona el archivo de OBU.', 'archivo.mimes' => 'El archivo debe ser .xlsx, .xls o .csv.', 'archivo.max' => 'El archivo supera los 10 MB.']
        );

        $pid = Periodo::id();
        if (!$pid) {
            return back()->with('error', 'No se encontró el período académico activo. Revisa SGA_PERIODO y la tabla periodos_academicos.');
        }

        $archivo = $r->file('archivo');
        set_time_limit(300);

        try {
            $res = $servicio->importar($archivo->getRealPath(), $pid);
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'No se pudo leer el archivo. Verifica que sea el listado de OBU (con la columna DNI). Detalle: ' . $e->getMessage());
        }

        $registros = array_sum($res['hojas'] ?? []);

        $imp = ImportacionObu::create([
            'periodo_id' => $pid,
            'lote_id'    => $res['lote_id'],
            'archivo'    => mb_substr($archivo->getClientOriginalName(), 0, 190),
            'registros'  => $registros,
            'resumen'    => ['estados' => $res['estados'] ?? [], 'hojas' => $res['hojas'] ?? [], 'sin_encabezado' => $res['sin_encabezado'] ?? []],
            'estado'     => 'cargado',
            'usuario_id' => $r->user("web")->id,
        ]);

        return redirect()->route('admin.becados.show', $imp)
            ->with('ok', "Archivo cargado a staging: {$registros} filas. Revisa las observaciones y pulsa «Procesar» para crear los becados.");
    }

    public function show(Request $r, ImportacionObu $importacion)
    {
        $estado = $r->query('estado');
        $estado = in_array($estado, ['valido', 'observado', 'duplicado', 'procesado', 'no_encontrado', 'por_revisar'], true) ? $estado : null;

        $filas = StgBecadoObu::where('lote_id', $importacion->lote_id)
            ->when($estado, fn ($w, $x) => $w->where('estado', $x))
            ->orderBy('hoja')->orderBy('fila_excel')->paginate(25)->withQueryString();

        $conteo = StgBecadoObu::where('lote_id', $importacion->lote_id)
            ->selectRaw('estado, COUNT(*) AS n')->groupBy('estado')->pluck('n', 'estado');

        $esUltima = !ImportacionObu::where('periodo_id', $importacion->periodo_id)->where('id', '>', $importacion->id)->exists();

        return view('admin.becados.show', compact('importacion', 'filas', 'conteo', 'estado', 'esUltima'));
    }

    public function procesar(Request $r, ImportacionObu $importacion, BecadosCruceService $cruce)
    {
        $esUltima = !ImportacionObu::where('periodo_id', $importacion->periodo_id)->where('id', '>', $importacion->id)->exists();

        if (!$esUltima) {
            return back()->with('error', 'Solo se puede procesar el archivo más reciente del período.');
        }
        if ($importacion->estado === 'procesado') {
            return back()->with('error', 'Este archivo ya fue procesado.');
        }

        set_time_limit(300);

        try {
            $res = $cruce->procesar((int) $importacion->periodo_id);
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'No se pudo procesar: ' . $e->getMessage());
        }

        $importacion->update([
            'estado'       => 'procesado',
            'procesados'   => $res['procesados'],
            'procesado_at' => now(),
        ]);

        $extra = count($res['no_encontrados']) + count($res['por_revisar']);
        return back()->with('ok', "Procesado: {$res['procesados']} becados creados o actualizados."
            . ($extra ? " {$extra} filas necesitan revisión (filtra por «no encontrado» o «por revisar»)." : ''));
    }
}
