#!/bin/sh
set -e

# Use Railway-provided PORT, fallback for local runs
PORT="${PORT:-8080}"
echo ">>> Binding Apache to PORT=${PORT}"

# Bind Apache to $PORT instead of 80
sed -ri "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/:80>/:${PORT}>/g" /etc/apache2/sites-available/000-default.conf

# Start Apache
exec apache2-foreground
