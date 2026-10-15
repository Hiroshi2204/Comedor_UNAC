<?php

namespace App\Console\Commands;

use App\Services\SgaSsoToken;
use Illuminate\Console\Command;

class EnlaceSga extends Command
{
    protected $signature = 'comedor:enlace-sga {codigo : Código del estudiante} {--sin-firma : Genera el link sin firma}';
    protected $description = 'Genera un link de acceso como el que enviaría el SGA (para pruebas)';

    public function handle(): int
    {
        $p = ['username' => $this->argument('codigo')];

        if (!$this->option('sin-firma')) {
            if (!config('sga.sso.secret')) {
                $this->error('Falta SGA\_SSO\_SECRET en el .env.');
                return self::FAILURE;
            }
            $p['ts'] = time();
            $p['sig'] = SgaSsoToken::firmar($p['username'], $p['ts']);
        }

        $this->line(route('estudiante.acceso', $p));
        return self::SUCCESS;
    }
}