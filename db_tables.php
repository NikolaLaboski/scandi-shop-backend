<?php
declare(strict_types=1);
header('Content-Type: text/plain; charset=utf-8');

$host = getenv('MYSQLHOST'); $db = getenv('MYSQLDATABASE');
$user = getenv('MYSQLUSER'); $pass = getenv('MYSQLPASSWORD');
$port = getenv('MYSQLPORT') ?: '3306';
$pdo = new PDO("mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4", $user, $pass, [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
echo "Tables:\n" . implode("\n", $tables) . "\n";

if (in_array('products', $tables, true)) {
  $c = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
  echo "\nproducts count: {$c}\n";
}
if (in_array('categories', $tables, true)) {
  $c = (int)$pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
  echo "categories count: {$c}\n";
}
