#!/usr/bin/env bash
#
# ============================================================================
# COPIA DE SEGURIDAD DEL SISTEMA DE SEMILLEROS
# ============================================================================
#
# Genera una copia completa del sistema en la carpeta «backups/», con el
# nombre de la fecha y hora del momento. Cada copia contiene tres archivos:
#
#   base_datos.sql        Volcado de todas las tablas con sus datos.
#   codigo_fuente.tar.gz  El proyecto completo, sin las dependencias.
#   manifest.txt          Qué contiene la copia y cómo restaurarla.
#
# Uso:
#   ./scripts/backup-semilleros.sh
#
# Sustituye a los antiguos scripts de Windows (backup-semilleros.ps1,
# run-backup-semilleros.bat y register-backup-task.ps1), que no funcionaban
# en macOS porque buscaban mysqldump.exe en rutas de XAMPP.
# ============================================================================

# «set -e» aborta el script ante el primer error, en lugar de continuar y
# dejar una copia incompleta que parezca válida.
set -e

# ----------------------------------------------------------------------------
# 1. Localizar la raíz del proyecto
# ----------------------------------------------------------------------------
# El script vive en «scripts/», así que la raíz es el directorio padre. Se
# calcula a partir de la ubicación real del script y no del directorio actual,
# para que funcione aunque se invoque desde cualquier otra carpeta.
DIRECTORIO_SCRIPT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
RAIZ_PROYECTO="$(dirname "$DIRECTORIO_SCRIPT")"
cd "$RAIZ_PROYECTO"

# ----------------------------------------------------------------------------
# 2. Leer la configuración de la base de datos desde el archivo .env
# ----------------------------------------------------------------------------
# Se leen los datos del .env en lugar de escribirlos aquí, para que el script
# siga funcionando si cambian la contraseña o el nombre de la base.
leer_variable_env() {
    local clave="$1"           # Nombre de la variable a buscar (ej. DB_HOST)
    local por_defecto="$2"     # Valor a usar si la variable no existe
    local valor

    # Se busca la primera línea que empiece por «CLAVE=» y se recorta el
    # nombre, dejando solo el valor. Las comillas se eliminan porque Laravel
    # permite escribir tanto DB_PASSWORD=clave como DB_PASSWORD="clave".
    valor=$(grep -E "^${clave}=" .env 2>/dev/null | head -1 | cut -d'=' -f2- | tr -d '"' | tr -d "'")

    # Si no se encontró nada, se devuelve el valor por defecto.
    if [ -z "$valor" ]; then
        echo "$por_defecto"
    else
        echo "$valor"
    fi
}

# Sin archivo .env no se sabe a qué base conectarse, así que se aborta.
if [ ! -f .env ]; then
    echo "ERROR: no se encontró el archivo .env en $RAIZ_PROYECTO"
    exit 1
fi

DB_HOST=$(leer_variable_env "DB_HOST" "127.0.0.1")
DB_PORT=$(leer_variable_env "DB_PORT" "3306")
DB_DATABASE=$(leer_variable_env "DB_DATABASE" "")
DB_USERNAME=$(leer_variable_env "DB_USERNAME" "root")
DB_PASSWORD=$(leer_variable_env "DB_PASSWORD" "")

# Sin nombre de base no hay nada que volcar.
if [ -z "$DB_DATABASE" ]; then
    echo "ERROR: no se pudo leer DB_DATABASE del archivo .env"
    exit 1
fi

# ----------------------------------------------------------------------------
# 3. Comprobar que mysqldump está instalado
# ----------------------------------------------------------------------------
# Se verifica antes de crear carpetas, para no dejar un directorio vacío si
# la herramienta no está disponible.
if ! command -v mysqldump >/dev/null 2>&1; then
    echo "ERROR: no se encontró «mysqldump»."
    echo "       Instálalo con: brew install mysql-client"
    exit 1
fi

# ----------------------------------------------------------------------------
# 4. Crear la carpeta de destino con la fecha y hora actuales
# ----------------------------------------------------------------------------
# El formato «2026-09-18_21-34-25» ordena alfabéticamente igual que
# cronológicamente, de modo que la copia más reciente siempre queda al final.
MARCA_TIEMPO=$(date +%Y-%m-%d_%H-%M-%S)
DIRECTORIO_COPIA="backups/$MARCA_TIEMPO"
mkdir -p "$DIRECTORIO_COPIA"

echo "Creando copia de seguridad en $DIRECTORIO_COPIA"
echo

# ----------------------------------------------------------------------------
# 5. Volcar la base de datos
# ----------------------------------------------------------------------------
# Explicación de las opciones utilizadas:
#   --default-character-set=utf8mb4  Conserva tildes y eñes.
#   --single-transaction             Toma una foto coherente de los datos sin
#                                    bloquear las tablas, así el sistema puede
#                                    seguir en uso mientras se hace la copia.
#   --routines --events              Incluye procedimientos y eventos
#                                    programados, no solo tablas y datos.
echo "  [1/3] Volcando la base de datos «$DB_DATABASE»..."
mysqldump \
    --host="$DB_HOST" \
    --port="$DB_PORT" \
    --user="$DB_USERNAME" \
    --password="$DB_PASSWORD" \
    --default-character-set=utf8mb4 \
    --single-transaction \
    --routines \
    --events \
    --skip-lock-tables \
    "$DB_DATABASE" \
    --result-file="$DIRECTORIO_COPIA/base_datos.sql" 2>/dev/null

# Se comprueba que el volcado existe y no está vacío. Sin esta comprobación,
# un fallo de conexión dejaría un archivo de 0 bytes con apariencia de copia.
if [ ! -s "$DIRECTORIO_COPIA/base_datos.sql" ]; then
    echo "ERROR: el volcado de la base de datos salió vacío."
    rm -rf "$DIRECTORIO_COPIA"
    exit 1
fi

TABLAS=$(grep -c "^CREATE TABLE" "$DIRECTORIO_COPIA/base_datos.sql")
echo "        $TABLAS tablas volcadas."

# ----------------------------------------------------------------------------
# 6. Comprimir el código fuente
# ----------------------------------------------------------------------------
# Se excluyen las dependencias porque se reinstalan con un comando y ocupan
# casi 300 MB; y la propia carpeta «backups» para que una copia no contenga
# a las anteriores, creciendo sin control en cada ejecución.
echo "  [2/3] Comprimiendo el código fuente..."
tar --exclude='./node_modules' \
    --exclude='./vendor' \
    --exclude='./backups' \
    --exclude='./.git' \
    --exclude='./storage/logs/*.log' \
    --exclude='./storage/framework/views/*.php' \
    -czf "$DIRECTORIO_COPIA/codigo_fuente.tar.gz" . 2>/dev/null

ARCHIVOS=$(tar -tzf "$DIRECTORIO_COPIA/codigo_fuente.tar.gz" | grep -vc '/$')
echo "        $ARCHIVOS archivos comprimidos."

# ----------------------------------------------------------------------------
# 7. Escribir el manifiesto con las instrucciones de restauración
# ----------------------------------------------------------------------------
echo "  [3/3] Escribiendo el manifiesto..."
cat > "$DIRECTORIO_COPIA/manifest.txt" <<MANIFIESTO
COPIA DE SEGURIDAD - SISTEMA DE SEMILLEROS
==========================================

Fecha:         $MARCA_TIEMPO
Proyecto:      $RAIZ_PROYECTO
Base de datos: $DB_DATABASE ($DB_HOST:$DB_PORT)

CONTENIDO
---------
base_datos.sql        $TABLAS tablas con sus datos
codigo_fuente.tar.gz  $ARCHIVOS archivos del proyecto

NO INCLUYE (se regenera solo)
-----------------------------
node_modules/  ->  npm install
vendor/        ->  composer install
storage/logs/  ->  registros de ejecución, no son datos del sistema

COMO RESTAURAR
--------------
1) tar -xzf codigo_fuente.tar.gz -C /ruta/destino
2) composer install && npm install
3) mysql -u $DB_USERNAME -e "CREATE DATABASE $DB_DATABASE CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   mysql -u $DB_USERNAME $DB_DATABASE < base_datos.sql
4) php artisan storage:link
5) ./iniciar.sh
MANIFIESTO

# ----------------------------------------------------------------------------
# 8. Informe final
# ----------------------------------------------------------------------------
PESO=$(du -sh "$DIRECTORIO_COPIA" | cut -f1)
echo
echo "Copia completada: $DIRECTORIO_COPIA ($PESO)"
