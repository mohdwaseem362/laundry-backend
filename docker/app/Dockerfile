# docker/app/Dockerfile
FROM php:8.2-fpm

# install system packages + PHP extensions (pcntl, pdo_pgsql, redis)
# install system deps and php redis extension
RUN apt-get update && apt-get install -y \
    libpq-dev \
    zlib1g-dev \
    libzip-dev \
    unzip \
    git \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && docker-php-ext-install pdo pdo_pgsql zip \
    && rm -rf /var/lib/apt/lists/*


# Install composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Use non-root user in production if required (optional)
