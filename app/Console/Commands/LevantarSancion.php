<?php

namespace App\Console\Commands;

use App\Models\Sancion;
use App\Models\UsuarioSistema;
use App\Services\SancionService;
use Illuminate\Console\Command;

class LevantarSancion extends Command
{
    protected $signature = 'comedor:levantar-sancion {sancion? : ID de la sanción (si se omite, lista las activas)}
        {--admin= : ID del administrador (por defecto el primero activo)}';
    protected $description = 'Lista las sanciones activas o levanta una (prueba; en producción lo hace el administrador desde su pantalla)';

    public function handle(SancionService $servicio): int
    {
        if (!$this->argument('sancion')) {
            $filas = Sancion::with('estudiante')->where('estado', 'activa')->get()->map(fn($s) => [
                $s->id,
                $s->estudiante->codigo_universitario,
                $s->fecha_inicio->toDateString(),
                $s->motivo,
            ])->all();

            $filas ? $this->table(['ID', 'Código', 'Desde', 'Motivo'], $filas) : $this->info('No hay sanciones activas.');
            return self::SUCCESS;
        }

        $sancion = Sancion::find($this->argument('sancion'));
        if (!$sancion) {
            $this->error('No existe esa sanción.');
            return self::FAILURE;
        }

        $admin = $this->option('admin')
            ? UsuarioSistema::find($this->option('admin'))
            : UsuarioSistema::where('rol', 'administrador')->where('activo', true)->first();
        if (!$admin) {
            $this->error('No hay un administrador activo.');
            return self::FAILURE;
        }

        try {
            $ok = $servicio->levantar($sancion, $admin);
        } catch (\DomainException $e) {
            $this->warn($e->getMessage());
            return self::SUCCESS;
        }

        $ok ? $this->info('Sanción levantada. El estudiante ya puede pedir tickets y su conteo de inasistencias reinicia.')
            : $this->warn('La sanción ya no estaba activa.');
        return self::SUCCESS;
    }
}
