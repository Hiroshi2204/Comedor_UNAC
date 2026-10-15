<?php

return [
    'url'           => env('SGA_API_URL'),
    'user'          => env('SGA_WS_USER'),
    'pass'          => env('SGA_WS_PASS'),
    'periodo'       => env('SGA_PERIODO', '2026B'),
    'calendario'    => env('SGA_CALENDARIO_ACADEMICO_ID'),
    'escuelas_json' => storage_path('app/sga/escuelas.json'),
    'login_url' => env('SGA_LOGIN_URL'),
];