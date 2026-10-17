@php
    $trazos = [
        'dashboard'      => '<path d="M2.5 7.2 8 2.5l5.5 4.7v6.3h-11z"/>',
        'tickets'        => '<rect x="2.5" y="2.5" width="11" height="11" rx="2"/><circle cx="8" cy="8" r="1.4"/>',
        'estudiantes'    => '<circle cx="8" cy="5.5" r="2.5"/><path d="M3 13.5c0-2.8 2.2-4.5 5-4.5s5 1.7 5 4.5"/>',
        'becados'        => '<path d="M8 2l1.9 4 4.1.5-3 2.9.8 4.2L8 11.5 4.2 13.6 5 9.4 2 6.5 6.1 6z"/>',
        'horarios'       => '<circle cx="8" cy="8" r="5.5"/><path d="M8 5v3.2l2 1.3"/>',
        'ventanas'       => '<rect x="2.5" y="3" width="11" height="10" rx="1.5"/><path d="M8 3v10"/>',
        'sanciones'      => '<path d="M8 3v6"/><circle cx="8" cy="12" r=".8" fill="currentColor"/>',
        'reportes'       => '<rect x="2.5" y="2.5" width="11" height="11" rx="1.5"/><path d="M5 6h6M5 8.5h6M5 11h4"/>',
        'notificaciones' => '<path d="M4 11V7a4 4 0 0 1 8 0v4l1 1.5H3z"/><path d="M6.5 14h3"/>',
        'usuarios'       => '<circle cx="6" cy="6" r="2.2"/><path d="M2 13c0-2.3 1.8-3.7 4-3.7s4 1.4 4 3.7"/><path d="M10.5 4.2a2.2 2.2 0 0 1 0 4.2M12 9.6c1.3.5 2 1.6 2 3.4"/>',
        'configuracion'  => '<circle cx="8" cy="8" r="2"/><path d="M8 2v1.5M8 12.5V14M2 8h1.5M12.5 8H14M3.8 3.8l1 1M11.2 11.2l1 1M12.2 3.8l-1 1M4.8 11.2l-1 1"/>',
    ];
@endphp
<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $trazos[$n] ?? '' !!}</svg>