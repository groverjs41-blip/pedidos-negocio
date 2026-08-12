# ==========================================
# Stage: Base PHP 8.3 environment
# ==========================================
FROM php:8.3-fpm-alpine AS php-base

# Copy composer from official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install system dependencies and mysql-client (provides mysqladmin for healthchecks)
# Added icu-dev for intl PHP extension
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
    mysql-client \
    icu-dev

# Install PHP extensions required by Laravel, Filament, Reverb and Composer
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip opcache intl

# ==========================================
# Stage 1: Composer dependencies
# ==========================================
FROM php-base AS composer-stage

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
FROM php-base AS runtime-stage

# Configure production PHP Opcache
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

# Run Laravel package discovery and Filament upgrades (re-publishes components)
RUN rm -f bootstrap/cache/packages.php bootstrap/cache/services.php \
    && php artisan package:discover --ansi \
    && php artisan filament:upgrade --ansi

# Set permissions for storage and bootstrap cache directories
RUN chown -R www-data:www-data /var/www \
    && find /var/www/storage -type d -exec chmod 775 {} \; \
    && find /var/www/storage -type f -exec chmod 664 {} \; \
    && find /var/www/bootstrap/cache -type d -exec chmod 775 {} \; \
    && find /var/www/bootstrap/cache -type f -exec chmod 664 {} \;

EXPOSE 9000

CMD ["php-fpm"]

# ==========================================
# Stage 4: Nginx Web Server for Production
# ==========================================
FROM nginx:alpine AS web-stage

# Copy Nginx configuration file
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf

# Copy public directory from node-stage (contains icons, manifest, and Vite build output)
COPY --from=node-stage /app/public /var/www/public
