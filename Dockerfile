# PHP 8.3 + Apache
FROM php:8.3-apache


RUN docker-php-ext-install pdo pdo_mysql


RUN a2enmod rewrite


WORKDIR /var/www/html
COPY . /var/www/html


COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN if [ -f composer.json ]; then \
      composer install --no-dev --prefer-dist --no-interaction --no-progress || true; \
    fi


RUN set -e; cat >/entrypoint.sh <<'EOF'
#!/usr/bin/env sh
set -e

PORT="${PORT:-8080}"


if grep -qE '^Listen 80' /etc/apache2/ports.conf; then
  sed -i "s/^Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
else
  echo "Listen ${PORT}" >> /etc/apache2/ports.conf
fi


sed -i "s#<VirtualHost \*:80>#<VirtualHost *:${PORT}>#g" /etc/apache2/sites-available/000-default.conf
sed -i "s#DocumentRoot .*#DocumentRoot /var/www/html#g" /etc/apache2/sites-available/000-default.conf


cat >/etc/apache2/conf-available/graphql.conf <<'EOC'
<Directory "/var/www/html/graphql">
  DirectorySlash Off
  DirectoryIndex index.php
  Options Indexes FollowSymLinks
  AllowOverride All
  Require all granted
</Directory>
EOC

a2enconf graphql || true
a2enmod rewrite || true


exec apache2-foreground
EOF
RUN chmod +x /entrypoint.sh

EXPOSE 8080
ENTRYPOINT ["/entrypoint.sh"]
