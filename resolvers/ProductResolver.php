<?php
// ProductResolver.php
// Provides read operations for products, including attributes, prices, stock flags,
// normalized gallery arrays, and a convenience 'price' (first price amount).

require_once __DIR__ . '/../resolvers/AttributeResolver.php';

class ProductResolver
{
    /**
     * Create a new PDO connection to the webshop database.
     * In production, move DSN/credentials to env vars and set PDO attributes (ERRMODE, etc.).
     *
     * @return \PDO
     */
    private static function connect()
    {
        return new PDO('mysql:host=localhost;dbname=webshop', 'root', '');
    }

    /**
     * Read ordered gallery URLs for a product from product_images.
     * Falls back to an empty array if none are found.
     *
     * @param int|string $productId
     * @return array<int, string>
     */
    private static function getGalleryForProduct($productId)
    {
        $db = self::connect();
        $stmt = $db->prepare("SELECT url FROM product_images WHERE product_id = ? ORDER BY sort_order ASC, id ASC");
        $stmt->execute([$productId]);
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return $rows ?: [];
    }

    /**
     * Fetch and enrich all products:
     *  - attributes via AttributeResolver
     *  - prices array
     *  - inStock cast to boolean
     *  - gallery normalized (product_images first, else products.image fallback)
     *  - price convenience field from prices[0].amount (if present)
     *
     * On per-product error, logs and supplies safe defaults.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getAllProducts()
    {
        $db = self::connect();
        $stmt = $db->query("SELECT * FROM products");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($products as &$product) {
            try {
                // Attributes (with items)
                $product['attributes'] = AttributeResolver::getAttributesForProduct($product['id']);

                // Prices (amount + currency fields)
                $product['prices'] = self::getPricesForProduct($product['id']);

                // Normalize boolean field
                $product['inStock'] = (bool)$product['inStock'];

                // Gallery: prefer product_images; fallback to legacy products.image
                $gallery = self::getGalleryForProduct($product['id']);
                if (empty($gallery) && !empty($product['image'])) {
                    $gallery = [$product['image']];
                }
                $product['gallery'] = $gallery;

                // Convenience scalar price (first amount if available)
                $product['price'] = isset($product['prices'][0]['amount'])
                    ? (float)$product['prices'][0]['amount'] : null;

            } catch (\Throwable $e) {
                // Fail soft for this product, keep the list response intact.
                $product['attributes'] = [];
                $product['prices'] = [];
                $product['gallery'] = [];
                file_put_contents(__DIR__ . '/../error_log.txt',
                    "[ERROR] Product ID {$product['id']}: {$e->getMessage()}\n",
                    FILE_APPEND
                );
            }
        }

        return $products;
    }

    /**
     * Fetch a single product by ID and enrich it (same as in getAllProducts()).
     *
     * @param int|string $id
     * @return array<string, mixed>|false associative array or false if not found
     */
    public static function getProductById($id)
    {
        $db = self::connect();
        $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            try {
                $product['attributes'] = AttributeResolver::getAttributesForProduct($product['id']);
                $product['prices'] = self::getPricesForProduct($product['id']);
                $product['inStock'] = (bool)$product['inStock'];

                // 🔥 ГАЛЕРИЈА: прво од product_images, ако нема -> products.image
                $gallery = self::getGalleryForProduct($product['id']);
                if (empty($gallery) && !empty($product['image'])) {
                    $gallery = [$product['image']];
                }
                $product['gallery'] = $gallery;

                $product['price'] = isset($product['prices'][0]['amount'])
                    ? (float)$product['prices'][0]['amount'] : null;

            } catch (\Throwable $e) {
                // If enrichment fails, return minimal safe structure and log.
                $product['attributes'] = [];
                $product['prices'] = [];
                $product['gallery'] = [];
                file_put_contents(__DIR__ . '/../error_log.txt',
                    "[ERROR] Product ID {$product['id']}: {$e->getMessage()}\n",
                    FILE_APPEND
                );
            }
        }

        return $product;
    }

    /**
     * Get prices for a product as an array of rows:
     *  [ ['amount'=>float, 'currency_label'=>string, 'currency_symbol'=>string], ... ]
     *
     * @param int|string $productId
     * @return array<int, array<string, mixed>>
     */
    private static function getPricesForProduct($productId)
    {
        $db = self::connect();
        $stmt = $db->prepare("SELECT amount, currency_label, currency_symbol FROM prices WHERE product_id = ?");
        $stmt->execute([$productId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
