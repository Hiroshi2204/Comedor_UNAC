<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Acceso del administrador — Comedor UNAC</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --azul: #12356B;
            --azul-btn: #1E73BE;
            --tinta: #0F2447;
            --gris: #64748B;
            --borde: #D9E2EF;
            --campo: #FBFCFE;
            --fondo: #F5F7FB;
            --claro: #CFE0F4;
            --rojo: #D9272E;
            --rojo-fondo: #FDECEC;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            height: 100%;
        }

        body {
            margin: 0;
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif;
            background: var(--fondo);
            color: var(--tinta);
            -webkit-font-smoothing: antialiased;
        }

        .login {
            min-height: 100vh;
            display: grid;
            grid-template-columns: minmax(320px, 36%) 1fr;
        }

        .marca {
            background: var(--azul);
            color: #fff;
            padding: clamp(32px, 6vw, 72px);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .marca .siglas {
            font-size: clamp(2.25rem, 4vw, 3.25rem);
            font-weight: 700;
            line-height: 1;
        }

        .marca .nombre {
            margin-top: 10px;
            font-size: .78rem;
            font-weight: 700;
            letter-spacing: .08em;
            color: var(--claro);
        }

        .marca h1 {
            margin: clamp(40px, 9vh, 96px) 0 0;
            font-size: clamp(1.4rem, 2.2vw, 1.9rem);
            font-weight: 700;
            line-height: 1.2;
        }

        .marca p {
            margin: 10px 0 0;
            max-width: 32ch;
            font-size: .95rem;
            line-height: 1.45;
            color: var(--claro);
        }

        .panel {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 24px;
        }

        .tarjeta {
            width: 100%;
            max-width: 420px;
            background: #fff;
            border: 1px solid var(--borde);
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 8px 24px rgba(15, 36, 71, .06);
        }

        .tarjeta h2 {
            margin: 0;
            font-size: 1.35rem;
            font-weight: 700;
        }

        .tarjeta .sub {
            margin: 6px 0 24px;
            font-size: .8rem;
            font-weight: 700;
            color: var(--gris);
        }

        label {
            display: block;
            margin: 0 0 6px;
            font-size: .75rem;
            font-weight: 700;
            color: var(--gris);
        }

        .campo {
            margin-bottom: 16px;
        }

        input[type=text],
        input[type=password] {
            width: 100%;
            height: 46px;
            padding: 0 14px;
            font: inherit;
            font-size: .95rem;
            color: var(--tinta);
            background: var(--campo);
            border: 1px solid var(--borde);
            border-radius: 8px;
        }

        input::placeholder {
            color: #94A3B8;
        }

        input:focus-visible,
        button:focus-visible {
            outline: 2px solid var(--azul-btn);
            outline-offset: 2px;
        }

        input[aria-invalid=true] {
            border-color: var(--rojo);
        }

        .con-boton {
            position: relative;
        }

        .con-boton input {
            padding-right: 84px;
        }

        .ver {
            position: absolute;
            top: 50%;
            right: 8px;
            transform: translateY(-50%);
            padding: 6px 8px;
            font: inherit;
            font-size: .75rem;
            font-weight: 700;
            color: var(--azul-btn);
            background: transparent;
            border: 0;
            border-radius: 6px;
            cursor: pointer;
        }

        .error {
            margin: 0 0 16px;
            padding: 10px 12px;
            font-size: .82rem;
            font-weight: 600;
            line-height: 1.35;
            color: var(--rojo);
            background: var(--rojo-fondo);
            border: 1px solid #F4B4B4;
            border-radius: 8px;
        }

        .entrar {
            width: 100%;
            height: 46px;
            margin-top: 4px;
            font: inherit;
            font-size: .95rem;
            font-weight: 700;
            color: #fff;
            background: var(--azul-btn);
            border: 0;
            border-radius: 8px;
            cursor: pointer;
        }

        .entrar:hover {
            filter: brightness(.95);
        }

        .entrar[disabled] {
            opacity: .7;
            cursor: progress;
        }

        .pie {
            margin: 18px 0 0;
            text-align: center;
            font-size: .72rem;
            color: var(--gris);
            line-height: 1.5;
        }

        .pie strong {
            display: block;
            font-weight: 700;
        }

        @media (max-width: 768px) {
            .login {
                display: block;
            }

            .marca {
                padding: 28px 20px;
                text-align: center;
                align-items: center;
            }

            .marca .siglas {
                font-size: 2rem;
            }

            .marca .nombre {
                margin-top: 6px;
            }

            .marca h1,
            .marca p {
                display: none;
            }

            .panel {
                align-items: flex-start;
                padding: 20px 16px 32px;
            }

            .tarjeta {
                padding: 24px 20px;
            }
        }
    </style>
</head>

<body>
    <main class="login">
        <section class="marca" aria-label="Comedor Universitario UNAC">
            <div class="siglas">UNAC</div>
            <div class="nombre">COMEDOR UNIVERSITARIO</div>
            <h1>Gestión digital de tickets</h1>
            <p>Servicio para estudiantes y personal del comedor.</p>
        </section>

        <section class="panel">
            <div class="tarjeta">
                <h2>Acceso al sistema</h2>
                <p class="sub">Administrador · cuenta local</p>

                <form method="POST" action="{{ route('login.store') }}" id="form-login" novalidate>
                    @csrf

                    <div class="campo">
                        <label for="login">Usuario</label>
                        <input id="login" name="login" type="text" value="{{ old('login') }}"
                            placeholder="Tu usuario" autocomplete="username" autocapitalize="none"
                            spellcheck="false" maxlength="150" required autofocus
                            @error('login') aria-invalid="true" aria-describedby="error-login" @enderror>
                    </div>

                    <div class="campo">
                        <label for="password">Contraseña</label>
                        <div class="con-boton">
                            <input id="password" name="password" type="password" placeholder="Tu contraseña"
                                autocomplete="current-password" maxlength="100" required>
                            <button type="button" class="ver" id="ver-clave" aria-controls="password" aria-pressed="false">Mostrar</button>
                        </div>
                    </div>

                    @error('login')
                    <p class="error" id="error-login" role="alert">{{ $message }}</p>
                    @enderror
                    @error('password')
                    <p class="error" role="alert">{{ $message }}</p>
                    @enderror

                    <button type="submit" class="entrar" id="entrar">Iniciar sesión</button>
                </form>

                <p class="pie">
                    <strong>Conexión segura · HTTPS</strong>
                    Acceso exclusivo para personal autorizado.
                </p>
            </div>
        </section>
    </main>

    <script>
        (function() {
            var form = document.getElementById('form-login');
            var boton = document.getElementById('entrar');
            var clave = document.getElementById('password');
            var ver = document.getElementById('ver-clave');

            ver.addEventListener('click', function() {
                var visible = clave.type === 'text';
                clave.type = visible ? 'password' : 'text';
                ver.textContent = visible ? 'Mostrar' : 'Ocultar';
                ver.setAttribute('aria-pressed', visible ? 'false' : 'true');
            });

            form.addEventListener('submit', function() {
                boton.disabled = true;
                boton.textContent = 'Validando…';
            });

            window.addEventListener('pageshow', function() {
                boton.disabled = false;
                boton.textContent = 'Iniciar sesión';
                clave.value = '';
            });
        })();
    </script>
</body>

</html>