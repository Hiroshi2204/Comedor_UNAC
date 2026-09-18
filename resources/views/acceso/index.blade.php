<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Comedor Universitario — UNAC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(160deg, #1E2761 0%, #141B4D 100%);
            min-height: 100vh;
            color: #fff;
        }

        .card-acceso {
            border: none;
            border-radius: 16px;
            transition: transform .15s;
        }

        .card-acceso:hover {
            transform: translateY(-4px);
        }

        .btn-comedor {
            background-color: #E8871E;
            border-color: #E8871E;
        }
    </style>
</head>

<body class="d-flex align-items-center">
    <div class="container text-center">
        <i class="bi bi-cup-hot-fill display-2 mb-2" style="color:#E8871E;"></i>
        <h1 class="fw-bold mb-1">Sistema de Tickets Digitales</h1>
        <p class="opacity-75 mb-5">Comedor Universitario — Universidad Nacional del Callao</p>

        <div class="row justify-content-center g-4">
            <div class="col-md-4">
                <div class="card card-acceso p-4 h-100">
                    <i class="bi bi-mortarboard-fill display-5 text-primary mb-3"></i>
                    <h5 class="fw-bold text-dark">Soy estudiante</h5>
                    <p class="text-muted small">Ingresa con tu cuenta del SGA para ver tu ticket del día.</p>
                    <a href="{{ route('estudiante.proximamente') }}" class="btn btn-outline-dark mt-auto">Ingresar</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-acceso p-4 h-100">
                    <i class="bi bi-person-badge-fill display-5 mb-3" style="color:#E8871E;"></i>
                    <h5 class="fw-bold text-dark">Soy personal del comedor</h5>
                    <p class="text-muted small">Administrador o trabajador — gestiona tickets y reportes.</p>
                    <a href="{{ route('login') }}" class="btn btn-comedor text-white mt-auto">Ingresar</a>
                </div>
            </div>
        </div>
    </div>
</body>

</html>