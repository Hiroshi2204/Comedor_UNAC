<?php

namespace App\Support;

/** Cálculos del dashboard del administrador. PHP puro, sin Laravel. */
class PanelAdmin
{
    /** @param array<string,int> $porEstado conteo de tickets de hoy por estado */
    public static function resumen(array $porEstado, int $sancionesActivas, string $periodo): array
    {
        $n = fn (string $e) => (int) ($porEstado[$e] ?? 0);

        $total      = array_sum(array_map('intval', $porEstado));
        $pendientes = $n('generado');
        $recogidos  = $n('recogido');
        $cancelados = $n('cancelado');
        $noRecogidos = $n('vencido') + $n('no_recogido');
        $validados  = $n('validado') + $recogidos + $n('no_recogido');   // los que pasaron por la puerta
        $atendibles = max(1, $total - $cancelados);
        $pct        = (int) round($recogidos / $atendibles * 100);

        $kpis = [
            ['titulo' => 'Tickets generados hoy', 'valor' => $total,        'detalle' => "{$validados} validados · {$pendientes} pendientes", 'color' => 'azul'],
            ['titulo' => 'Tickets recogidos',     'valor' => $recogidos,    'detalle' => "{$pct}% de atención",                              'color' => 'verde'],
            ['titulo' => 'No recogidos',          'valor' => $noRecogidos,  'detalle' => 'Base para inasistencias',                           'color' => 'naranja'],
            ['titulo' => 'Sanciones activas',     'valor' => $sancionesActivas, 'detalle' => "Periodo {$periodo}",                           'color' => 'rojo'],
        ];

        $max = max(1, $total);
        $barras = [];
        foreach ([
            ['Generados',     $total,       'azul'],
            ['Validados',     $validados,   'morado'],
            ['Recogidos',     $recogidos,   'verde'],
            ['No recogidos',  $noRecogidos, 'rojo'],
        ] as [$nombre, $valor, $color]) {
            $barras[] = ['nombre' => $nombre, 'valor' => $valor, 'color' => $color, 'ancho' => (int) round($valor / $max * 100)];
        }

        return ['kpis' => $kpis, 'barras' => $barras, 'total' => $total];
    }

    public static function motivoEscaneo(string $codigo): string
    {
        return [
            'no_existe'      => 'código no registrado',
            'cancelado'      => 'ticket cancelado',
            'vencido'        => 'ticket vencido',
            'ya_recogido'    => 'ya había recogido',
            'ya_validado'    => 'ya había ingresado',
            'falta_puerta'   => 'no pasó por la puerta',
            'fuera_de_fecha' => 'ticket de otro día',
            'muy_temprano'   => 'fuera de su franja',
            'sin_permiso'    => 'puesto sin permiso',
        ][$codigo] ?? $codigo;
    }

    /**
     * @param array<int,array{ts:int,titulo:string,detalle:string,color:string}> $eventos
     * @return array<int,array{hora:string,titulo:string,detalle:string,color:string}>
     */
    public static function ordenarActividad(array $eventos, int $limite = 8): array
    {
        usort($eventos, fn ($a, $b) => $b['ts'] <=> $a['ts']);

        return array_map(
            fn ($e) => ['hora' => date('H:i', $e['ts']), 'titulo' => $e['titulo'], 'detalle' => $e['detalle'], 'color' => $e['color']],
            array_slice($eventos, 0, $limite)
        );
    }
}