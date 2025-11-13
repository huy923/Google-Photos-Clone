# Use php version 8.2
FROM php:8.2-fpm

# Set working directory
WORKDIR /var/www/html

# Install required packages for Laravel
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev zip git unzip curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql

# Configure PHP for large file uploads
RUN echo "upload_max_filesize = 10G" > /usr/local/etc/php/conf.d/uploads.ini && \
    echo "post_max_size = 10G" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "max_input_time = 3600" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "max_execution_time = 3600" >> /usr/local/etc/php/conf.d/uploads.ini

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install wait-for-it script to wait for MySQL
RUN apt-get install -y netcat-traditional

# Copy source code
COPY . .

# Install dependencies Laravel
RUN composer install --no-interaction --optimize-autoloader

# Copy environment file and generate key
RUN cp .env.example .env || true
RUN php artisan key:generate --ansi || true

# CRITICAL: Create all required storage directories
RUN mkdir -p storage/framework/sessions \
    storage/framework/views \
    storage/framework/cache/data \
    storage/logs \
    bootstrap/cache

# Set proper permissions for storage and cache
RUN chown -R www-data:www-data storage bootstrap/cache
RUN chmod -R 775 storage bootstrap/cache

EXPOSE 9000

# Run migrations and start PHP-FPM
CMD php artisan config:clear && \
    php artisan cache:clear && \
    php artisan migrate --force && \
    php artisan storage:link || true && \
    php-fpm