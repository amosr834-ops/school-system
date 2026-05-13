<?php
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("Referrer-Policy: no-referrer");
header("Cache-Control: no-store");
header("Content-Security-Policy: frame-ancestors 'none'");

$origin = $_SERVER["HTTP_ORIGIN"] ?? "";
$allowedOrigins = array_filter(array_map("trim", explode(",", defined("CORS_ALLOWED_ORIGINS") ? CORS_ALLOWED_ORIGINS : "")));

if ($origin !== "" && in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: " . $origin);
    header("Vary: Origin");
}

header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 600");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    if ($origin !== "" && !in_array($origin, $allowedOrigins, true)) {
        http_response_code(403);
        exit();
    }

    http_response_code(204);
    exit();
}
?>
