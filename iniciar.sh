#!/bin/bash
# Arranca la aplicación en desarrollo: servidor Laravel + servidor Vite.
# Uso: ./iniciar.sh   (Ctrl+C detiene ambos)
#
# Requiere haber ejecutado antes: composer install, npm install y la
# configuración de .env y la base de datos descrita en el README.
cd "$(dirname "$0")" || exit 1

# En macOS con Homebrew, deja disponibles php, node y mariadb.
if [ -x /opt/homebrew/bin/brew ]; then
    eval "$(/opt/homebrew/bin/brew shellenv)"
    brew services start mariadb >/dev/null 2>&1
fi

echo "==> Laravel -> http://127.0.0.1:8000"
php artisan serve --port=8000 &
PID_LARAVEL=$!

echo "==> Vite (frontend)"
npm run dev &
PID_VITE=$!

trap "echo; echo '==> Deteniendo...'; kill $PID_LARAVEL $PID_VITE 2>/dev/null; exit 0" INT TERM
wait
