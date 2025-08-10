<?php
// CreateOrderMutation.php
// Example mutation to create an order and associated order items.

class CreateOrderMutation
{
    public static function getDefinition()
    {
        return [
            'type' => Type::boolean(),
            'args' => [
                'items' => [
                    'type' => Type::nonNull(Type::listOf(OrderInputSchema::getOrderItemInputType()))
                ],
                'customerName' => Type::string(),
                'address' => Type::string(),
            ],
            'resolve' => function ($root, $args) {
                try {
                    $dbHost = getenv('MYSQLHOST') ?: 'localhost';
                    $dbName = getenv('MYSQLDATABASE') ?: 'webshop';
                    $dbUser = getenv('MYSQLUSER') ?: 'root';
                    $dbPass = getenv('MYSQLPASSWORD') ?: '';
                    $dbPort = getenv('MYSQLPORT') ?: '3306';

                    $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
                    $db  = new PDO($dsn, $dbUser, $dbPass, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    ]);

                    // ... остатокот од кодот за внес на order и order_items ...

                    return true;
                } catch (\Throwable $e) {
                    file_put_contents(__DIR__ . '/../error_log.txt',
                        "[ERROR] CreateOrder: {$e->getMessage()}\n",
                        FILE_APPEND
                    );
                    return false;
                }
            }
        ];
    }
}
