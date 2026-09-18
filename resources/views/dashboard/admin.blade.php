@extends('layouts.app')
@section('title', 'Panel del administrador')

@section('content')
<h4 class="fw-bold mb-4">Hola, {{ $usuario->nombre }}</h4>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card card-stat p-3 d-flex flex-row align-items-center gap-3">
            <div class="icon-circle"><i class="bi bi-ticket-perforated-fill"></i></div>
            <div>
                <div class="text-muted small">Tickets de hoy</div>
                <div class="h4 mb-0 fw-bold">{{ $stats['tickets_hoy'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-stat p-3 d-flex flex-row align-items-center gap-3">
            <div class="icon-circle"><i class="bi bi-check-circle-fill"></i></div>
            <div>
                <div class="text-muted small">Recogidos hoy</div>
                <div class="h4 mb-0 fw-bold">{{ $stats['tickets_recogidos_hoy'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-stat p-3 d-flex flex-row align-items-center gap-3">
            <div class="icon-circle"><i class="bi bi-mortarboard-fill"></i></div>
            <div>
                <div class="text-muted small">Becados activos</div>
                <div class="h4 mb-0 fw-bold">{{ $stats['becados_activos'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-stat p-3 d-flex flex-row align-items-center gap-3">
            <div class="icon-circle"><i class="bi bi-exclamation-triangle-fill"></i></div>
            <div>
                <div class="text-muted small">Sanciones activas</div>
                <div class="h4 mb-0 fw-bold">{{ $stats['sanciones_activas'] }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card card-stat p-4">
    <h6 class="fw-bold mb-3">Accesos rápidos</h6>
    <div class="row g-3">
        <div class="col-md-3"><button class="btn btn-outline-secondary w-100" disabled><i class="bi bi-file-earmark-excel me-1"></i> Importar Excel</button></div>
        <div class="col-md-3"><button class="btn btn-outline-secondary w-100" disabled><i class="bi bi-people me-1"></i> Gestionar becados</button></div>
        <div class="col-md-3"><button class="btn btn-outline-secondary w-100" disabled><i class="bi bi-clock me-1"></i> Ventanas de atención</button></div>
        <div class="col-md-3"><button class="btn btn-outline-secondary w-100" disabled><i class="bi bi-bar-chart me-1"></i> Reportes</button></div>
    </div>
</div>
@endsection