<?php
// graphql/index.php

declare(strict_types=1);

use GraphQL\GraphQL;
use GraphQL\Error\DebugFlag;

ini_set('display_errors', '0');
error_reporting(E_ALL);

// CORS (adjust origin if needed)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// If vendor autoload exists, include it
$autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

// Build schema
$schema = require __DIR__ . '/schema.php';

// Accept both GET (simple health) and POST (GraphQL)
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    header('Content-Type: text/plain; charset=utf-8');
    echo "GraphQL endpoint ready. Use POST with JSON body to query.\n";
    exit;
}

if ($method !== 'POST') {
    http_response_code(405);
    header('Allow: GET, POST, OPTIONS');
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// Read and decode JSON input
$raw = file_get_contents('php://input') ?: '';
$input = json_decode($raw, true);

$query     = $input['query']     ?? '';
$variables = $input['variables'] ?? null;
$operation = $input['operationName'] ?? null;

if (!is_string($query) || $query === '') {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Missing or invalid GraphQL query']);
    exit;
}

try {
    $result = GraphQL::executeQuery(
        $schema,
        $query,
        null,     // rootValue
        null,     // context
        is_array($variables) ? $variables : null,
        is_string($operation) ? $operation : null
    );

    // DEBUG DISABLED
    $debugFlags = 0;

    $output = $result->toArray($debugFlags);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($output);
} catch (Throwable $e) {
    header('Content-Type: application/json; charset=utf-8', true, 500);
    echo json_encode([
        'errors' => [[
            'message' => 'Internal server error'
        ]],
    ]);
}
