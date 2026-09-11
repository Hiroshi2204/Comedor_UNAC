<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;

class SgaApiService
{
    /**
     * Autentica al estudiante contra el SGA usando Basic Auth
     * y devuelve la respuesta cruda si las credenciales son válidas.
     *
     * @return array|null null si las credenciales son inválidas o la API falla.
     */
    public function obtenerMatriculaAutenticando(string $codigo, string $password): ?array
    {
        try {
            $response = Http::withBasicAuth($codigo, $password)
                ->timeout(15)
                ->post(config('services.sga.url'), [
                    'data' => [
                        'operation' => 'obtener-alumnos-matriculados',
                        'edu_calendario_academico_id' => config('services.sga.calendario_academico_id'),
                        'ad_org_id' => config('services.sga.org_id'),
                    ],
                ]);
        } catch (ConnectionException $e) {
            report($e);
            return null; // SGA caído o inalcanzable
        }

        if ($response->status() === 401 || $response->status() === 403) {
            return null; // credenciales inválidas
        }

        if ($response->failed()) {
            report(new \RuntimeException('SGA API error: ' . $response->status() . ' - ' . $response->body()));
            return null;
        }

        return $response->json();
    }
}
