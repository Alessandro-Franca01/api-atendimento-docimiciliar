FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    libzip-dev libpng-dev libonig-dev libxml2-dev libicu-dev zip unzip git curl \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd zip intl \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
