<?php

$pdo = new PDO('mysql:host=localhost;dbname=webshop', 'root', '');


$json = file_get_contents('data.json');
$data = json_decode($json, true)['data'];


$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
$pdo->exec("TRUNCATE TABLE attribute_items");
$pdo->exec("TRUNCATE TABLE attributes");
$pdo->exec("TRUNCATE TABLE prices");
$pdo->exec("TRUNCATE TABLE products");
$pdo->exec("TRUNCATE TABLE categories");
$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");


$insertCategory = $pdo->prepare("INSERT INTO categories (name) VALUES (:name)");
foreach ($data['categories'] as $category) {
    $insertCategory->execute([
        ':name' => $category['name']
    ]);
}


$insertProduct = $pdo->prepare("
    INSERT INTO products (id, name, inStock, category, brand, description, image)
    VALUES (:id, :name, :inStock, :category, :brand, :description, :image)
");

$insertPrice = $pdo->prepare("
    INSERT INTO prices (product_id, amount, currency_label, currency_symbol)
    VALUES (:product_id, :amount, :currency_label, :currency_symbol)
");

$insertAttribute = $pdo->prepare("
    INSERT INTO attributes (product_id, name, type)
    VALUES (:product_id, :name, :type)
");

$insertItem = $pdo->prepare("
    INSERT INTO attribute_items (attribute_id, displayValue, value)
    VALUES (:attribute_id, :displayValue, :value)
");

foreach ($data['products'] as $product) {
    $insertProduct->execute([
        ':id' => $product['id'],
        ':name' => $product['name'],
        ':inStock' => $product['inStock'] ? 1 : 0,
        ':category' => $product['category'],
        ':brand' => $product['brand'],
        ':description' => $product['description'],
        ':image' => $product['gallery'][0] ?? null,
    ]);

 
    foreach ($product['prices'] as $price) {
        $insertPrice->execute([
            ':product_id' => $product['id'],
            ':amount' => $price['amount'],
            ':currency_label' => $price['currency']['label'],
            ':currency_symbol' => $price['currency']['symbol'],
        ]);
    }

   
    foreach ($product['attributes'] as $attribute) {
        $insertAttribute->execute([
            ':product_id' => $product['id'],
            ':name' => $attribute['name'],
            ':type' => $attribute['type'],
        ]);
        $attributeId = $pdo->lastInsertId();

        foreach ($attribute['items'] as $item) {
            $insertItem->execute([
                ':attribute_id' => $attributeId,
                ':displayValue' => $item['displayValue'],
                ':value' => $item['value'],
            ]);
        }
    }
}

echo "All fine!\n";
