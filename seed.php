<?php
declare(strict_types=1);

// DB from ENV (works on Railway; falls back locally)
$host = getenv('MYSQLHOST') ?: 'localhost';
$db   = getenv('MYSQLDATABASE') ?: 'webshop';
$user = getenv('MYSQLUSER') ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: '';
$port = getenv('MYSQLPORT') ?: '3306';

$dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";

header('Content-Type: text/plain; charset=utf-8');

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo "DB connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Optional truncate guard: enable via ?truncate=1 or SEED_TRUNCATE=1
$allowTruncate = (isset($_GET['truncate']) && $_GET['truncate'] === '1') || getenv('SEED_TRUNCATE') === '1';

// Ensure required tables exist (we do NOT create them here)
$required = ['categories','products','prices','attributes','attribute_items'];
$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
$missing = array_values(array_diff($required, $tables ?? []));
if (!empty($missing)) {
    http_response_code(500);
    echo "Missing tables: " . implode(', ', $missing) . "\n";
    echo "Create them in phpMyAdmin (as you already do) and run this again.\n";
    exit(1);
}

// Load data.json
$json = @file_get_contents(__DIR__ . '/data.json');
if ($json === false) {
    http_response_code(500);
    echo "Cannot read data.json\n";
    exit(1);
}
$root = json_decode($json, true);
if (!is_array($root) || !isset($root['data'])) {
    http_response_code(500);
    echo "data.json has invalid structure\n";
    exit(1);
}
$data = $root['data'];

if ($allowTruncate) {
    // Truncate everything (safe only when explicitly requested)
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("TRUNCATE TABLE attribute_items");
    $pdo->exec("TRUNCATE TABLE attributes");
    $pdo->exec("TRUNCATE TABLE prices");
    $pdo->exec("TRUNCATE TABLE products");
    $pdo->exec("TRUNCATE TABLE categories");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "[truncate] tables cleared.\n";
}

$insertCategory = $pdo->prepare("INSERT IGNORE INTO categories (name) VALUES (:name)");

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

// Helpers for non-truncate mode: clean existing rows for a product
$delAttrItemsForProduct = $pdo->prepare("
    DELETE ai FROM attribute_items ai
    INNER JOIN attributes a ON a.id = ai.attribute_id
    WHERE a.product_id = :pid
");
$delAttributesForProduct = $pdo->prepare("DELETE FROM attributes WHERE product_id = :pid");
$delPricesForProduct     = $pdo->prepare("DELETE FROM prices WHERE product_id = :pid");
$delProduct              = $pdo->prepare("DELETE FROM products WHERE id = :pid");

$pdo->beginTransaction();

try {
    // Categories
    if (!empty($data['categories'])) {
        foreach ($data['categories'] as $category) {
            $insertCategory->execute([':name' => (string)$category['name']]);
        }
    }

    // Products and related rows
    if (!empty($data['products'])) {
        foreach ($data['products'] as $product) {
            $pid = (int)$product['id'];

            if (!$allowTruncate) {
                // clean existing rows for this product to avoid duplicates
                $delAttrItemsForProduct->execute([':pid' => $pid]);
                $delAttributesForProduct->execute([':pid' => $pid]);
                $delPricesForProduct->execute([':pid' => $pid]);
                $delProduct->execute([':pid' => $pid]);
            }

            $insertProduct->execute([
                ':id'          => $pid,
                ':name'        => (string)$product['name'],
                ':inStock'     => !empty($product['inStock']) ? 1 : 0,
                ':category'    => (string)$product['category'],
                ':brand'       => (string)$product['brand'],
                ':description' => isset($product['description']) ? (string)$product['description'] : null,
                ':image'       => isset($product['gallery'][0]) ? (string)$product['gallery'][0] : null,
            ]);

            if (!empty($product['prices'])) {
                foreach ($product['prices'] as $price) {
                    $insertPrice->execute([
                        ':product_id'     => $pid,
                        ':amount'         => (float)$price['amount'],
                        ':currency_label' => (string)$price['currency']['label'],
                        ':currency_symbol'=> (string)$price['currency']['symbol'],
                    ]);
                }
            }

            if (!empty($product['attributes'])) {
                foreach ($product['attributes'] as $attribute) {
                    $insertAttribute->execute([
                        ':product_id' => $pid,
                        ':name'       => (string)$attribute['name'],
                        ':type'       => (string)$attribute['type'],
                    ]);
                    $attributeId = (int)$pdo->lastInsertId();

                    if (!empty($attribute['items'])) {
                        foreach ($attribute['items'] as $item) {
                            $insertItem->execute([
                                ':attribute_id' => $attributeId,
                                ':displayValue' => (string)$item['displayValue'],
                                ':value'        => (string)$item['value'],
                            ]);
                        }
                    }
                }
            }
        }
    }

    $pdo->commit();
    echo "Seed done.\n";
    echo $allowTruncate ? "(mode: truncate)\n" : "(mode: per-product refresh)\n";
} catch (Throwable $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo "Seed failed: " . $e->getMessage() . "\n";
}
