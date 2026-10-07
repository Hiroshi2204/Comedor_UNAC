<?php

namespace App\Services;

use App\Models\StgBecadoObu;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class BecadosObuImportService
{
    public function importar(string $ruta, int $periodoId): array
    {
        $reader = IOFactory::createReaderForFile($ruta);
        $reader->setReadDataOnly(true);
        $libro = $reader->load($ruta);

        $lote = (string) Str::uuid();
        $ahora = now();
        $vistos = [];
        $filasBD = [];
        $resumen = ['lote_id' => $lote, 'hojas' => [], 'sin_encabezado' => []];

        foreach ($libro->getAllSheets() as $hoja) {
            $titulo = $hoja->getTitle();
            $filas = $hoja->toArray(null, true, false, false); // valores crudos, índice 0
            $pos = $this->ubicarEncabezado($filas);

            if (!$pos) {
                $resumen['sin_encabezado'][] = $titulo;
                continue;
            }

            [$filaHeader, $colDni] = $pos;
            $n = 0;

            for ($i = $filaHeader + 1; $i < count($filas); $i++) {
                $nombre = $this->texto($filas[$i][$colDni - 1] ?? null);
                $dniRaw = $this->texto($filas[$i][$colDni] ?? null);
                $codRaw = $this->texto($filas[$i][$colDni + 1] ?? null);

                if ($dniRaw === '' && $codRaw === '') {
                    continue; // fila vacía o pie de página (fecha, firmas)
                }

                [$dni, $codigo, $obs] = $this->normalizar($nombre, $dniRaw, $codRaw);
                $estado = $obs ? 'observado' : 'valido';

                $clave = $codigo ?? $dni;
                if ($clave !== null) {
                    if (isset($vistos[$clave])) {
                        $estado = 'duplicado';
                        $obs[] = "Repetido en {$vistos[$clave]}";
                    } else {
                        $vistos[$clave] = "{$titulo} fila " . ($i + 1);
                    }
                }

                $filasBD[] = [
                    'periodo_id'      => $periodoId,
                    'lote_id'         => $lote,
                    'hoja'            => Str::limit($titulo, 50, ''),
                    'escuela'         => Str::limit(trim($titulo), 50, ''),
                    'fila_excel'      => $i + 1,
                    'nombre_completo' => Str::limit($nombre, 150, ''),
                    'dni_raw'         => Str::limit($dniRaw, 20, ''),
                    'codigo_raw'      => Str::limit($codRaw, 20, ''),
                    'dni'             => $dni,
                    'codigo'          => $codigo,
                    'estado'          => $estado,
                    'observacion'     => $obs ? Str::limit(implode('; ', $obs), 250, '') : null,
                    'created_at'      => $ahora,
                    'updated_at'      => $ahora,
                ];
                $n++;
            }
            $resumen['hojas'][$titulo] = $n;
        }

        DB::transaction(function () use ($filasBD) {
            foreach (array_chunk($filasBD, 500) as $bloque) {
                StgBecadoObu::insert($bloque);
            }
        });

        $resumen['estados'] = StgBecadoObu::where('lote_id', $lote)
            ->selectRaw('estado, count(*) as n')->groupBy('estado')->pluck('n', 'estado')->all();

        $resumen['observados'] = StgBecadoObu::where('lote_id', $lote)
            ->where('estado', '!=', 'valido')->orderBy('id')
            ->get(['hoja', 'fila_excel', 'nombre_completo', 'observacion'])->toArray();

        return $resumen;
    }

    /** Busca la celda "DNI" en las primeras filas: el encabezado cambia de fila y columna entre hojas. */
    private function ubicarEncabezado(array $filas): ?array
    {
        foreach (array_slice($filas, 0, 25, true) as $i => $fila) {
            foreach ($fila as $j => $celda) {
                if ($j >= 1 && is_string($celda) && mb_strtoupper(trim($celda)) === 'DNI') {
                    return [$i, $j];
                }
            }
        }
        return null;
    }

    private function texto(mixed $v): string
    {
        if ($v === null) return '';
        if (is_int($v) || is_float($v)) return (string) (int) round($v); // evita 7.37E+6
        return trim(preg_replace('/\s+/u', ' ', (string) $v));
    }

    private function normalizar(string $nombre, string $dniRaw, string $codRaw): array
    {
        $obs = [];
        if ($nombre === '') $obs[] = 'Sin nombre';

        $dni = null;
        $d = preg_replace('/\D/', '', $dniRaw);
        if ($d === '') {
            $obs[] = 'Sin DNI';
        } else {
            if ($d !== $dniRaw) $obs[] = 'DNI con caracteres no numéricos';
            if (strlen($d) === 8) {
                $dni = $d;
            } elseif (strlen($d) === 7) {
                $dni = str_pad($d, 8, '0', STR_PAD_LEFT);
                $obs[] = 'DNI de 7 dígitos: se completó con 0 a la izquierda';
            } else {
                $obs[] = 'DNI de ' . strlen($d) . ' dígitos';
            }
        }

        $codigo = null;
        if ($codRaw === '') {
            $obs[] = 'Sin código';
        } elseif (!ctype_digit($codRaw)) {
            $obs[] = 'Código no numérico';
        } else {
            $codigo = $codRaw;
            if (strlen($codRaw) !== 10) $obs[] = 'Código de ' . strlen($codRaw) . ' dígitos';
        }

        return [$dni, $codigo, $obs];
    }
}