<?php
// Simple health endpoint for Railway HTTP health checks
http_response_code(200);
header('Content-Type: text/plain');
echo "ok";
