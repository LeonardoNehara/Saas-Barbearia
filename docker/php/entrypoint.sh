#!/bin/sh
set -eu

umask 0002
mkdir -p storage/app/private storage/app/public storage/logs \
    storage/framework/views storage/framework/cache/data \
    storage/framework/sessions storage/framework/testing bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache
find storage bootstrap/cache -type d -exec chmod 2775 {} +
find storage bootstrap/cache -type f -exec chmod 0664 {} +

exec docker-php-entrypoint "$@"
