FROM php:8.4-fpm-bullseye

# Set environment variables
ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=UTC

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    zip \
    unzip \
    libfcgi-bin \
    libpq-dev \
    libssl-dev \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    redis \
    pgsql \
    pdo_pgsql \
    oci8 \
    pdo_oci \
    pdo_sqlsrv \
    ftp \
    intl \
    opcache \
    zip \
    posix

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Custom PHP configuration
COPY docker/php/custom-limits.ini /usr/local/etc/php/conf.d/custom-limits.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Set working directory
WORKDIR /var/www

# Copy composer files first for better caching
COPY composer.json composer.lock ./

# Install dependencies (without scripts to avoid failure if files missing)
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts --no-autoloader

# Copy existing application directory contents
COPY . .

# Finish composer
RUN composer dump-autoload --optimize

# Clear caches to avoid old configuration issues
RUN php artisan config:clear || true \
    && php artisan route:clear || true \
    && php artisan view:clear || true

# Set permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Ensure PHP-FPM listens on all interfaces
RUN sed -i 's/listen = 127.0.0.1:9000/listen = 9000/' /usr/local/etc/php-fpm.d/www.conf

# Copy and make entrypoint executable
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Expose port 9000 and start php-fpm server
EXPOSE 9000
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]

