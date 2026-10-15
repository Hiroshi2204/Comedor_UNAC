<?php

namespace App\Services;

use App\Exceptions\TicketNoDisponible;
use App\Models\CancelacionTicket;
use App\Models\Estudiante;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CancelacionTicketService
{
    /** El estudiante cancela su ticket. Solo antes de pasar por la puerta y antes de que venza. */
    public function cancelar(Ticket $ticket, Estudiante $estudiante, ?string $motivo = null, ?Carbon $ahora = null): Ticket
    {
        $ahora ??= now();

        return DB::transaction(function () use ($ticket, $estudiante, $motivo, $ahora) {
            $t = Ticket::where('id', $ticket->id)->lockForUpdate()->firstOrFail();

            if ($t->estudiante_id !== $estudiante->id) {
                throw new TicketNoDisponible('Este ticket no te pertenece.');
            }

            match ($t->estado) {
                'generado'    => null,
                'cancelado'   => throw new TicketNoDisponible('Este ticket ya fue cancelado.'),
                'validado',
                'recogido'    => throw new TicketNoDisponible('Ya pasaste por la puerta: no se puede cancelar.'),
                default       => throw new TicketNoDisponible('Este ticket ya venció.'),
            };

            if ($t->vence_at && $ahora->gt($t->vence_at)) {
                throw new TicketNoDisponible('Este ticket ya venció.');
            }

            CancelacionTicket::create([
                'ticket_id'         => $t->id,
                'estudiante_id'     => $estudiante->id,
                'motivo'            => $motivo ? mb_substr($motivo, 0, 255) : null,
                'fecha_cancelacion' => $ahora,
            ]);

            $t->update(['estado' => 'cancelado']); // libera el cupo (no cuenta en NO_CUENTAN)

            return $t;
        });
    }
}
