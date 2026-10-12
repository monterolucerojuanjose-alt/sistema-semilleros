# Copias de seguridad

## Qué respalda

El script `scripts/backup-semilleros.sh` crea una carpeta en `backups/` con el
nombre de la fecha y hora de la copia (por ejemplo `backups/2026-09-18_21-34-25`),
y deposita dentro tres archivos:

| Archivo | Contenido |
|---|---|
| `base_datos.sql` | Volcado completo de la base configurada en `.env`, con todas las tablas y sus datos |
| `codigo_fuente.tar.gz` | El proyecto completo: código, `.env`, y `storage/app` con los proyectos subidos, los certificados y las fotos de perfil |
| `manifest.txt` | Qué contiene esa copia concreta y los pasos para restaurarla |

El formato de fecha `AAAA-MM-DD_HH-MM-SS` hace que las copias se ordenen
alfabéticamente igual que cronológicamente, de modo que la más reciente siempre
queda al final del listado.

### Qué no incluye

Se excluyen a propósito, porque se regeneran con un solo comando y multiplicarían
por ocho el tamaño de cada copia:

- `node_modules/` → se recupera con `npm install`
- `vendor/` → se recupera con `composer install`
- `storage/logs/` → registros de ejecución, no son datos del sistema
- `backups/` → para que una copia no contenga a las anteriores

## Ejecutar

```bash
./scripts/backup-semilleros.sh
```

El script se detiene con un mensaje claro si falta el archivo `.env`, si no
encuentra `mysqldump` o si el volcado sale vacío. En ese último caso borra la
carpeta que había creado, para no dejar una copia incompleta con apariencia
de válida.

Si `mysqldump` no está instalado:

```bash
brew install mysql-client
```

## Programar una copia diaria

En macOS se usa `launchd` a través de `crontab`. Para abrir el editor:

```bash
crontab -e
```

Y añadir esta línea, que ejecuta la copia todos los días a las 22:30
(ajusta la ruta si moviste el proyecto):

```
30 22 * * * cd "/Users/montero/Documents/Tesis/SEMILLEROS (1)" && ./scripts/backup-semilleros.sh
```

## Restaurar

Los pasos también quedan escritos dentro de cada copia, en su `manifest.txt`.

**1. Recuperar el código**

```bash
tar -xzf backups/<fecha_hora>/codigo_fuente.tar.gz -C /ruta/destino
```

**2. Reinstalar las dependencias**, dentro de la carpeta restaurada:

```bash
composer install && npm install
```

**3. Cargar la base de datos**

```bash
mysql -u root -e "CREATE DATABASE semilleros_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root semilleros_db < backups/<fecha_hora>/base_datos.sql
```

**4. Rehacer el enlace de archivos públicos**

`public/storage` es un enlace simbólico a `storage/app/public`. No viaja dentro
del comprimido, hay que recrearlo:

```bash
php artisan storage:link
```

**5. Levantar el sistema**

```bash
./iniciar.sh
```

## Retención

El script no borra copias antiguas por su cuenta: prefiere ocupar disco a
eliminar un respaldo que pudiera hacer falta. Para revisar cuánto ocupan y
limpiar a mano:

```bash
du -sh backups/*
```

## Nota histórica

Hasta septiembre de 2026 este proceso se hacía con tres scripts de PowerShell
(`backup-semilleros.ps1`, `run-backup-semilleros.bat` y `register-backup-task.ps1`),
que buscaban `mysqldump.exe` en rutas de XAMPP y por tanto no funcionaban en
macOS. Se sustituyeron por el script `.sh` documentado aquí.
