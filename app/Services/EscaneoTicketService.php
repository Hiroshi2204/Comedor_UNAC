<?php

namespace App\Services;

use App\Models\EscaneoTicket;
use App\Models\Ticket;
use App\Models\UsuarioSistema;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EscaneoTicketService
{
    private ReglasEscaneo $reglas;

    public function __construct()
    {
        $this->reglas = new ReglasEscaneo(config('comedor'));
    }

    /** Puerta: confirma que el ticket es válido y deja pasar al estudiante. */
    public function validarEnPuerta(string $codigo, UsuarioSistema $trabajador, ?Carbon $ahora = null): array
    {
        return $this->procesar('puerta', $codigo, $trabajador, $ahora ?? now());
    }

    /** Interior: entrega la comida. Exige haber pasado por la puerta. */
    public function entregar(string $codigo, UsuarioSistema $trabajador, ?Carbon $ahora = null): array
    {
        return $this->procesar('entrega', $codigo, $trabajador, $ahora ?? now());
    }

    private function procesar(string $puesto, string $codigo, UsuarioSistema $trabajador, Carbon $ahora): array
    {
        $codigo = trim($codigo);

        return DB::transaction(function () use ($puesto, $codigo, $trabajador, $ahora) {
            // el bloqueo evita que dos trabajadores procesen el mismo QR a la vez
            $t = Ticket::with(['estudiante', 'ventanaAtencion'])->where('codigo_qr', $codigo)->lockForUpdate()->first();

            if (!$t) {
                $res = $this->resultado(false, 'no_existe', 'El código no corresponde a ningún ticket.');
            } elseif ($rechazo = $this->reglas->evaluar($this->datos($t), $puesto, $ahora->toImmutable())) {
                $res = $this->resultado(false, $rechazo[0], $rechazo[1], $t);
            } else {
                if ($puesto === 'puerta') {
                    $vence = $this->reglas->venceTrasPuerta($t->vence_at?->format('Y-m-d H:i:s'), $t->servicio, $ahora->toImmutable());
                    $t->forceFill([
                        'estado'       => 'validado',
                        'validado_at'  => $ahora,
                        'validado_por' => $trabajador->id,
                        'vence_at'     => $vence->format('Y-m-d H:i:s'),
                    ])->save();
                } else {
                    $t->forceFill([
                        'estado'            => 'recogido',
                        'fecha_hora_recojo' => $ahora,
                        'trabajador_id'     => $trabajador->id,
                    ])->save();
                }
                $res = $this->resultado(true, 'ok', $puesto === 'puerta' ? 'Puede pasar.' : 'Entregar la comida.', $t);
            }

            // todo intento queda registrado (sirve para detectar QR compartidos o repetidos)
            EscaneoTicket::create([
                'ticket_id'     => $t?->id,
                'codigo_qr'     => Str::limit($codigo, 64, ''),
                'puesto'        => $puesto,
                'trabajador_id' => $trabajador->id,
                'exitoso'       => $res['ok'],
                'resultado'     => $res['codigo'],
            ]);

            return $res;
        });
    }

    private function datos(Ticket $t): array
    {
        return [
            'estado'        => $t->estado,
            'fecha'         => $t->fecha->format('Y-m-d'),
            'servicio'      => $t->servicio,
            'franja_inicio' => $t->ventanaAtencion ? substr($t->ventanaAtencion->hora_inicio, 0, 5) : null,
            'vence_at'      => $t->vence_at?->format('Y-m-d H:i:s'),
            'validado_at'   => $t->validado_at?->format('Y-m-d H:i:s'),
            'recogido_at'   => $t->fecha_hora_recojo?->format('Y-m-d H:i:s'),
        ];
    }

    private function resultado(bool $ok, string $codigo, string $mensaje, ?Ticket $t = null): array
    {
        return ['ok' => $ok, 'codigo' => $codigo, 'mensaje' => $mensaje, 'ticket' => $t ? $this->ficha($t) : null];
    }

    /** Lo que el trabajador necesita ver para comparar con el carné del estudiante. */
    private function ficha(Ticket $t): array
    {
        $e = $t->estudiante;
        $v = $t->ventanaAtencion;

        return [
            'ticket_id'  => $t->id,
            'estudiante' => $e->nombreCompleto(),
            'codigo'     => $e->codigo_universitario,
            'dni'        => '****' . substr((string) $e->dni, -4),
            'escuela'    => $e->escuela_profesional,
            'tipo'       => $t->tipo,                       // 'becado' | 'no_becado' (carril de atención)
            'servicio'   => $t->servicio,
            'franja'     => $v ? substr($v->hora_inicio, 0, 5) . '-' . substr($v->hora_fin, 0, 5) : null,
            'vence'      => $t->vence_at?->format('H:i'),
            'estado'     => $t->estado,
        ];
    }
}
