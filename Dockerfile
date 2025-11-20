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

# Configure virtual host for public directory
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html/public\n\
    DirectoryIndex index.php index.html\n\
    <Directory /var/www/html/public>\n\
        AllowOverride All\n\
        Require all granted\n\
        Options Indexes FollowSymLinks MultiViews\n\
    </Directory>\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

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

# Run tests during build to verify image quality (fails build if tests fail)
RUN composer test --no-interaction

# Set proper ownership
RUN chown -R www-data:www-data /var/www/html

# Switch to non-root user for security
USER www-data

# Use actual API endpoint for health check (simpler than separate /health endpoint)
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost?id=1 || exit 1

EXPOSE 80
CMD ["apache2-foreground"]
