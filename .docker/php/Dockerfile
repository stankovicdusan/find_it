FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    git unzip libicu-dev libzip-dev libxml2-dev libpng-dev libonig-dev libpq-dev \
 && docker-php-ext-install intl pdo_mysql opcache zip

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# PHP ini
COPY php.ini /usr/local/etc/php/conf.d/zz-php.ini
