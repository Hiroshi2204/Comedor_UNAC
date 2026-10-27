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

    // true = becados y no becados pueden pedir ticket aunque tengan clase en curso.
    // Por defecto permitido; en .env: COMEDOR_PERMITIR_EN_CLASE=false para volver a bloquear.
    'permitir_ticket_en_clase' => filter_var(env('COMEDOR_PERMITIR_EN_CLASE', true), FILTER_VALIDATE_BOOLEAN),

    // Ruta del php.exe de línea de comandos para lanzar la sincronización desde el panel.
    // Necesario en Windows/Laragon si el servidor web no tiene "php" en el PATH. Ej.: C:\laragon\bin\php\php-8.2.12-Win32-vs16-x64\php.exe
    'php_cli' => env('PHP_CLI_BINARY'),

    // Reloj de demostración: permite simular fecha y hora desde Admin > /admin/demo.
    // Se activa con DEMO_RELOJ=true en el .env y nunca funciona en producción.
    'demo' => [
        'habilitado'     => filter_var(env('DEMO_RELOJ', false), FILTER_VALIDATE_BOOLEAN) && env('APP_ENV') !== 'production',
        'comando_cierre' => env('DEMO_COMANDO_CIERRE', 'tickets:cerrar'),
    ],

    // Acceso de pruebas: nunca se activa en producción, aunque el .env lo pida.
    'acceso_pruebas' => [
        'activo' => filter_var(env('ACCESO_PRUEBAS', false), FILTER_VALIDATE_BOOLEAN)
            && env('APP_ENV') !== 'production',
    ],

];
