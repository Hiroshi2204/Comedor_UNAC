<?php

namespace App\Services;

use App\Models\Inasistencia;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CierreTicketsService
{
    public function __construct(private SancionService $sanciones) {}

    /** Cierra los tickets cuyo plazo ya pasó, registra inasistencias y evalúa sanciones. */
    public function cerrar(?Carbon $ahora = null): array
    {
        $ahora ??= now();
        $vencidos = 0;
        $afectados = [];

        // nunca pasaron por la puerta = inasistencia
        DB::transaction(function () use ($ahora, &$vencidos, &$afectados) {
            $tickets = Ticket::where('estado', 'generado')
                ->whereNotNull('vence_at')->where('vence_at', '<', $ahora)
                ->lockForUpdate()->get();

            foreach ($tickets as $t) {
                $t->update(['estado' => 'vencido']);

                Inasistencia::firstOrCreate(
                    ['ticket_id' => $t->id],
                    [
                        'estudiante_id'        => $t->estudiante_id,
                        'periodo_academico_id' => $t->periodo_academico_id,
                        'fecha'                => $t->fecha->toDateString(),
                    ]
                );

                $afectados[$t->estudiante_id] = true;
                $vencidos++;
            }
        });

        // pasaron por la puerta pero no recogieron la comida (no es inasistencia)
        $noRecogidos = Ticket::where('estado', 'validado')
            ->whereNotNull('vence_at')->where('vence_at', '<', $ahora)
            ->update(['estado' => 'no_recogido']);

        $sancionados = 0;
        foreach (array_keys($afectados) as $estudianteId) {
            if ($this->sanciones->evaluar($estudianteId, $ahora)) {
                $sancionados++;
            }
        }

        $levantadas = $this->sanciones->levantarVencidas($ahora);

        return [
            'vencidos'     => $vencidos,
            'no_recogidos' => $noRecogidos,
            'sancionados'  => $sancionados,
            'levantadas'   => $levantadas,
        ];
    }
}