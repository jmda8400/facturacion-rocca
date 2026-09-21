FROM composer:2 AS dependencies
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --prefer-dist --optimize-autoloader

FROM php:8.3-fpm-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends curl nginx supervisor openssl libicu-dev libonig-dev libxml2-dev libzip-dev libpng-dev \
    && docker-php-ext-install -j"$(nproc)" bcmath gd intl mbstring opcache pdo_mysql soap zip \
    && rm -rf /var/lib/apt/lists/* /var/www/html /var/log/nginx/*

WORKDIR /var/www/app
COPY --from=dependencies /app/vendor ./vendor
COPY . .
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/facturacion.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint

RUN chmod +x artisan /usr/local/bin/entrypoint \
    && mkdir -p bootstrap/cache storage/app/private storage/framework/cache storage/framework/sessions storage/framework/views storage/logs \
    && chown -R www-data:www-data bootstrap/cache storage

EXPOSE 8080
ENTRYPOINT ["entrypoint"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/supervisord.conf"]
