<?php

namespace App\Services;

use App\Models\Becado;
use App\Models\Estudiante;
use App\Models\StgBecadoObu;
use Illuminate\Support\Str;

class BecadosCruceService
{
    public function procesar(int $periodoId): array
    {
        $lote = StgBecadoObu::where('periodo_id', $periodoId)->latest('id')->value('lote_id');
        if (!$lote) {
            throw new \RuntimeException('No hay lotes cargados para ese periodo.');
        }

        $res = ['lote_id' => $lote, 'procesados' => 0, 'no_encontrados' => [], 'por_revisar' => []];

        StgBecadoObu::where('lote_id', $lote)
            ->whereIn('estado', ['valido', 'observado'])   // los 'duplicado' se omiten
            ->chunkById(200, function ($filas) use (&$res, $periodoId) {
                foreach ($filas as $s) {
                    $est = $this->buscarEstudiante($s);

                    if (!$est) {
                        $this->marcar($s, 'no_encontrado', 'No figura en el padrón de matriculados');
                        $res['no_encontrados'][] = [$s->hoja, $s->fila_excel, $s->nombre_completo];
                        continue;
                    }

                    if (!$this->nombreCoincide($s->nombre_completo, $est)) {
                        $this->marcar($s, 'por_revisar', "Nombre distinto al del padrón: {$est->nombreCompleto()}");
                        $res['por_revisar'][] = [$s->hoja, $s->fila_excel, $s->nombre_completo, $est->nombreCompleto()];
                        continue;
                    }

                    $b = Becado::firstOrNew([
                        'estudiante_id'        => $est->id,
                        'periodo_academico_id' => $periodoId,
                    ]);
                    if (!$b->exists) {
                        $b->fill(['tipo_beca' => 'ALIMENTOS', 'estado' => 'activo', 'fecha_registro' => today()]);
                    }
                    $b->stg_becado_id = $s->id;   // no se toca 'estado' si ya existía (p. ej. suspendido)
                    $b->save();

                    $nota = $s->codigo !== $est->codigo_universitario
                        ? "Código corregido por DNI: {$est->codigo_universitario}" : null;
                    $this->marcar($s, 'procesado', $nota);
                    $res['procesados']++;
                }
            });

        return $res;
    }

    private function buscarEstudiante(StgBecadoObu $s): ?Estudiante
    {
        if ($s->codigo && ($e = Estudiante::where('codigo_universitario', $s->codigo)->first())) {
            return $e;
        }
        $docs = array_unique(array_filter([$s->dni, preg_replace('/\D/', '', $s->dni_raw)]));
        return $docs ? Estudiante::whereIn('dni', $docs)->first() : null;
    }

    /** OBU abrevia y escribe mal los nombres: se tolera hasta 2 letras de diferencia en los 2 primeros tokens. */
    private function nombreCoincide(string $excel, Estudiante $e): bool
    {
        $ex = $this->tokens($excel);
        $tk = $this->tokens("{$e->apellidos} {$e->nombres}");
        if (!$ex) return false;

        foreach (array_slice($ex, 0, 2) as $t) {
            if (!collect($tk)->contains(fn ($x) => levenshtein($t, $x) <= 2)) return false;
        }
        return true;
    }

    private function tokens(string $s): array
    {
        return preg_split('/[^A-Z]+/', Str::upper(Str::ascii($s)), -1, PREG_SPLIT_NO_EMPTY);
    }

    private function marcar(StgBecadoObu $s, string $estado, ?string $nota): void
    {
        $obs = trim(($s->observacion ? $s->observacion . '; ' : '') . $nota, '; ');
        $s->update(['estado' => $estado, 'observacion' => $obs !== '' ? Str::limit($obs, 250, '') : null]);
    }
}