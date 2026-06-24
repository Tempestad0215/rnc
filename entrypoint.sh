#!/usr/bin/env sh

set -e

echo "🚀 Ejecutando optimizaciones de caché..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "🔄 Corriendo migraciones pendientes..."
php artisan migrate --force

echo "✅ Todo listo. Iniciando PHP-FPM..."
exec php-fpm
