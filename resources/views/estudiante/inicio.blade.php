<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mi comedor — UNAC</title>
    @if ($refrescar)
    <meta http-equiv="refresh" content="30">
    @endif
    <style>
        :root {
            --azul: #13356f;
            --azul2: #1f5fbf;
            --gris: #f4f6fb;
            --borde: #dfe4ee;
            --verde: #16a34a;
            --rojo: #dc2626;
            --ambar: #d97706;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
            background: var(--gris);
            color: #1b2438;
        }

        header.top {
            background: var(--azul);
            color: #fff;
            padding: 14px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }

        header.top h1 {
            margin: 0;
            font-size: 1.1rem;
        }

        header.top small {
            opacity: .8;
            display: block;
        }

        header.top button {
            background: transparent;
            border: 1px solid #fff8;
            color: #fff;
            border-radius: 8px;
            padding: 6px 10px;
        }

        main {
            max-width: 720px;
            margin: 0 auto;
            padding: 16px;
        }

        .badge {
            display: inline-block;
            font-size: .75rem;
            border-radius: 999px;
            padding: 2px 10px;
            background: #e8efff;
            color: var(--azul2);
        }

        .aviso {
            background: #fff7e6;
            border: 1px solid #f5d79a;
            border-radius: 10px;
            padding: 12px;
        }

        .ok {
            background: #e9f9ef;
            border: 1px solid #a7e3bb;
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 12px;
        }

        .error {
            background: #fdecec;
            border: 1px solid #f4b4b4;
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 12px;
        }

        section.dia h2 {
            margin: 24px 0 8px;
            font-size: 1.05rem;
        }

        section.dia h2 small {
            font-weight: 400;
            color: #6b7691;
        }

        article.servicio {
            background: #fff;
            border: 1px solid var(--borde);
            border-radius: 14px;
            padding: 14px;
            margin-bottom: 12px;
        }

        article.servicio header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        article.servicio h3 {
            margin: 0;
            font-size: 1rem;
        }

        .estado {
            font-size: .78rem;
            border-radius: 999px;
            padding: 2px 10px;
            background: #eef1f7;
        }

        .e-disponible,
        .e-reservable {
            background: #e9f9ef;
            color: var(--verde);
        }

        .e-agotado,
        .e-cerrado {
            background: #fdecec;
            color: var(--rojo);
        }

        .e-aun_no_abre,
        .e-en_clase {
            background: #fff7e6;
            color: var(--ambar);
        }

        .qr {
            display: flex;
            justify-content: center;
            padding: 10px 0;
        }

        .qr svg {
            width: 200px;
            height: 200px;
        }

        .codigo {
            text-align: center;
            font-size: .75rem;
            color: #6b7691;
            word-break: break-all;
        }

        button.primario {
            width: 100%;
            background: var(--azul2);
            color: #fff;
            border: 0;
            border-radius: 10px;
            padding: 12px;
            font-size: 1rem;
            margin-top: 8px;
        }

        button.sec {
            background: #fff;
            color: var(--rojo);
            border: 1px solid var(--rojo);
            border-radius: 10px;
            padding: 8px 12px;
            margin-top: 8px;
        }

        select {
            width: 100%;
            padding: 10px;
            border-radius: 10px;
            border: 1px solid var(--borde);
            margin-top: 8px;
            font-size: 1rem;
        }

        p {
            margin: 8px 0;
        }

        .muted {
            color: #6b7691;
            font-size: .9rem;
        }
    </style>
</head>

<body>
    <header class="top">
        <div>
            <h1>Hola, {{ $estudiante->nombreCompleto() }}</h1>
            <small>{{ $estudiante->escuela_profesional }} · {{ $esBecado ? 'Becado' : 'No becado' }}</small>
        </div>
        <form method="POST" action="{{ route('estudiante.salir') }}">@csrf <button>Salir</button></form>
    </header>

    <main>
        @if (session('ok'))
        <div class="ok" role="status">{{ session('ok') }}</div>
        @endif
        @error('ticket')
        <div class="error" role="alert">{{ $message }}</div>
        @enderror

        @include('estudiante._dia', ['d' => $dHoy, 'fecha' => $hoy, 'titulo' => 'Hoy'])
        @include('estudiante._dia', ['d' => $dManana, 'fecha' => $manana, 'titulo' => 'Mañana'])
    </main>
</body>

</html>