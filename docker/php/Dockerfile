FROM php:8.4-fpm

RUN apt-get update && apt-get install -y --no-install-recommends \
    git curl unzip libzip-dev libonig-dev libicu-dev \
    && docker-php-ext-install -j$(nproc) pdo_mysql zip mbstring intl bcmath pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY entrypoint.sh /usr/local/bin/agenda-entrypoint
RUN sed -i 's/\r$//' /usr/local/bin/agenda-entrypoint \
    && chmod +x /usr/local/bin/agenda-entrypoint

WORKDIR /var/www

ENTRYPOINT ["agenda-entrypoint"]
CMD ["php-fpm"]
