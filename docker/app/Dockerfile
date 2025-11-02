# docker/app/Dockerfile
FROM php:8.2-fpm

# install system packages + PHP extensions (pcntl, pdo_pgsql, redis)
RUN apt-get update && apt-get install -y \
    git unzip zip libzip-dev libonig-dev libpng-dev libicu-dev libxml2-dev \
    supervisor procps curl zlib1g-dev libpq-dev build-essential \
  && docker-php-ext-install pdo_pgsql pgsql mbstring zip exif intl pcntl \
  && pecl install redis \
  && docker-php-ext-enable redis \
  && rm -rf /var/lib/apt/lists/*

# Install composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Use non-root user in production if required (optional)
