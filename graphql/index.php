<?php
// index.php
// Front controller for the GraphQL endpoint.
// Responsibilities:
// - Basic error reporting (dev-friendly).
// - CORS preflight handling for browsers.
// - Bootstrap Composer autoload + schema wiring.
// - Execute GraphQL queries and return JSON responses.

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// CORS: allow requests from any origin and common headers/methods.
// NOTE: For production, consider restricting Access-Control-Allow-Origin to your frontend domain.
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

// Handle preflight requests early (no body expected).
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../vendor/autoload.php';

use GraphQL\GraphQL;

// Load types/resolvers/mutations used by the schema factory.
// NOTE: These includes register type definitions and resolvers in memory.
require_once __DIR__ . '/../schemas/ProductSchema.php';
require_once __DIR__ . '/../schemas/AttributeSchema.php';
require_once __DIR__ . '/../resolvers/ProductResolver.php';
require_once __DIR__ . '/../resolvers/AttributeResolver.php';
require_once __DIR__ . '/../mutations/CreateOrderMutation.php';

// Build the executable schema (query + mutation root objects).
$schema = require __DIR__ . '/schema.php';

try {
    // Read raw JSON body from the request.
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);

    // Minimal validation: ensure a GraphQL query exists.
    if (!isset($input['query'])) {
        throw new \Exception("No GraphQL query provided.");
    }

    $query = $input['query'];
    $variableValues = $input['variables'] ?? null;

    // Execute the GraphQL operation.
    //  - $rootValue = null (not used here)
    //  - $context = null (could pass DB, auth, etc., if needed)
    $result = GraphQL::executeQuery($schema, $query, null, null, $variableValues);
    $output = $result->toArray();
} catch (\Throwable $e) {
    // On any unhandled exception, return HTTP 500 with a JSON error payload.
    // NOTE: Be careful exposing file/line in production (can leak internals).
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'errors' => [[
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]]
    ]);
    exit;
}

// Success: return the GraphQL execution result as JSON.
header('Content-Type: application/json');
echo json_encode($output);
