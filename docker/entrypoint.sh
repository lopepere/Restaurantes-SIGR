#!/usr/bin/env bash
set -e

cd /var/www/html

# 1. Garantizar que exista el archivo .env
if [ ! -f ".env" ] && [ -f ".env.example" ]; then
    echo "[SIGR] Creando archivo .env a partir de .env.example..."
    cp .env.example .env
fi

# 2. Garantizar directorios de almacenamiento y caché requeridos por Laravel
mkdir -p \
    storage/app/public/productos \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache || true
chmod -R 775 storage bootstrap/cache || true

# 3. Si el volumen de vendor está vacío, poblarlo desde /opt/vendor-dist o ejecutar composer install
if [ ! -f "vendor/autoload.php" ]; then
    if [ -d "/opt/vendor-dist" ]; then
        echo "[SIGR] Copiando dependencias de Composer preinstaladas..."
        mkdir -p vendor
        cp -r /opt/vendor-dist/. vendor/
    else
        echo "[SIGR] Instalando dependencias de Composer..."
        composer install --no-interaction --prefer-dist --optimize-autoloader
    fi
fi

# 4. Si el volumen de public/build no tiene los activos compilados de Vite, restaurarlos desde /opt/public-build
if [ ! -f "public/build/manifest.json" ] && [ -d "/opt/public-build" ]; then
    echo "[SIGR] Copiando activos compilados de Vite a public/build..."
    mkdir -p public/build
    cp -r /opt/public-build/. public/build/
fi

# Eliminar archivo public/hot si quedó de un entorno local para que use los assets compilados
if [ -f "public/hot" ]; then
    rm -f public/hot
fi

# 5. Esperar a que MySQL esté disponible cuando DB_CONNECTION=mysql
if [ "${DB_CONNECTION:-mysql}" = "mysql" ]; then
    echo "[SIGR] Esperando conexión con MySQL en ${DB_HOST:-mysql}:${DB_PORT:-3306}..."
    MAX_TRIES=30
    COUNT=0
    until php -r "
        try {
            \$host = getenv('DB_HOST') ?: 'mysql';
            \$port = getenv('DB_PORT') ?: '3306';
            \$user = getenv('DB_USERNAME') ?: 'root';
            \$pass = getenv('DB_PASSWORD') ?: '';
            new PDO(\"mysql:host={\$host};port={\$port}\", \$user, \$pass, [PDO::ATTR_TIMEOUT => 3]);
            exit(0);
        } catch (Throwable \$e) {
            exit(1);
        }
    "; do
        COUNT=$((COUNT + 1))
        if [ "$COUNT" -ge "$MAX_TRIES" ]; then
            echo "[SIGR] Advertencia: Tiempo de espera agotado para MySQL, continuando..."
            break
        fi
        sleep 2
    done
fi

# 6. Tareas de inicialización exclusivas del contenedor principal PHP-FPM
if [ "$1" = "php-fpm" ]; then
    rm -f /tmp/app-ready

    # Descubrir paquetes de Laravel
    php artisan package:discover --ansi || true

    # Generar APP_KEY si está vacío
    if [ -z "${APP_KEY}" ] && ! grep -q "^APP_KEY=base64:" .env 2>/dev/null; then
        echo "[SIGR] Generando APP_KEY..."
        php artisan key:generate --force --ansi || true
    fi

    # Crear enlace simbólico de storage público
    if [ ! -L "public/storage" ]; then
        php artisan storage:link --force --ansi || true
    fi

    # Ejecutar migraciones automáticamente si AUTO_MIGRATE=true (por defecto true)
    if [ "${AUTO_MIGRATE:-true}" = "true" ]; then
        echo "[SIGR] Ejecutando migraciones de base de datos..."
        php artisan migrate --force --ansi || true
    fi

    # Ejecutar seeders iniciales si AUTO_SEED=true
    if [ "${AUTO_SEED:-false}" = "true" ]; then
        echo "[SIGR] Ejecutando seeders iniciales..."
        php artisan db:seed --force --ansi || true
    fi

    # Limpiar cachés previas para tomar siempre las variables actuales del .env
    php artisan optimize:clear --ansi || true

    chown -R www-data:www-data storage bootstrap/cache || true
    touch /tmp/app-ready
fi

echo "[SIGR] Iniciando proceso: $@"
exec "$@"
