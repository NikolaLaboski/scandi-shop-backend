<?php
// schema.php
// Constructs the executable GraphQL schema with query and mutation root types.

use GraphQL\Type\Schema;
use GraphQL\Type\Definition\ObjectType;


require_once __DIR__ . '/../schemas/ProductSchema.php';
// createOrder
require_once __DIR__ . '/../mutations/CreateOrderMutation.php';


require_once __DIR__ . '/../schemas/OrderInputSchema.php';

$mutationFields = CreateOrderMutation::getMutation(); // ['createOrder' => [...] ]

return new Schema([
    'query' => ProductSchema::getQueryType(),
    'mutation' => new ObjectType([
        'name'   => 'Mutation',
        'fields' => $mutationFields,
    ]),
]);
