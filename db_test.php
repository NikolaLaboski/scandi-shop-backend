<?php
declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

$host = getenv('MYSQLHOST') ?: '';
$db   = getenv('MYSQLDATABASE') ?: '';
$user = getenv('MYSQLUSER') ?: '';
$pass = getenv('MYSQLPASSWORD') ?: '';
$port = getenv('MYSQLPORT') ?: '';

echo "ENV check:\n";
echo "HOST={$host}\nDB={$db}\nUSER={$user}\nPORT={$port}\n";

if ($host === '' || $db === '' || $user === '' || $port === '') {
    http_response_code(500);
    echo "❌ Missing one or more MYSQL* env vars.\n";
    exit;
}

$dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
echo "DSN={$dsn}\n";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->query("SELECT 1")->fetch();
    echo "✅ DB OK\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo "❌ DB Error: " . $e->getMessage() . "\n";
}
