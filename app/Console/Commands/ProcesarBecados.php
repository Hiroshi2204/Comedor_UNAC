<?php

namespace App\Console\Commands;

use App\Services\BecadosCruceService;
use Illuminate\Console\Command;

class ProcesarBecados extends Command
{
    protected $signature = 'becados:procesar {periodo : ID del periodo académico}';
    protected $description = 'Cruza el último lote de staging con estudiantes y crea los becados';

    public function handle(BecadosCruceService $servicio): int
    {
        $r = $servicio->procesar((int) $this->argument('periodo'));

        $this->info("Lote {$r['lote_id']} | Becados creados o actualizados: {$r['procesados']}");

        if ($r['no_encontrados']) {
            $this->warn('No figuran en el padrón:');
            $this->table(['Hoja', 'Fila', 'Nombre'], $r['no_encontrados']);
        }
        if ($r['por_revisar']) {
            $this->warn('Nombre distinto, requiere revisión:');
            $this->table(['Hoja', 'Fila', 'Excel', 'Padrón'], $r['por_revisar']);
        }
        return self::SUCCESS;
    }
}