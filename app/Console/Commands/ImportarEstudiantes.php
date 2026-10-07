<?php

namespace App\Console\Commands;

use App\Models\Estudiante;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportarEstudiantes extends Command
{
    protected $signature = 'estudiantes:importar {archivo : CSV de matriculados} {periodo : ID del periodo académico}';
    protected $description = 'Carga o actualiza el padrón de matriculados en estudiantes';

    public function handle(): int
    {
        $ruta = $this->argument('archivo');
        if (!is_file($ruta)) {
            $this->error("No existe el archivo: {$ruta}");
            return self::FAILURE;
        }

        $f = fopen($ruta, 'r');
        $cabecera = array_map(
            fn ($c) => trim(preg_replace('/^\xEF\xBB\xBF/', '', $c)),
            fgetcsv($f)
        );
        $ix = array_flip($cabecera);

        foreach (['CÓDIGO', 'NÚM. DOC.', 'PATERNO', 'MATERNO', 'NOMBRES', 'ESCUELA', 'CORREO INSTITUCIONAL'] as $col) {
            if (!isset($ix[$col])) {
                $this->error("Falta la columna: {$col}");
                return self::FAILURE;
            }
        }

        $periodoId = (int) $this->argument('periodo');
        $n = 0;

        DB::transaction(function () use ($f, $ix, $periodoId, &$n) {
            while (($r = fgetcsv($f)) !== false) {
                $v = function (string $col) use ($r, $ix) {
                    $x = trim($r[$ix[$col]] ?? '');
                    return ($x === '' || strtoupper($x) === 'NULL') ? null : $x;
                };

                if (!$v('CÓDIGO')) continue;

                Estudiante::updateOrCreate(
                    ['codigo_universitario' => $v('CÓDIGO')],
                    [
                        'dni'                       => $v('NÚM. DOC.'),
                        'nombres'                   => $v('NOMBRES'),
                        'apellidos'                 => trim(($v('PATERNO') ?? '') . ' ' . ($v('MATERNO') ?? '')),
                        'email_institucional'       => $v('CORREO INSTITUCIONAL'),
                        'escuela_profesional'       => $v('ESCUELA'),
                        'periodo_academico_id'      => $periodoId,
                        'matriculado'               => true,
                        'ultima_sincronizacion_sga' => now(),
                    ]
                );
                $n++;
            }
        });
        fclose($f);

        $this->info("Estudiantes cargados o actualizados: {$n}");
        return self::SUCCESS;
    }
}