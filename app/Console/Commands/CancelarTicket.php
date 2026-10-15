<?php

namespace App\Console\Commands;

use App\Exceptions\TicketNoDisponible;
use App\Models\Ticket;
use App\Services\CancelacionTicketService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CancelarTicket extends Command
{
    protected $signature = 'comedor:cancelar {qr : Código QR del ticket}
        {--motivo= : Motivo de la cancelación}
        {--ahora= : Simula la hora actual, ej. "2026-10-12 12:20"}';
    protected $description = 'Cancela un ticket como si lo hiciera su dueño (para pruebas)';

    public function handle(CancelacionTicketService $servicio): int
    {
        $t = Ticket::with('estudiante')->where('codigo_qr', $this->argument('qr'))->first();
        if (!$t) {
            $this->error('No existe un ticket con ese QR.');
            return self::FAILURE;
        }

        $ahora = $this->option('ahora') ? Carbon::parse($this->option('ahora')) : now();

        try {
            $servicio->cancelar($t, $t->estudiante, $this->option('motivo'), $ahora);
            $this->info("Ticket de {$t->servicio} ({$t->fecha->toDateString()}) cancelado.");
        } catch (TicketNoDisponible $e) {
            $this->warn($e->getMessage());
        }
        return self::SUCCESS;
    }
}