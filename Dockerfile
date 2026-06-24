# 1. Cambiamos a PHP 8.4 para cumplir con las dependencias de tus paquetes actualizados
FROM php:8.4-fpm-alpine

# Instalar dependencias del sistema necesarias para Postgres, Zip y optimizaciones de rendimiento
RUN apk add --no-cache \
    bash \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    icu-dev

# Instalar y habilitar las extensiones nativas de PHP indispensables
RUN docker-php-ext-install \
    pdo_pgsql \
    pgsql \
    zip \
    bcmath \
    intl \
    opcache

# Copiar Composer globalmente desde su imagen oficial
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar el directorio de trabajo dentro del contenedor
WORKDIR /var/www

# Copiar los archivos de dependencias primero (para aprovechar la caché de capas de Docker)
COPY composer.json composer.lock ./

# 🔥 CORRECCIÓN: Agregamos --ignore-platform-req=ext-gd para que ignore la extensión gráfica que pedía phpspreadsheet
RUN composer install --no-scripts --no-autoloader --no-dev --prefer-dist --ignore-platform-req=ext-gd

# Copiar todo el código fuente de tu aplicación Laravel al contenedor
COPY . .

# Generar el autoloader optimizado de Composer y ejecutar scripts de optimización
RUN composer dump-autoload --optimize --classmap-authoritative

# Asegurar los permisos correctos para las carpetas de almacenamiento y caché de Laravel
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Exponer el puerto nativo de PHP-FPM
EXPOSE 9000

# Por defecto, el contenedor iniciará en modo Web (PHP-FPM)
CMD ["php-fpm"]
