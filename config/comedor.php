<?php

return [
    'margen_min' => 10,                    // tiempo para caminar entre aula y comedor
    'franja_min' => 15,
    'desayuno_inicio_desde' => '08:00',    // primera clase entre 08:00 y 09:59
    'desayuno_inicio_hasta' => '10:00',
    'cena_si_termina_despues' => '17:00',
    'servicios' => [
        'desayuno' => ['inicio' => '07:00', 'fin' => '08:00', 'stock' => 600],
        'almuerzo' => ['inicio' => '12:00', 'fin' => '15:00', 'stock' => 2200],
        'cena'     => ['inicio' => '18:00', 'fin' => '20:30', 'stock' => 300],
    ],
];