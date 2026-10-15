<?php

namespace App\Services;

use App\Models\Inasistencia;
use App\Models\Sancion;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\UsuarioSistema;

class SancionService
{
    /** Crea una sanción si el estudiante llegó al límite de inasistencias desde su última sanción. */
    public function evaluar(int $estudianteId, ?Carbon $ahora = null): ?Sancion
    {
        $ahora ??= now();
        $limite = (int) config('comedor.inasistencias.limite');
        $dias   = (int) config('comedor.inasistencias.sancion_dias');

        return DB::transaction(function () use ($estudianteId, $ahora, $limite, $dias) {
            if (Sancion::where('estudiante_id', $estudianteId)->where('estado', 'activa')->lockForUpdate()->exists()) {
                return null;
            }

            $ultima = Sancion::where('estudiante_id', $estudianteId)->latest('id')->first();

            $q = Inasistencia::where('estudiante_id', $estudianteId);
            if ($ultima) {
                $q->where('created_at', '>', $ultima->updated_at);
            }
            $n = $q->count();

            if ($n < $limite) {
                return null;
            }

            return Sancion::create([
                'estudiante_id'                   => $estudianteId,
                'numero_inasistencias_acumuladas' => $n,
                'estado'                          => 'activa',
                'motivo'                          => "{$n} inasistencias a tickets del comedor",
                'fecha_inicio'                    => $ahora->toDateString(),
                'fecha_fin' => $dias > 0 ? $ahora->copy()->addDays($dias)->toDateString() : null,
            ]);
        });
    }

    /** Pasa a "levantada" las sanciones cuya fecha_fin ya pasó. Devuelve cuántas. */
    public function levantarVencidas(?Carbon $ahora = null): int
    {
        $ahora ??= now();

        return Sancion::where('estado', 'activa')
            ->whereNotNull('fecha_fin')->where('fecha_fin', '<', $ahora->toDateString())
            ->update(['estado' => 'levantada']);
    }

    public function levantar(Sancion $sancion, UsuarioSistema $admin, ?Carbon $ahora = null): bool
    {
        if (!$admin->esAdministrador()) {
            throw new \DomainException('Solo un administrador puede levantar una sanción.');
        }
        $ahora ??= now();

        return DB::transaction(function () use ($sancion, $ahora) {
            $s = Sancion::where('id', $sancion->id)->lockForUpdate()->first();
            if (!$s || $s->estado !== 'activa') {
                return false;
            }

            $s->update(['estado' => 'levantada', 'fecha_fin' => $ahora->toDateString()]);

            return true;
        });
    }
}
