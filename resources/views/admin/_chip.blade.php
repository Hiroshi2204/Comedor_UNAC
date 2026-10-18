@php
    $etiquetas = [
        'generado' => 'Generado', 'validado' => 'Validado', 'recogido' => 'Recogido', 'vencido' => 'Vencido',
        'no_recogido' => 'No recogido', 'cancelado' => 'Cancelado', 'activa' => 'Activa', 'levantada' => 'Levantada',
        'cargado' => 'Por procesar', 'procesado' => 'Procesado', 'historico' => 'Histórico',
    ];
@endphp
<span class="chip e-{{ $e }}">{{ $etiquetas[$e] ?? ucfirst(str_replace('_', ' ', $e)) }}</span>
