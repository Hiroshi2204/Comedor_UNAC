<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SgaAuthService
{
    public function validar(string $username, string $password): bool
    {
        $json = Http::withBasicAuth(config('sga.user'), config('sga.pass'))
            ->acceptJson()
            ->timeout(15)
            ->post(config('sga.login_url'), ['data' => ['username' => $username, 'password' => $password]])
            ->throw()
            ->json();

        return filter_var($json['data']['result'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }
}
