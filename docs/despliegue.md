# Guía de Despliegue en Producción — SIGR v1.0.0

Esta guía describe el procedimiento estándar para desplegar el **Sistema Integral de Gestión de Restaurante (SIGR)** en un entorno de producción basado en Linux (Ubuntu 22.04 LTS o 24.04 LTS).

---

## 1. Arquitectura de Producción Recomendada

El sistema está diseñado como un **Monolito Modular** de alto rendimiento con comunicación reactiva bidireccional:

```
                  +-----------------------------------+
                  |   Cliente Web / Terminal Mesero   |
                  +-----------------+-----------------+
                                    |
                           HTTPS / WSS (Port 443)
                                    |
                  +-----------------v-----------------+
                  |       Nginx (Reverse Proxy)       |
                  +--------+-----------------+--------+
                           |                 |
                HTTP (PHP FastCGI)      WebSockets (/app)
                           |                 |
             +-------------v----+     +------v-------------+
             |   PHP-FPM 8.3+   |     |   Laravel Reverb   |
             |   (SIGR Core)    |     |   (Puerto 8080)    |
             +--------+---------+     +--------------------+
                      |
        +-------------+-------------+
        |                           |
+-------v-------+           +-------v-------+
|  MySQL 8.0+   |           |    Redis 7    |
| (Almacenamiento           | (Colas, Caché |
|  Transaccional            |  y Sesiones)  |
|  e Inmutable) |           |               |
+---------------+           +---------------+
```

### Componentes de la Pila Tecnológica:
- **Sistema Operativo:** Ubuntu Server 22.04 LTS o 24.04 LTS.
- **Servidor Web / Proxy Inverso:** Nginx 1.22+.
- **Motor de Ejecución:** PHP 8.3 o 8.4 (con extensiones `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `gd`, `intl`, `mbstring`, `openssl`, `pcre`, `pdo_mysql`, `redis`, `tokenizer`, `xml`, `zip`).
- **Base de Datos Principal:** MySQL 8.0+ (InnoDB, UTF8MB4, `utf8mb4_unicode_ci`).
- **Caché y Colas:** Redis 7.0+.
- **Servidor WebSockets:** Laravel Reverb (Daemon gestionado por Supervisor en puerto interno `8080`).
- **Administrador de Procesos:** Supervisor (`supervisor`).
- **Programador de Tareas:** Crontab del sistema (`schedule:run`).

---

## 2. Requisitos Previos e Instalación de Paquetes en el Servidor

Ejecuta los siguientes comandos en el servidor para actualizar el sistema e instalar las dependencias básicas:

```bash
# Actualizar repositorios y paquetes
sudo apt update && sudo apt upgrade -y

# Instalar herramientas esenciales
sudo apt install -y git curl unzip zip supervisor nginx redis-server software-properties-common

# Agregar repositorio de PHP (Ondrej Sury)
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Instalar PHP y extensiones requeridas
sudo apt install -y php8.3-fpm php8.3-cli php8.3-mysql php8.3-curl php8.3-gd \
    php8.3-mbstring php8.3-xml php8.3-zip php8.3-bcmath php8.3-intl php8.3-redis

# Instalar Composer globalmente
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

# Instalar Node.js LTS (v20 o superior)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

---

## 3. Despliegue Paso a Paso

### 3.1. Clonar el Repositorio y Permisos
```bash
# Directorio estándar de la aplicación
sudo mkdir -p /var/www/sigr
sudo chown -R $USER:www-data /var/www/sigr

# Clonar el proyecto
git clone <URL_REPOSITORIO_GIT> /var/www/sigr
cd /var/www/sigr

# Establecer permisos de escritura para directorios de Laravel
sudo chown -R www-data:www-data /var/www/sigr/storage /var/www/sigr/bootstrap/cache
sudo chmod -R 775 /var/www/sigr/storage /var/www/sigr/bootstrap/cache
```

### 3.2. Configuración de Entorno (`.env`)
Copia la plantilla y edita las variables críticas:

```bash
cp .env.example .env
nano .env
```

Ajusta los siguientes parámetros para producción:
```ini
APP_NAME=SIGR
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://sigr.tudominio.com
APP_TIMEZONE=America/Bogota

LOG_CHANNEL=daily
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sigr_produccion
DB_USERNAME=sigr_app
DB_PASSWORD=ContraseñaUltraSegura_2026!

DB_AUDIT_CONNECTION=mysql_audit
DB_AUDIT_HOST=127.0.0.1
DB_AUDIT_PORT=3306
DB_AUDIT_DATABASE=sigr_produccion
DB_AUDIT_USERNAME=sigr_audit
DB_AUDIT_PASSWORD=ContraseñaAuditSegura_2026!

CACHE_STORE=redis
CACHE_PREFIX=sigr_cache_

QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

BROADCAST_CONNECTION=reverb

REVERB_APP_ID=100001
REVERB_APP_KEY=sigr_reverb_key_produccion_2026
REVERB_APP_SECRET=sigr_reverb_secret_produccion_2026
REVERB_HOST="sigr.tudominio.com"
REVERB_PORT=443
REVERB_SCHEME="https"

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

### 3.3. Instalación de Dependencias PHP y Compilación Frontend
```bash
# Instalar dependencias de PHP optimizadas para producción
composer install --no-dev --optimize-autoloader

# Generar llave de aplicación (si es instalación inicial)
php artisan key:generate

# Enlazar directorio de almacenamiento público
php artisan storage:link

# Instalar dependencias JS y compilar activos para producción
npm ci
npm run build
```

### 3.4. Creación de Usuarios de Base de Datos y Permisos de Inmutabilidad
En la consola de MySQL (`sudo mysql`):

```sql
CREATE DATABASE sigr_produccion CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Usuario operativo de la aplicación
CREATE USER 'sigr_app'@'127.0.0.1' IDENTIFIED BY 'ContraseñaUltraSegura_2026!';
GRANT ALL PRIVILEGES ON sigr_produccion.* TO 'sigr_app'@'127.0.0.1';

-- Usuario dedicado de auditoría inmutable (Solo SELECT e INSERT)
CREATE USER 'sigr_audit'@'127.0.0.1' IDENTIFIED BY 'ContraseñaAuditSegura_2026!';
GRANT SELECT, INSERT ON sigr_produccion.activity_log TO 'sigr_audit'@'127.0.0.1';

FLUSH PRIVILEGES;
```

### 3.5. Migraciones y Seeders
```bash
# Ejecutar migraciones
php artisan migrate --force

# Cargar catálogo inicial de roles, configuración y datos maestros
php artisan db:seed --force
```

### 3.6. Optimización de Caché de Laravel
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

---

## 4. Configuración de Daemons con Supervisor

Para garantizar alta disponibilidad en el procesamiento de colas asíncronas y el servidor WebSocket Reverb, creamos dos perfiles en Supervisor:

### 4.1. Worker de Colas (`/etc/supervisor/conf.d/sigr-worker.conf`)
```ini
[program:sigr-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/sigr/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600 --timeout=90
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/sigr/storage/logs/worker.log
stopwaitsecs=3600
```

### 4.2. Servidor WebSocket Reverb (`/etc/supervisor/conf.d/sigr-reverb.conf`)
```ini
[program:sigr-reverb]
process_name=%(program_name)s
command=php /var/www/sigr/artisan reverb:start --host=127.0.0.1 --port=8080
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/sigr/storage/logs/reverb.log
stopwaitsecs=60
```

### 4.3. Iniciar Daemons
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start sigr-worker:*
sudo supervisorctl start sigr-reverb
```

---

## 5. Programador de Tareas (Laravel Scheduler)

Para automatizar la publicación/retiro de promociones por franja horaria (`PublicarPromocionesJob`), la depuración de carritos abandonados y copias de seguridad:

Edita el crontab del usuario `www-data`:
```bash
sudo crontab -u www-data -e
```

Agrega la siguiente línea:
```cron
* * * * * cd /var/www/sigr && php artisan schedule:run >> /dev/null 2>&1
```

---

## 6. Configuración de Nginx y Certificado TLS

Crea el archivo de configuración del virtual host en `/etc/nginx/sites-available/sigr.conf`:

```nginx
# Redirección HTTP a HTTPS
server {
    listen 80;
    listen [::]:80;
    server_name sigr.tudominio.com;
    return 301 https://$host$request_uri;
}

# Servidor Principal HTTPS
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name sigr.tudominio.com;

    root /var/www/sigr/public;
    index index.php index.html;

    # Certificados SSL (Certbot / Let's Encrypt)
    ssl_certificate /etc/letsencrypt/live/sigr.tudominio.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/sigr.tudominio.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    # Encabezados de Seguridad
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    charset utf-8;
    client_max_body_size 20M;

    # Proxy Reverso para Laravel Reverb (WebSockets)
    location /app {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto https;
        proxy_read_timeout 60s;
        proxy_connect_timeout 60s;
    }

    # Despacho de la aplicación Laravel (Inertia.js / PHP-FPM)
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        fastcgi_read_timeout 120;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Habilita el sitio y recarga Nginx:
```bash
sudo ln -s /etc/nginx/sites-available/sigr.conf /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

Para generar los certificados gratuitos con Let's Encrypt:
```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d sigr.tudominio.com
```

---

## 7. Procedimiento de Actualización Continua (Deploy Script)

Para desplegar futuras versiones sin tiempo de inactividad, puedes ejecutar el siguiente script automatizado:

```bash
#!/usr/bin/env bash
set -e

echo "Desplegando actualización de SIGR..."

# Activar modo de mantenimiento con bypass para administradores
php artisan down --secret="mantenimiento-sigr-2026"

# Descargar cambios de código
git pull origin main

# Actualizar dependencias de PHP
composer install --no-dev --prefer-dist --optimize-autoloader

# Ejecutar migraciones de base de datos
php artisan migrate --force

# Recompilar Frontend si hubo cambios en Vue o Tailwind
npm ci
npm run build

# Limpiar y regenerar cachés
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Reiniciar trabajadores de colas y Reverb
sudo supervisorctl restart sigr-worker:*
sudo supervisorctl restart sigr-reverb

# Desactivar modo de mantenimiento
php artisan up

echo "¡Despliegue completado con éxito!"
```

---

## 8. Verificación Post-Despliegue

Tras completar el despliegue, verifica los siguientes puntos clave:

1. **Estado de Servicios:**
   ```bash
   sudo systemctl status nginx
   sudo systemctl status php8.3-fpm
   sudo systemctl status redis-server
   sudo supervisorctl status
   ```
2. **Conexión WebSocket Reverb:**
   Abre la consola de desarrollador en el navegador (`F12` -> `Network` -> `WS`) en `https://sigr.tudominio.com` y verifica que la conexión a `wss://sigr.tudominio.com/app` establezca estado `101 Switching Protocols`.
3. **Registro de Auditoría Inmutable:**
   Verifica que los inicios de sesión y modificaciones de registros se persistan en la tabla `activity_log`.
4. **Comprobación de Logs:**
   ```bash
   tail -f storage/logs/laravel.log
   tail -f storage/logs/worker.log
   tail -f storage/logs/reverb.log
   ```

