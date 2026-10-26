<?php

namespace App\Console\Commands;

use App\Services\CierreTicketsService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CerrarTickets extends Command
{
    protected $signature = 'comedor:cerrar-tickets {--ahora= : Simula la hora actual, ej. "2026-10-12 12:40"}';
    protected $description = 'Vence tickets, registra inasistencias, aplica y levanta sanciones';

    public function handle(CierreTicketsService $servicio): int
    {
        $r = $servicio->cerrar($this->option('ahora') ? Carbon::parse($this->option('ahora')) : now());
        $this->info("Reprogramados a la última franja: {$r['reprogramados']} | Vencidos: {$r['vencidos']} | No recogidos: {$r['no_recogidos']} | Sancionados: {$r['sancionados']} | Sanciones levantadas: {$r['levantadas']}");
        return self::SUCCESS;
    }
}
