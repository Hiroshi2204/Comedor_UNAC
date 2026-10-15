<?php

namespace App\Http\Controllers;

use App\Exceptions\TicketNoDisponible;
use App\Models\Ticket;
use App\Services\CancelacionTicketService;
use App\Services\SolicitudTicketService;
use Carbon\Carbon;
use Illuminate\Http\Request;

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

        return view('estudiante.inicio', [
            'estudiante'      => $e,
            'esBecado'        => $e->becaActiva()->exists(),
            'hoy'             => $hoy,
            'manana'          => $hoy->copy()->addDay(),
            'dHoy'            => $dHoy,
            'dManana'         => $dManana,
            'refrescar'       => $hayTicketActivo, // el estado cambia cuando pasa por la puerta o recoge
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
