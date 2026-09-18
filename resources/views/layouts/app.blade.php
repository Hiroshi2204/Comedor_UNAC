<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Sistema de Tickets del Comedor') — UNAC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --navy: #1E2761;
            --navy-dark: #141B4D;
            --orange: #E8871E;
        }

        body {
            background-color: #F7F8FC;
        }

        .navbar-comedor {
            background-color: var(--navy);
        }

        .navbar-comedor .navbar-brand,
        .navbar-comedor .nav-link {
            color: #fff !important;
        }

        .btn-comedor {
            background-color: var(--orange);
            border-color: var(--orange);
            color: #fff;
        }

        .btn-comedor:hover {
            background-color: #cf7415;
            border-color: #cf7415;
            color: #fff;
        }

        .card-stat {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(30, 39, 97, .08);
        }

        .icon-circle {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--navy);
            color: var(--orange);
            font-size: 1.3rem;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-comedor px-3">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="{{ route('dashboard') }}">
                <i class="bi bi-cup-hot-fill me-1"></i> Comedor UNAC
            </a>
            <div class="d-flex align-items-center ms-auto">
                <span class="text-white me-3 small">
                    {{ auth()->user()->nombre }}
                    <span class="badge bg-warning text-dark ms-1">{{ ucfirst(auth()->user()->rol) }}</span>
                </span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-sm btn-outline-light">
                        <i class="bi bi-box-arrow-right"></i> Salir
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <main class="container-fluid py-4 px-4">
        @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        @yield('content')
    </main>
</body>

</html>