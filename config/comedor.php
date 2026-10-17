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
        'cena'     => ['inicio' => '18:00', 'fin' => '19:30', 'stock' => 300],
    ],
    'reserva' => [
        'abre'           => '18:00',  // hora del día anterior desde la que los becados pueden reservar
        'tolerancia_min' => 15,       // minutos extra para recoger después de terminar su franja
    ],
    'ticket' => [
        'vigencia_min'     => 30,  // tiempo de validez del ticket desde que se genera
        'anticipacion_min' => 10,
        'cierre_min_antes' => 5,
    ],
    'no_becados' => [
        'factor_reserva'          => 1.0,
        'libera_min_antes_cierre' => 30,
    ],
    'no_becados' => [
        'factor_reserva'          => 1.0, // 1 = se reserva un cupo por cada becado elegible que aún no genera
        'libera_min_antes_cierre' => 30,  // en los últimos N minutos la reserva se libera
    ],
    'escaneo' => [
        'entrega_min'       => 30,    // minutos extra para recoger la comida tras pasar la puerta
        'gracia_cierre_min' => 20,    // tolerancia después del cierre del servicio
        'exigir_puerta'     => true,  // sin validación en puerta no se entrega
    ],
    'inasistencias' => [
        'limite'       => 3,   // inasistencias que activan una sanción
        'sancion_dias' => 0,   // duración de la sanción
    ],

    'permitir_ticket_en_clase' => env('COMEDOR_PERMITIR_EN_CLASE', false),

    'acceso_pruebas' => [
        'activo' => env('ACCESO_PRUEBAS', false),
    ],

];
