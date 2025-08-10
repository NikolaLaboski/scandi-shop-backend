<?php
// db_test.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    $pdo = new PDO('mysql:host=localhost;dbname=webshop', 'root', '');
    echo "✅ Connected to DB<br><br>";

    $productId = 1;

    $stmt = $pdo->prepare("
        SELECT a.id, a.name, ai.value
        FROM product_attributes pa
        JOIN attributes a ON a.id = pa.attribute_id
        JOIN attribute_items ai ON ai.attribute_id = a.id AND ai.product_id = pa.product_id
        WHERE pa.product_id = ?
    ");
    $stmt->execute([$productId]);

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<pre>";
    print_r($results);
    echo "</pre>";

} catch (PDOException $e) {
    echo "❌ DB Error: " . $e->getMessage();
}
