# ==============================================================================
# Etapa 1: Compilación de activos Frontend (Vite + Vue 3 + Tailwind CSS 4)
# ==============================================================================
FROM node:22-alpine AS frontend-builder

WORKDIR /app

COPY package.json package-lock.json* ./
RUN npm ci --no-audit --no-fund

COPY vite.config.js ./
COPY resources ./resources
COPY public ./public

RUN npm run build

# ==============================================================================
# Etapa 2: Runtime de Aplicación (PHP 8.4-FPM Alpine) para Producción / VPS
# ==============================================================================
FROM php:8.4-fpm-alpine

LABEL maintainer="SIGR <dev@sigr.test>"
LABEL description="SIGR - Contenedor de aplicación Laravel con PHP-FPM 8.4 (sin php artisan serve)"

# Instalar paquetes del sistema y bibliotecas requeridas por las extensiones de PHP
RUN apk add --no-cache \
    bash \
    curl \
    git \
    unzip \
    tzdata \
    icu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    freetype-dev \
    oniguruma-dev \
    mysql-client \
    $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mysqli \
        bcmath \
        exif \
        gd \
        intl \
        mbstring \
        opcache \
        pcntl \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS \
    && rm -rf /tmp/pear

# Instalar Composer oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Configuración de PHP para producción / VPS
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-sigr.ini

WORKDIR /var/www/html

# Copiar código fuente de la aplicación
COPY . /var/www/html

# Instalar dependencias de Composer optimizadas
RUN composer install \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

# Copiar activos compilados de Vite desde la Etapa 1 y guardar respaldo en /opt/public-build
COPY --from=frontend-builder /app/public/build /var/www/html/public/build
RUN cp -r /var/www/html/public/build /opt/public-build && cp -r /var/www/html/vendor /opt/vendor-dist

# Asegurar estructura de directorios y permisos de Laravel
RUN mkdir -p \
    storage/app/public/productos \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Script de entrada (entrypoint)
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh && chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm", "-F"]
