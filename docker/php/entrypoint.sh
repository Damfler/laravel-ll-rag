#!/bin/sh
set -e

# Фиксим права на storage при каждом старте контейнера
# (volume-mount перекрывает права выставленные в Dockerfile)
mkdir -p /var/www/storage/framework/{sessions,views,cache} \
         /var/www/storage/logs \
         /var/www/bootstrap/cache

chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Запускаем оригинальный entrypoint PHP-FPM
exec docker-php-entrypoint "$@"
