# Use official PHP 8.3 with Apache
FROM php:8.3-apache

# Install PHP extensions for MySQL
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache rewrite
RUN a2enmod rewrite

# Copy project files
COPY . /var/www/html/

# Set working directory
WORKDIR /var/www/html

# Install Composer (from official Composer image)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install dependencies without dev packages
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress || true

# Set Apache DocumentRoot to graphql folder
ENV APACHE_DOCUMENT_ROOT=/var/www/html/graphql

# Update Apache configs to new DocumentRoot
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Copy entrypoint script
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Expose (Railway will inject $PORT)
EXPOSE 8080

# Start container using entrypoint
ENTRYPOINT ["/entrypoint.sh"]
