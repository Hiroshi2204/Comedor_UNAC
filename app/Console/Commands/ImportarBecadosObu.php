<?php

namespace App\Console\Commands;

use App\Services\BecadosObuImportService;
use Illuminate\Console\Command;

class ImportarBecadosObu extends Command
{
    protected $signature = 'becados:importar {archivo : Ruta del .xlsx de OBU} {periodo : ID del periodo académico}';
    protected $description = 'Carga el Excel de becados de OBU (una hoja por escuela) al staging';

    public function handle(BecadosObuImportService $servicio): int
    {
        $ruta = $this->argument('archivo');
        if (!is_file($ruta)) {
            $this->error("No existe el archivo: {$ruta}");
            return self::FAILURE;
        }

        $r = $servicio->importar($ruta, (int) $this->argument('periodo'));

        $this->info("Lote {$r['lote_id']}");
        $this->table(['Hoja', 'Filas'], collect($r['hojas'])->map(fn ($n, $h) => [$h, $n])->values()->all());
        $this->line('Total: ' . array_sum($r['hojas']) . ' | ' . json_encode($r['estados']));

        if ($r['sin_encabezado']) {
            $this->warn('Hojas sin encabezado "DNI": ' . implode(', ', $r['sin_encabezado']));
        }
        if ($r['observados']) {
            $this->table(['Hoja', 'Fila', 'Nombre', 'Observación'], $r['observados']);
        }

        return self::SUCCESS;
    }
}