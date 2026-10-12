#!/usr/bin/env bash
#
# Crea (o vuelve a crear) la base de datos «semilleros_db_test» como copia
# exacta de la base real, para ejecutar la suite de pruebas de usabilidad
# sin tocar los datos de producción.
#
# Uso:  bash scripts/preparar-base-pruebas.sh
#
set -euo pipefail

cd "$(dirname "$0")/.."

# El .env viene de Windows y trae retorno de carro; hay que quitarlo o el
# nombre de la base llega con un carácter invisible y mysqldump falla.
ORIGEN=$(grep '^DB_DATABASE=' .env | cut -d= -f2 | tr -d '\r\n ')
DESTINO="${ORIGEN}_test"
VOLCADO=$(mktemp -t semilleros_copia)

echo "Copiando ${ORIGEN} → ${DESTINO}"

# --default-character-set=utf8mb4 es obligatorio: sin él las tildes se corrompen.
mysqldump --default-character-set=utf8mb4 -u root --single-transaction --routines "$ORIGEN" > "$VOLCADO"

mysql --default-character-set=utf8mb4 -u root -e \
    "DROP DATABASE IF EXISTS \`${DESTINO}\`;
     CREATE DATABASE \`${DESTINO}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

mysql --default-character-set=utf8mb4 -u root "$DESTINO" < "$VOLCADO"
rm -f "$VOLCADO"

echo "Listo. Ahora puedes ejecutar:"
echo "  php vendor/bin/phpunit -c phpunit.usabilidad.xml"
