# Tutorial de instalación

Sistema de Gestión de Semilleros de Investigación. Instalación y configuración en un computador personal y en un servidor.

## 1. Presentación

Este tutorial explica, paso a paso, cómo instalar y configurar el Sistema de Gestión de Semilleros de Investigación. Está escrito para la persona encargada de sistemas que deba poner en marcha la aplicación.

El documento tiene dos procedimientos. El primero (sección 3) instala el sistema en un computador personal, para pruebas o desarrollo. El segundo (sección 4) lo instala en un servidor, para que los usuarios entren desde la red o desde Internet.

El código fuente está publicado en un repositorio de GitHub. Todos los comandos de este tutorial se tomaron de ese código.

- Repositorio del sistema: https://github.com/monterolucerojuanjose-alt/sistema-semilleros
- Manual de usuario: https://github.com/monterolucerojuanjose-alt/sistema-semilleros/blob/main/docs/manual/Manual_de_Usuario_Sistema_Semilleros.pdf

## 2. Requisitos

### 2.1. Programas necesarios

La Tabla 1 presenta los programas que deben estar instalados antes de empezar. Son los mismos para el computador personal y para el servidor.

**Tabla 1.** Programas necesarios

| Programa | Versión | Para qué se usa |
|---|---|---|
| PHP | 8.2, 8.3 o 8.4 | Ejecuta la aplicación (Laravel 11). |
| Composer | 2 | Descarga las librerías de PHP. |
| Node.js y npm | 18 o superior | Compila la interfaz (Vue 3, Vite 5 y Tailwind CSS 3). |
| MySQL o MariaDB | MySQL 8 o MariaDB 10.4 o superior | Guarda los datos del sistema. |
| Git | Cualquier versión reciente | Descarga el código desde GitHub. |
| Nginx o Apache | Cualquier versión reciente | Solo en el servidor: atiende las peticiones web. |

*Nota.* Programas requeridos para instalar el sistema. Elaboración propia a partir del archivo README.md y de composer.json del repositorio.

### 2.2. Extensiones de PHP

PHP debe tener activas estas extensiones: pdo_mysql, mbstring, openssl, tokenizer, xml, curl, gd, zip, bcmath y fileinfo. Para ver las extensiones activas se usa el siguiente comando:

```
php -m
```

### 2.3. Requisitos del equipo

La Tabla 2 presenta una recomendación para el servidor. Los valores son una referencia para un uso institucional moderado y deben ajustarse según el número de usuarios y de archivos.

**Tabla 2.** Recursos recomendados para el servidor

| Recurso | Recomendación |
|---|---|
| Sistema operativo | Ubuntu Server 22.04 o 24.04 LTS (64 bits). También funciona en otras distribuciones de Linux. |
| Procesador | 2 núcleos. |
| Memoria RAM | 2 GB como mínimo; 4 GB recomendado. |
| Disco | 20 GB libres. Los proyectos y certificados se guardan como archivos (cada documento puede pesar hasta 10 MB). |
| Red | Dirección IP fija o nombre de dominio. Puertos 80 (HTTP) y 443 (HTTPS) abiertos. |
| Correo | Una cuenta SMTP, si se desea que el sistema envíe correos de notificación. |

*Nota.* Recomendación de recursos para el servidor. Elaboración propia.

## 3. Instalación en un computador personal

Este procedimiento sirve en Windows, macOS y Linux. Los comandos se escriben en una terminal, dentro de la carpeta del proyecto.

### Paso 1. Descargar el código

```
git clone https://github.com/monterolucerojuanjose-alt/sistema-semilleros.git
cd sistema-semilleros
```

### Paso 2. Instalar las dependencias

El primer comando descarga las librerías de PHP y el segundo las de la interfaz.

```
composer install
npm install
```

### Paso 3. Crear el archivo de configuración

El archivo .env guarda la configuración de la instalación. Se crea como copia de .env.example. En Windows se usa copy en lugar de cp.

```
cp .env.example .env
php artisan key:generate
```

### Paso 4. Crear la base de datos

En el cliente de MySQL o MariaDB se ejecuta:

```
CREATE DATABASE semilleros_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Después se abre el archivo .env y se escriben los datos de conexión. Los valores de usuario y contraseña dependen de cada equipo.

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=semilleros_db
DB_USERNAME=root
DB_PASSWORD=
```

### Paso 5. Crear las tablas y el primer administrador

El primer comando crea todas las tablas, los cinco roles y los permisos. La instalación queda en blanco: sin usuarios, semilleros ni proyectos de ejemplo.

```
php artisan migrate --seed
```

El segundo comando crea el primer usuario administrador. Pide el nombre, el correo y una contraseña de mínimo 8 caracteres.

```
php artisan semilleros:crear-administrador
```

### Paso 6. Activar el almacenamiento público

Este comando permite ver desde el navegador los archivos subidos, como las fotos de perfil y los certificados.

```
php artisan storage:link
```

### Paso 7. Iniciar el sistema

El sistema necesita dos procesos al mismo tiempo. Cada uno se ejecuta en una terminal distinta.

```
php artisan serve
npm run dev
```

En macOS y Linux los dos procesos se pueden iniciar con un solo comando:

```
./iniciar.sh
```

El sistema queda disponible en la dirección http://127.0.0.1:8000.

### Paso 8. Entrar al sistema

Se inicia sesión con el correo y la contraseña del administrador creado en el Paso 5. Desde la aplicación, el administrador crea los demás usuarios, los semilleros, los eventos y las rúbricas. Los estudiantes también pueden registrarse desde la pantalla de inicio de sesión.

### Paso 9 (opcional). Cargar datos de demostración

Para ver el sistema con datos de ejemplo se ejecuta el siguiente comando. Solo debe usarse en una base de datos de pruebas, porque las contraseñas de estos usuarios son públicas.

```
php artisan db:seed --class=DatosDemostracionSeeder
```

Este comando crea semilleros, proyectos, eventos, rúbricas, evaluaciones y certificados de ejemplo, y un usuario por cada rol (Tabla 3). La contraseña de todos es password.

**Tabla 3.** Usuarios de demostración por rol

| Rol | Correo |
|---|---|
| Administrador | admin.rol@umariana.edu.co |
| Coordinador | coordinador.rol@umariana.edu.co |
| Docente/Tutor | tutor.rol@umariana.edu.co |
| Estudiante | estudiante.rol@umariana.edu.co |
| Jurado | jurado.rol@umariana.edu.co |

*Nota.* Usuarios que crea el comando de datos de demostración. También se crea el usuario admin@semilleros.edu con la contraseña admin123. Estas claves son solo para pruebas.

## 4. Instalación en un servidor

Este procedimiento usa Ubuntu Server 24.04 LTS, Nginx, PHP 8.3 y MariaDB. En el servidor no se usan php artisan serve ni npm run dev: Nginx atiende las peticiones y la interfaz se compila una sola vez.

En los comandos, el texto en mayúsculas (por ejemplo, CLAVE_SEGURA o midominio.edu.co) debe reemplazarse por los valores reales.

### Paso 1. Instalar los programas

```
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx mariadb-server git unzip composer nodejs npm \
  php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring php8.3-xml \
  php8.3-curl php8.3-gd php8.3-zip php8.3-bcmath
```

Después se comprueban las versiones. PHP debe ser 8.2 o superior y Node.js 18 o superior.

```
php -v
composer --version
node -v
```

### Paso 2. Crear la base de datos y su usuario

Primero se asegura la instalación de MariaDB y después se abre su consola.

```
sudo mysql_secure_installation
sudo mariadb
```

Dentro de la consola se crean la base de datos y un usuario propio para el sistema. No se recomienda usar el usuario root.

```
CREATE DATABASE semilleros_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'semilleros'@'localhost' IDENTIFIED BY 'CLAVE_SEGURA';
GRANT ALL PRIVILEGES ON semilleros_db.* TO 'semilleros'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Paso 3. Descargar el código

```
cd /var/www
sudo git clone https://github.com/monterolucerojuanjose-alt/sistema-semilleros.git
sudo mv sistema-semilleros semilleros
sudo chown -R $USER:www-data /var/www/semilleros
cd /var/www/semilleros
```

### Paso 4. Instalar las dependencias y compilar la interfaz

En el servidor se instalan solo las librerías necesarias para funcionar. El comando npm run build genera la carpeta public/build con la interfaz compilada.

```
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

### Paso 5. Configurar el archivo .env

```
cp .env.example .env
php artisan key:generate
nano .env
```

En el archivo .env se cambian estas líneas:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://midominio.edu.co

DB_DATABASE=semilleros_db
DB_USERNAME=semilleros
DB_PASSWORD=CLAVE_SEGURA
```

APP_DEBUG debe quedar en false. Si queda en true, los errores muestran información interna del sistema a cualquier visitante.

Para que el sistema envíe correos reales, también se cambian las líneas MAIL_ con los datos de la cuenta SMTP de la institución:

```
MAIL_MAILER=smtp
MAIL_HOST=SERVIDOR_SMTP
MAIL_PORT=587
MAIL_USERNAME=USUARIO_SMTP
MAIL_PASSWORD=CLAVE_SMTP
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="semilleros@midominio.edu.co"
```

### Paso 6. Crear las tablas y los datos iniciales

El primer comando crea las tablas, los cinco roles y los permisos. El segundo crea el primer administrador: pide el nombre, el correo y una contraseña de mínimo 8 caracteres. El tercero activa el almacenamiento público.

```
php artisan migrate --seed --force
php artisan semilleros:crear-administrador
php artisan storage:link
```

La instalación queda en blanco. Con la cuenta del administrador se configura todo desde la aplicación: usuarios y roles, semilleros, eventos y rúbricas.

En un servidor real no se cargan los datos de demostración, porque sus contraseñas son públicas.

### Paso 7. Dar permisos de escritura

El servidor web debe poder escribir en dos carpetas: storage (archivos subidos, sesiones y registros) y bootstrap/cache.

```
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### Paso 8. Permitir archivos de hasta 10 MB

El sistema acepta documentos de proyecto y certificados de hasta 10 MB. PHP permite menos por defecto, así que se edita su configuración:

```
sudo nano /etc/php/8.3/fpm/php.ini
```

Se buscan estas dos líneas y se dejan así:

```
upload_max_filesize = 12M
post_max_size = 12M
```

```
sudo systemctl restart php8.3-fpm
```

### Paso 9. Configurar Nginx

Se crea el archivo del sitio:

```
sudo nano /etc/nginx/sites-available/semilleros
```

Con este contenido. La carpeta pública del sitio es public, no la raíz del proyecto.

```
server {
    listen 80;
    server_name midominio.edu.co;
    root /var/www/semilleros/public;
    index index.php;
    charset utf-8;
    client_max_body_size 12M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Después se activa el sitio, se revisa la configuración y se reinicia Nginx.

```
sudo ln -s /etc/nginx/sites-available/semilleros /etc/nginx/sites-enabled/
sudo rm /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx
```

### Paso 10. Activar el envío de correos en segundo plano

El sistema envía los correos de notificación mediante una cola de trabajos guardada en la base de datos. Para que los correos salgan, debe existir un proceso que atienda esa cola. Se crea como servicio:

```
sudo nano /etc/systemd/system/semilleros-cola.service
```

```
[Unit]
Description=Cola de trabajos del Sistema de Semilleros
After=network.target mariadb.service

[Service]
User=www-data
WorkingDirectory=/var/www/semilleros
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=3 --max-time=3600
Restart=always

[Install]
WantedBy=multi-user.target
```

```
sudo systemctl daemon-reload
sudo systemctl enable --now semilleros-cola
```

### Paso 11. Activar HTTPS

El sistema maneja contraseñas y datos personales, por lo que debe publicarse con HTTPS. Con un nombre de dominio que apunte al servidor, el certificado gratuito de Let’s Encrypt se instala así:

```
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d midominio.edu.co
```

### Paso 12. Optimizar

Este comando guarda en caché la configuración, las rutas y las vistas. Debe repetirse cada vez que se cambie el archivo .env.

```
php artisan optimize
```

El sistema queda disponible en la dirección configurada en APP_URL.

## 5. Verificación de la instalación

El sistema incluye dos comandos propios para comprobar que todo quedó bien. El primero revisa la conexión con la base de datos y muestra estadísticas. El segundo muestra el estado general del sistema.

```
php artisan db:check
php artisan sistema:estado
```

Además se recomienda hacer estas comprobaciones en el navegador:

- La dirección /up responde sin error. Es la ruta de comprobación de estado de Laravel.
- La página de inicio de sesión se ve con sus estilos.
- El administrador creado puede entrar y ver su panel.
- Se puede subir un documento de proyecto y volver a descargarlo.

Para asignar un rol a un usuario desde la terminal se usa este comando. Los roles válidos son Administrador, Coordinador, Docente/Tutor, Estudiante y Jurado.

```
php artisan user:assign-role correo@umariana.edu.co Coordinador
```

## 6. Copias de seguridad y actualización

### 6.1. Copias de seguridad

El guion scripts/backup-semilleros.sh crea una carpeta dentro de backups con tres archivos: el volcado de la base de datos, el código comprimido (incluye .env y los archivos subidos) y un manifiesto con los pasos para restaurar. Necesita el programa mysqldump.

```
./scripts/backup-semilleros.sh
```

Para hacer una copia diaria automática, por ejemplo a las 22:30, se agrega esta línea con el comando crontab -e:

```
30 22 * * * cd /var/www/semilleros && ./scripts/backup-semilleros.sh
```

### 6.2. Actualizar el sistema

Cuando haya una versión nueva en el repositorio, se hace primero una copia de seguridad y después se ejecuta:

```
cd /var/www/semilleros
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan optimize
sudo systemctl restart semilleros-cola
```

## 7. Solución de problemas

La Tabla 4 reúne los problemas más comunes durante la instalación y la forma de resolverlos.

**Tabla 4.** Problemas frecuentes y su solución

| Problema | Solución |
|---|---|
| No conecta con la base de datos. | Ejecutar php artisan db:check. Revisar que MySQL o MariaDB esté iniciado y que los datos DB_ del archivo .env sean correctos. |
| Aparece un error que dice que las tablas no existen. | Ejecutar php artisan migrate. |
| La página se ve sin estilos o en blanco. | En el computador personal, revisar que npm run dev esté en ejecución. En el servidor, ejecutar npm run build. |
| El puerto 8000 está ocupado (computador personal). | Ejecutar php artisan serve --port=8001. |
| Error 500 en el servidor. | Revisar el archivo storage/logs/laravel.log y repetir el Paso 7 (permisos). |
| Error 413 o fallo al subir un archivo grande. | Repetir los Pasos 8 y 9: límites de PHP y client_max_body_size de Nginx. |
| Los correos no llegan. | Revisar las líneas MAIL_ del archivo .env y que el servicio semilleros-cola esté activo (sudo systemctl status semilleros-cola). |
| Un cambio en .env no tiene efecto. | Ejecutar php artisan optimize:clear y después php artisan optimize. |
| Las fotos de perfil o los certificados no se ven. | Ejecutar php artisan storage:link. |

*Nota.* Problemas frecuentes de instalación. Elaboración propia a partir del archivo README.md del repositorio.

## 8. Enlaces

- Código fuente del sistema: https://github.com/monterolucerojuanjose-alt/sistema-semilleros
- Manual de usuario: https://github.com/monterolucerojuanjose-alt/sistema-semilleros/blob/main/docs/manual/Manual_de_Usuario_Sistema_Semilleros.pdf
- Documentación de despliegue de Laravel 11: https://laravel.com/docs/11.x/deployment
