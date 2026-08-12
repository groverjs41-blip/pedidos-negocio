# ==========================================
# Stage 1: Composer dependencies
# ==========================================
FROM composer:latest AS composer-stage
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts --prefer-dist

# ==========================================
# Stage 2: Node asset compilation
# ==========================================
FROM node:20-alpine AS node-stage
WORKDIR /app
COPY package.json package-lock.json vite.config.js ./
COPY resources/ ./resources/
COPY public/ ./public/
RUN npm ci
RUN npm run build

# ==========================================
# Stage 3: PHP 8.3 FPM runtime environment
# ==========================================
FROM php:8.3-fpm-alpine AS runtime-stage

# Install system dependencies and mysql-client (provides mysqladmin for healthchecks)
RUN apk add --no-cache \
    bash \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    oniguruma-dev \
    libzip-dev \
    mysql-client

# Install PHP extensions required by Laravel, Filament and Reverb
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip opcache

# Configure production-ready PHP Opcache
RUN { \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=8'; \
    echo 'opcache.max_accelerated_files=4000'; \
    echo 'opcache.revalidate_freq=2'; \
    echo 'opcache.fast_shutdown=1'; \
    echo 'opcache.enable_cli=1'; \
} > /usr/local/etc/php/conf.d/opcache-recommended.ini

# Set working directory
WORKDIR /var/www

# Copy application source code (excluding files in .dockerignore)
COPY . /var/www

# Copy composer vendor folder from composer-stage
COPY --from=composer-stage /app/vendor /var/www/vendor

# Copy compiled frontend assets from node-stage
COPY --from=node-stage /app/public/build /var/www/public/build

# Set permissions for storage and bootstrap cache directories
RUN chown -R www-data:www-data /var/www \
    && find /var/www/storage -type d -exec chmod 775 {} \; \
    && find /var/www/storage -type f -exec chmod 664 {} \; \
    && find /var/www/bootstrap/cache -type d -exec chmod 775 {} \; \
    && find /var/www/bootstrap/cache -type f -exec chmod 664 {} \;

EXPOSE 9000

CMD ["php-fpm"]
