#!/bin/sh
set -e

# Default if PORT is not set (local run)
: ${PORT:=8080}

# Replace Apache to listen on $PORT instead of 80
sed -ri "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/:80>/:${PORT}>/g" /etc/apache2/sites-available/000-default.conf

exec apache2-foreground
