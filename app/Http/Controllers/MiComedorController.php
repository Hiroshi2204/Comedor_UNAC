<?php

namespace App\Http\Controllers;

use App\Exceptions\TicketNoDisponible;
use App\Models\Ticket;
use App\Services\CancelacionTicketService;
use App\Services\SolicitudTicketService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\HorarioClase;
use App\Models\Inasistencia;
use App\Models\Sancion;

class MiComedorController extends Controller
{
    public function __construct(
        private SolicitudTicketService $solicitudes,
        private CancelacionTicketService $cancelaciones,
    ) {}

    public function index(Request $r)
    {
        $e = $r->attributes->get('estudiante');
        $hoy = today();

        $dHoy    = $this->solicitudes->disponibilidad($e, $hoy);                 // ticket al momento
        $dManana = $this->solicitudes->disponibilidad($e, $hoy->copy()->addDay()); // reserva (solo becados)

        $hayTicketActivo = collect($dHoy['servicios'])->contains(
            fn($s) => $s['ticket'] && in_array($s['ticket']->estado, ['generado', 'validado'], true)
        );

        $limite = (int) config('comedor.inasistencias.limite', 3);
        $sancionActiva = $e->sancionActiva()->exists();   // si en tu modelo es una propiedad, usa: (bool) $e->sancionActiva

        // el conteo reinicia cuando el administrador levanta la sanción (parte 4)
        $levantada = Sancion::where('estudiante_id', $e->id)->where('estado', 'levantada')->latest('updated_at')->first();
        $q = Inasistencia::where('estudiante_id', $e->id);
        if ($levantada) {
            $q->where('created_at', '>', $levantada->updated_at);
        }
        $inasistencias = $sancionActiva ? $limite : $q->count();

        $dia = [1 => 'lunes', 2 => 'martes', 3 => 'miercoles', 4 => 'jueves', 5 => 'viernes', 6 => 'sabado'][$hoy->dayOfWeekIso] ?? null;
        $clases = $dia ? HorarioClase::where('estudiante_id', $e->id)
            ->where('periodo_academico_id', $e->periodo_academico_id)
            ->where('dia_semana', $dia)
            ->where(fn($q) => $q->whereNull('fecha_inicio')->orWhere('fecha_inicio', '<=', $hoy->toDateString()))
            ->where(fn($q) => $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $hoy->toDateString()))
            ->orderBy('hora_inicio')
            ->get(['curso', 'hora_inicio', 'hora_fin', 'aula'])
            ->map(fn($c) => [
                'inicio' => substr($c->hora_inicio, 0, 5),
                'fin'    => substr($c->hora_fin, 0, 5),
                'curso'  => $c->curso,
                'aula'   => $c->aula,
            ])->all() : [];

        return view('estudiante.inicio', [
            'estudiante'    => $e,
            'esBecado'      => $e->becaActiva()->exists(),
            'hoy'           => $hoy,
            'manana'        => $hoy->copy()->addDay(),
            'dHoy'          => $dHoy,
            'dManana'       => $dManana,
            'refrescar'     => $hayTicketActivo,
            'clases'        => $clases,
            'inasistencias' => $inasistencias,
            'sancionActiva' => $sancionActiva,
            'periodo'       => preg_replace('/^(\d{4})(.+)$/', '$1-$2', (string) config('sga.periodo', '')),
        ]);
    }

    public function solicitar(Request $r)
    {
        $e = $r->attributes->get('estudiante');

        $d = $r->validate([
            'servicio'   => ['required', 'in:desayuno,almuerzo,cena'],
            'fecha'      => ['required', 'date_format:Y-m-d'],
            'ventana_id' => ['nullable', 'integer'],
        ]);

        try {
            $this->solicitudes->solicitar($e, $d['servicio'], $d['ventana_id'] ?? null, Carbon::parse($d['fecha']));
        } catch (TicketNoDisponible $ex) {
            return redirect()->route('estudiante.inicio')->withErrors(['ticket' => $ex->getMessage()]);
        }

        return redirect()->route('estudiante.inicio')->with('ok', 'Tu ticket de ' . $d['servicio'] . ' está listo.');
    }

    public function cancelar(Request $r, Ticket $ticket)
    {
        $e = $r->attributes->get('estudiante');

        try {
            $this->cancelaciones->cancelar($ticket, $e, $r->input('motivo'));
        } catch (TicketNoDisponible $ex) {
            return redirect()->route('estudiante.inicio')->withErrors(['ticket' => $ex->getMessage()]);
        }

        return redirect()->route('estudiante.inicio')
            ->with('ok', 'Ticket cancelado. No podrás pedir otro de ese servicio ese día.');
    }
}
