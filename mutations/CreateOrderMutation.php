<?php
// CreateOrderMutation.php
// Declares the GraphQL mutation field "createOrder" and its resolver.
// Behavior:
//  - Validates required args via GraphQL types (nonNull).
//  - Inserts a row into orders, then inserts related order_items for each product.
//  - Returns a simple success message with the new order ID.
// Notes:
//  - Uses PDO prepared statements (good for SQL injection prevention).
//  - DB credentials are hardcoded here; consider moving to env variables in production.
//  - No explicit transaction: if any item insert fails after the order, you may end up with partial data.
//    For stricter consistency, wrap inserts in a BEGIN/COMMIT/ROLLBACK.

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;

require_once __DIR__ . '/../schemas/OrderInputSchema.php'; // Додај ја оваа линија!
// ^ Provides OrderInputSchema::getOrderItemInputType() used below.

class CreateOrderMutation
{
    /**
     * getMutation
     * Returns the "fields" array for the Mutation root type, including createOrder.
     * @return array<string, mixed>
     */
    public static function getMutation()
    {
        return [
            'createOrder' => [
                // Return type: simple string message
                'type' => Type::string(),
                // Arguments required by the mutation
                'args' => [
                    'customer_name' => Type::nonNull(Type::string()),
                    'products' => Type::nonNull(Type::listOf(
                        OrderInputSchema::getOrderItemInputType() // expects { product_id, quantity }
                    )),
                    'total_price' => Type::nonNull(Type::float()),
                ],
                /**
                 * Resolver for createOrder
                 * @param mixed $root Not used
                 * @param array $args { customer_name: string, products: array<array>, total_price: float }
                 * @return string Success message containing the created order ID
                 */
                'resolve' => function ($root, $args) {
                    // Basic PDO connection; credentials are inline.
                    // NOTE: In production, load DSN/credentials from environment and set proper PDO attributes.
                    $db = new PDO('mysql:host=localhost;dbname=webshop', 'root', '');

                    // Insert parent order record (customer + total).
                    // Using positional parameters with a prepared statement to avoid injection.
                    $stmt = $db->prepare("INSERT INTO orders (customer_name, total_price) VALUES (?, ?)");
                    $stmt->execute([$args['customer_name'], $args['total_price']]);
                    $orderId = $db->lastInsertId();

                    // Insert line items for each product in the request.
                    // Assumes order_items (order_id, product_id, quantity) exist and product IDs are valid.
                    foreach ($args['products'] as $product) {
                        $stmt = $db->prepare("INSERT INTO order_items (order_id, product_id, quantity) VALUES (?, ?, ?)");
                        $stmt->execute([$orderId, $product['product_id'], $product['quantity']]);
                    }

                    // Return a neutral success string; clients can display it or ignore.
                    return "Order created successfully with ID: $orderId";
                }
            ]
        ];
    }
}
