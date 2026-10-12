<?php

namespace App\Console\Commands;

use App\Exceptions\TicketNoDisponible;
use App\Models\Estudiante;
use App\Models\VentanaAtencion;
use App\Services\SolicitudTicketService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SolicitarTicket extends Command
{
    protected $signature = 'comedor:solicitar {codigo} {servicio?}
        {--fecha= : Fecha del ticket (por defecto hoy; mañana = reserva)}
        {--franja= : Hora de inicio de la franja a reservar, ej. 07:30}
        {--ahora= : Simula la hora actual, ej. "2026-10-12 19:00"}';
    protected $description = 'Muestra la disponibilidad de un estudiante o le emite un ticket (para pruebas)';

    public function handle(SolicitudTicketService $servicio): int
    {
        $est = Estudiante::where('codigo_universitario', $this->argument('codigo'))->first();
        if (!$est) {
            $this->error('Estudiante no encontrado.');
            return self::FAILURE;
        }

        $ahora = $this->option('ahora') ? Carbon::parse($this->option('ahora')) : now();
        $fecha = $this->option('fecha') ? Carbon::parse($this->option('fecha')) : $ahora->copy();

        if ($this->argument('servicio')) {
            $ventanaId = null;
            if ($this->option('franja')) {
                $ventanaId = VentanaAtencion::where('servicio', $this->argument('servicio'))
                    ->where('hora_inicio', $this->option('franja') . ':00')->value('id');
            }
            try {
                $t = $servicio->solicitar($est, $this->argument('servicio'), $ventanaId, $fecha, $ahora);
                $this->info("Ticket emitido: {$t->servicio} {$t->fecha->toDateString()} | {$t->tipo} | vence {$t->vence_at->format('Y-m-d H:i')} | QR: {$t->codigo_qr}");
            } catch (TicketNoDisponible $e) {
                $this->warn($e->getMessage());
            }
            return self::SUCCESS;
        }

        $d = $servicio->disponibilidad($est, $fecha, $ahora);
        if (!$d['elegible']) {
            $this->warn($d['motivo']);
            return self::SUCCESS;
        }

        $this->line("Modo: {$d['modo']} | {$d['tipo']} | " . $fecha->toDateString());
        $this->table(['Servicio', 'Estado', 'Se puede pedir', 'Cupos'], collect($d['servicios'])->map(fn ($s, $n) => [
            ucfirst($n), $s['estado'], "{$s['abre']} a {$s['cierra']}", $s['cupos'],
        ])->values()->all());

        foreach ($d['servicios'] as $nombre => $s) {
            if ($s['franjas']) {
                $this->info('Franjas de ' . $nombre . ':');
                $this->table(['Franja', 'Cupos'], array_map(fn ($f) => ["{$f['inicio']}-{$f['fin']}", $f['cupos']], $s['franjas']));
            }
        }
        return self::SUCCESS;
    }
}