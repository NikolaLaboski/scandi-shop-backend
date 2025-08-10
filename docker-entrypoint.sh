#!/bin/sh
set -e

echo ">>> Binding Apache to PORT=${PORT:-8080}"

# Default port for local testing
: ${PORT:=8080}

# Replace Apache's listen port
sed -ri "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/:80>/:${PORT}>/g" /etc/apache2/sites-available/000-default.conf

# Optional: Create a health check file
cat <<'EOF' > ${APACHE_DOCUMENT_ROOT}/healthz.php
<?php
http_response_code(200);
echo "ok";
EOF

# Start Apache
exec apache2-foreground
