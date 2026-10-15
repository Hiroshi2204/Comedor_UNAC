<?php

namespace App\Console\Commands;

use App\Models\UsuarioSistema;
use App\Services\EscaneoTicketService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class EscanearTicket extends Command
{
    protected $signature = 'comedor:escanear {puesto : puerta o entrega} {codigo : Contenido del QR}
        {--trabajador= : ID del usuario del sistema (por defecto el primero)}
        {--ahora= : Simula la hora actual, ej. "2026-10-12 12:25"}';
    protected $description = 'Simula el escaneo de un QR en la puerta o en la entrega (para pruebas)';

    public function handle(EscaneoTicketService $servicio): int
    {
        $puesto = $this->argument('puesto');
        if (!in_array($puesto, ['puerta', 'entrega'], true)) {
            $this->error('El puesto debe ser "puerta" o "entrega".');
            return self::FAILURE;
        }

        $puestoUsuario = $puesto === 'puerta' ? 'puerta' : 'cocina';
        $trabajador = $this->option('trabajador')
            ? UsuarioSistema::find($this->option('trabajador'))
            : UsuarioSistema::where('rol', 'trabajador')->where('puesto', $puestoUsuario)->where('activo', true)->first();
        if (!$trabajador) {
            $this->error("No hay un trabajador activo con puesto {$puestoUsuario}. Créalo o indica --trabajador=ID.");
            return self::FAILURE;
        }

        $ahora = $this->option('ahora') ? Carbon::parse($this->option('ahora')) : now();
        $r = $servicio->{$puesto === 'puerta' ? 'validarEnPuerta' : 'entregar'}($this->argument('codigo'), $trabajador, $ahora);

        $r['ok'] ? $this->info("OK ({$r['codigo']}): {$r['mensaje']}") : $this->warn("RECHAZADO ({$r['codigo']}): {$r['mensaje']}");

        if ($r['ticket']) {
            $this->table(['Dato', 'Valor'], collect($r['ticket'])->map(fn($v, $k) => [$k, $v])->values()->all());
        }
        return self::SUCCESS;
    }
}
