<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SgaClient
{
    public function llamar(string $operacion, string $calendarioId, string $adOrgId): array
    {
        $json = Http::withBasicAuth(config('sga.user'), config('sga.pass'))
            ->acceptJson()
            ->timeout(180)
            ->retry(2, 2000)
            ->post(config('sga.url'), [
                'operation'                   => $operacion,
                'edu_calendario_academico_id' => $calendarioId,
                'ad_org_id'                   => $adOrgId,
            ])
            ->throw()
            ->json();

        if (($json['status'] ?? null) !== 'OK') {
            throw new \RuntimeException("SGA respondió mal en {$operacion}: " . ($json['status'] ?? 'sin status'));
        }
        return $json;
    }
}