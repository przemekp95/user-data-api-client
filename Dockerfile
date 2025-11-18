# PHP API container for development
FROM php:8.1-apache

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libjpeg-dev \
    unzip \
    && docker-php-ext-install pdo pdo_mysql \
    && rm -rf /var/lib/apt/lists/* \
    && apt-get clean

# Configure Apache
RUN a2enmod rewrite
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Configure PHP for development (can be overridden for production)
RUN echo "display_errors=On" >> /usr/local/etc/php/conf.d/errors.ini
RUN echo "error_reporting=E_ALL" >> /usr/local/etc/php/conf.d/errors.ini

# Set working directory
WORKDIR /var/www/html

# Copy composer files and install dependencies
COPY composer.json composer.lock* ./
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN if [ -f composer.lock ]; then composer install --no-dev --optimize-autoloader; else composer install --no-dev --optimize-autoloader --no-scripts --no-autoloader; fi

# Copy application code
COPY . .

# Set proper ownership
RUN chown -R www-data:www-data /var/www/html

# Switch to non-root user for security
USER www-data

# Basic health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/health || exit 1

EXPOSE 80
CMD ["apache2-foreground"]
