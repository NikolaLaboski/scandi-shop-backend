<?php
$host = 'localhost';
$db = 'webshop';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die('Connection failed: ' . $e->getMessage());
}

$data = json_decode(file_get_contents('data.json'), true);
foreach ($data as $product) {
    $stmt = $pdo->prepare("INSERT INTO products (id, name, category, price) VALUES (?, ?, ?, ?)");
    $stmt->execute([$product['id'], $product['name'], $product['category'], $product['price']]);

    if (!empty($product['attributes'])) {
        foreach ($product['attributes'] as $attribute) {
            $stmtAttr = $pdo->prepare("INSERT INTO attributes (product_id, name) VALUES (?, ?)");
            $stmtAttr->execute([$product['id'], $attribute['name']]);

            $attributeId = $pdo->lastInsertId();
            foreach ($attribute['items'] as $item) {
                $stmtItem = $pdo->prepare("INSERT INTO attribute_items (attribute_id, value) VALUES (?, ?)");
                $stmtItem->execute([$attributeId, $item]);
            }
        }
    }
}
echo "Database seeded successfully.";
?>