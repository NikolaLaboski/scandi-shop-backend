<?php
// schema.php
// Constructs the executable GraphQL schema with query and mutation root types.

use GraphQL\Type\Schema;

require_once __DIR__ . '/../schemas/ProductSchema.php';
require_once __DIR__ . '/../mutations/CreateOrderMutation.php';

// Expose the "query" root via ProductSchema, and a "mutation" root with createOrder.
return new Schema([
    'query' => ProductSchema::getQueryType(),
    'mutation' => new \GraphQL\Type\Definition\ObjectType([
        'name' => 'Mutation',
        'fields' => CreateOrderMutation::getMutation()
    ])
]);
