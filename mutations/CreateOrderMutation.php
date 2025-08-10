<?php
// CreateOrderMutation.php
// Example mutation to create an order and associated order items.

use GraphQL\Type\Definition\Type;

class CreateOrderMutation
{
    
    public static function getMutation(): array
    {
        return [
            'createOrder' => self::getDefinition()
        ];
    }

    public static function getDefinition(): array
    {
        return [
            
            'type' => Type::nonNull(Type::boolean()),
            'args' => [
                'items' => [
                    'type' => Type::nonNull(
                        Type::listOf(
                          
                            OrderInputSchema::getOrderItemInputType()
                        )
                    )
                ],
                'customerName' => ['type' => Type::string()],
                'address'      => ['type' => Type::string()],
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

                   

                    return true;
                } catch (\Throwable $e) {
                    @file_put_contents(
                        __DIR__ . '/../error_log.txt',
                        "[ERROR] CreateOrder: {$e->getMessage()}\n",
                        FILE_APPEND
                    );
                    return false;
                }
            }
        ];
    }
}
