<?php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// --- CORS ---
$allowed = ['https://scweb-shop.netlify.app', 'http://localhost:5173'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
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


if ($path === '/graphql' || $path === '/graphql/') {
  require __DIR__ . '/graphql/index.php';
  exit;
}


if ($path !== '/' && file_exists(__DIR__.$path)) {
  return false; 
}


header('Content-Type: text/plain; charset=utf-8');
echo "Backend root. GraphQL is at /graphql\n";
