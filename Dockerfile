# PHP 8.3 + Apache
FROM php:8.3-apache


RUN docker-php-ext-install pdo pdo_mysql

# Rewrite модул
RUN a2enmod rewrite

# Код
WORKDIR /var/www/html
COPY . /var/www/html

# Composer (ако имаш composer.json)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN if [ -f composer.json ]; then \
      composer install --no-dev --prefer-dist --no-interaction --no-progress || true; \
    fi


RUN set -eux; cat >/entrypoint.sh <<'EOF'\n\
#!/usr/bin/env sh\n\
set -e\n\
PORT="${PORT:-8080}"\n\

if grep -qE '^Listen 80' /etc/apache2/ports.conf; then\n\
  sed -i "s/^Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf\n\
else\n\
  echo "Listen ${PORT}" >> /etc/apache2/ports.conf\n\
fi\n\
# VirtualHost на $PORT 
sed -i "s#<VirtualHost \\*:80>#<VirtualHost *:${PORT}>#g" /etc/apache2/sites-available/000-default.conf\n\
sed -i "s#DocumentRoot .*#DocumentRoot /var/www/html#g" /etc/apache2/sites-available/000-default.conf\n\

cat >/etc/apache2/conf-available/graphql.conf <<'EOC'\n\
<Directory "/var/www/html/graphql">\n\
  DirectorySlash Off\n\
  DirectoryIndex index.php\n\
  Options Indexes FollowSymLinks\n\
  AllowOverride All\n\
  Require all granted\n\
</Directory>\n\
EOC\n\
a2enconf graphql || true\n\
a2enmod rewrite || true\n\
exec apache2-foreground\n\
EOF\n\
 && chmod +x /entrypoint.sh

EXPOSE 8080
ENTRYPOINT ["/entrypoint.sh"]
