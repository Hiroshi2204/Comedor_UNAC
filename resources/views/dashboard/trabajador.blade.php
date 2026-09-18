@extends('layouts.app')
@section('title', 'Panel del trabajador')

@section('content')
<h4 class="fw-bold mb-4">Hola, {{ $usuario->nombre }}</h4>

<div class="card card-stat p-4">
    <h6 class="fw-bold mb-3"><i class="bi bi-search"></i> Buscar ticket para entrega</h6>
    <p class="text-muted small">Este módulo se habilitará junto con la Etapa de generación de tickets.</p>
    <div class="input-group" style="max-width:420px;">
        <input type="text" class="form-control" placeholder="Código de estudiante o QR" disabled>
        <button class="btn btn-comedor text-white" disabled>Buscar</button>
    </div>
</div>
@endsection