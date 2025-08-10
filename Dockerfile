# Use the official PHP 8.3 image with Apache
FROM php:8.3-apache

# Install required PHP extensions
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy project files
COPY . /var/www/html/

# Set working directory
WORKDIR /var/www/html

# Install Composer (from official composer image)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

# Set Apache document root to the graphql folder
ENV APACHE_DOCUMENT_ROOT=/var/www/html/graphql

# Update Apache configuration to use the new document root
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Expose (Railway injects $PORT; we remap Apache to it in CMD)
EXPOSE 80

# Bind Apache to $PORT provided by Railway, then start in foreground
CMD ["/bin/sh","-lc",": ${PORT:=80}; sed -ri \"s/Listen 80/Listen ${PORT}/\" /etc/apache2/ports.conf; sed -ri \"s/:80>/:${PORT}>/g\" /etc/apache2/sites-available/000-default.conf; exec apache2-foreground"]
