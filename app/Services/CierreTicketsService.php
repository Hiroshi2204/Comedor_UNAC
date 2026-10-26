<?php

namespace App\Services;

use App\Models\Inasistencia;
use App\Models\Ticket;
use App\Models\VentanaAtencion;
use App\Support\FranjasComedor;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CierreTicketsService
{
    public function __construct(private SancionService $sanciones) {}

    /**
     * Cierra los tickets cuyo plazo ya pasó:
     *  - 1.ª vez que no llega: se le manda a la ÚLTIMA franja del servicio (última oportunidad).
     *  - Si tampoco llega (o ya no hay franja): vence y se registra la inasistencia.
     */
    public function cerrar(?Carbon $ahora = null): array
    {
        $ahora ??= now();
        $vencidos = 0;
        $reprogramados = 0;
        $afectados = [];

        DB::transaction(function () use ($ahora, &$vencidos, &$reprogramados, &$afectados) {
            $tickets = Ticket::where('estado', 'generado')
                ->whereNotNull('vence_at')->where('vence_at', '<', $ahora)
                ->lockForUpdate()->get();

            $franjas = null; // se cargan una sola vez y solo si hay tickets por cerrar

            foreach ($tickets as $t) {
                $franjas ??= $this->franjasPorServicio();

                $destino = FranjasComedor::ultimaOportunidad(
                    [
                        'fecha'              => $t->fecha->toDateString(),
                        'ventana_id'         => (int) $t->ventana_atencion_id,
                        'ultima_oportunidad' => $t->ultima_oportunidad_at !== null,
                    ],
                    $franjas[$t->servicio] ?? [],
                    $ahora->toImmutable(),
                    (int) config('comedor.reserva.tolerancia_min'),
                    (string) config("comedor.servicios.{$t->servicio}.fin")
                );

                if ($destino) {
                    $t->forceFill([
                        'ventana_original_id'   => $t->ventana_atencion_id,
                        'ventana_atencion_id'   => $destino['ventana_id'],
                        'vence_at'              => $destino['vence_at']->format('Y-m-d H:i:s'),
                        'ultima_oportunidad_at' => $ahora,
                    ])->save();
                    $reprogramados++;
                    continue;
                }

                // ya usó su última oportunidad (o no queda tiempo): inasistencia
                $t->forceFill(['estado' => 'vencido'])->save();

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
            'reprogramados' => $reprogramados,
            'vencidos'      => $vencidos,
            'no_recogidos'  => $noRecogidos,
            'sancionados'   => $sancionados,
            'levantadas'    => $levantadas,
        ];
    }

    /** servicio => [['id', 'inicio', 'fin'], ...] ordenadas por hora de inicio */
    private function franjasPorServicio(): array
    {
        return VentanaAtencion::where('activo', true)->orderBy('hora_inicio')
            ->get(['id', 'servicio', 'hora_inicio', 'hora_fin'])
            ->groupBy('servicio')
            ->map(fn($g) => $g->map(fn($v) => [
                'id'     => (int) $v->id,
                'inicio' => substr($v->hora_inicio, 0, 5),
                'fin'    => substr($v->hora_fin, 0, 5),
            ])->values()->all())
            ->all();
    }
}
