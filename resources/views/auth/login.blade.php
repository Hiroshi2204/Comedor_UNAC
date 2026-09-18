<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ingreso de Personal — Comedor UNAC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            display: flex;
        }

        .panel-brand {
            background: linear-gradient(160deg, #1E2761 0%, #141B4D 100%);
            color: #fff;
        }

        .btn-comedor {
            background-color: #E8871E;
            border-color: #E8871E;
        }

        a.link-orange {
            color: #E8871E;
        }
    </style>
</head>

<body class="w-100">
    <div class="container-fluid">
        <div class="row min-vh-100">
            <div class="col-lg-5 panel-brand d-none d-lg-flex flex-column justify-content-center p-5">
                <i class="bi bi-cup-hot-fill display-3 mb-3" style="color:#E8871E;"></i>
                <h2 class="fw-bold">Sistema de Tickets Digitales</h2>
                <p class="opacity-75">Panel de administración y entrega del Comedor Universitario — UNAC.</p>
            </div>
            <div class="col-lg-7 d-flex align-items-center justify-content-center">
                <div style="max-width: 380px;" class="w-100">
                    <h4 class="fw-bold mb-1">Ingreso de personal</h4>
                    <p class="text-muted mb-4">Administrador y trabajadores del comedor.</p>

                    @if ($errors->any())
                    <div class="alert alert-danger py-2">{{ $errors->first() }}</div>
                    @endif

                    <form method="POST" action="{{ route('login') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Usuario</label>
                            <input type="text" name="username" value="{{ old('username') }}" class="form-control" required autofocus>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contraseña</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="form-check mb-3">
                            <input type="checkbox" name="remember" class="form-check-input" id="remember">
                            <label class="form-check-label" for="remember">Recordarme</label>
                        </div>
                        <button type="submit" class="btn btn-comedor w-100 text-white fw-semibold">Ingresar</button>
                    </form>

                    <hr class="my-4">
                    <p class="text-center small">
                        ¿Eres estudiante?
                        <a href="{{ route('estudiante.proximamente') }}" class="link-orange">Ingresa con tu cuenta del SGA</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>

</html>