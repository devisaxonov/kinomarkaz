FROM php:8.3-fpm-alpine

RUN apk add --no-cache pcre-dev $PHPIZE_DEPS \
    postgresql-dev \
    supervisor \
    linux-headers

# Composer o'rnatish
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN docker-php-ext-install pdo pdo_pgsql opcache

RUN pecl install redis && docker-php-ext-enable redis

COPY ./docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY ./docker/php/worker.conf /etc/supervisor/conf.d/worker.conf

WORKDIR /var/www/html

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/worker.conf"]
