FROM php:8.3-fpm-alpine

RUN apk add --no-cache linux-headers && docker-php-ext-install sockets

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
