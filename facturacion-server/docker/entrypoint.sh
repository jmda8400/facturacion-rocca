#!/bin/sh
set -eu

required="APP_KEY APP_URL BILLING_API_KEYS SETTINGS_PASSWORD DB_HOST DB_DATABASE DB_USERNAME DB_PASSWORD"
for variable in $required; do
    eval "value=\${$variable:-}"
    if [ -z "$value" ]; then
        echo "ERROR: la variable $variable es obligatoria." >&2
        exit 1
    fi
done

mkdir -p bootstrap/cache storage/app/private storage/framework/cache storage/framework/sessions storage/framework/views storage/logs
chown -R www-data:www-data bootstrap/cache storage

php artisan config:cache
php artisan view:cache
php artisan migrate --force

exec "$@"
