{{--
    Plantilla común de las páginas de error.

    Sin estas vistas Laravel muestra sus pantallas en inglés ("Page Expired",
    "Not Found") sin ninguna forma de volver, que fue lo que los usuarios
    reportaron como "error 404" durante la prueba de usabilidad.
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo') · Sistema de Semilleros</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #eef2ff 0%, #f8fafc 55%, #faf5ff 100%);
            color: #1f2937;
        }
        .tarjeta {
            width: 100%;
            max-width: 480px;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, .08);
            padding: 40px 32px;
            text-align: center;
        }
        .icono {
            width: 60px; height: 60px;
            margin: 0 auto 20px;
            display: flex; align-items: center; justify-content: center;
            border-radius: 50%;
            background: #eef2ff;
            color: #4f46e5;
        }
        .codigo { font-size: 13px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #9ca3af; }
        h1 { margin: 8px 0 10px; font-size: 22px; color: #111827; }
        p { margin: 0 0 8px; font-size: 15px; line-height: 1.55; color: #4b5563; }
        .acciones { margin-top: 26px; display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; }
        a.boton {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 9px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: background-color .15s;
        }
        .primario { background: #4f46e5; color: #fff; }
        .primario:hover { background: #4338ca; }
        .secundario { background: #fff; color: #374151; border: 1px solid #d1d5db; }
        .secundario:hover { background: #f9fafb; }
    </style>
</head>
<body>
    <div class="tarjeta">
        <div class="icono">
            <svg width="30" height="30" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                @yield('icono')
            </svg>
        </div>
        <p class="codigo">Error @yield('codigo')</p>
        <h1>@yield('titulo')</h1>
        @yield('mensaje')
        <div class="acciones">
            @yield('acciones')
        </div>
    </div>
</body>
</html>
