FROM php:8.4-fpm

WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        libicu-dev \
        libpq-dev \
        libsqlite3-dev \
        libzip-dev \
        unzip \
        zip \
    && docker-php-ext-install intl pdo_pgsql pdo_sqlite zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN git config --global --add safe.directory /var/www/html

COPY composer.json ./
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts

COPY . .

RUN composer dump-autoload --optimize \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
