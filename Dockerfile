FROM php:8.2-fpm-alpine
RUN apk add --no-cache sqlite-dev \
    && docker-php-ext-install pdo pdo_sqlite
WORKDIR /var/www/html
