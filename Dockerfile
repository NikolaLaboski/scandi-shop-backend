# ---------- Single-file Dockerfile (PHP built-in server + router) ----------
FROM php:8.3-cli


WORKDIR /app
COPY . /app


COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN if [ -f composer.json ]; then \
      composer install --no-dev --prefer-dist --no-interaction --no-progress || true; \
    fi


RUN set -e; cat > /app/router.php <<'PHP'
<?php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);


$allowed = ['https://scweb-shop.netlify.app', 'http://localhost:5173'];
$origin  = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($path === '/graphql' || $path === '/graphql/') {
  if (in_array($origin, $allowed, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Vary: Origin");
    header("Access-Control-Allow-Credentials: true");
  }
  header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
  header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Apollo-Require-Preflight");

  if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
  }
}


if ($path === '/graphql' || $path === '/graphql/') {
  require __DIR__ . '/graphql/index.php';
  exit;
}


if ($path !== '/' && file_exists(__DIR__.$path) && !is_dir(__DIR__.$path)) {
  return false; // php -S ќе го сервира
}


header('Content-Type: text/plain; charset=utf-8');
echo "Backend root. GraphQL is at /graphql\n";
PHP


EXPOSE 8080
CMD ["sh", "-lc", "php -S 0.0.0.0:${PORT:-8080} router.php"]
