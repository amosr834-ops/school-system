<?php
require_once "config.php";
require_once "Cors.php";
require_once "utils.php";

header("Content-Type: application/json");
$method = $_SERVER["REQUEST_METHOD"] ?? "GET";
$connection = ["database" => DB_NAME];

if (APP_DEBUG) {
    $connection = [
        "host" => DB_HOST,
        "port" => (int) DB_PORT,
        "database" => DB_NAME,
        "user" => DB_USER
    ];
}

if ($method === "POST") {
    echo json_encode([
        "status" => "success",
        "message" => "Database connected successfully",
        "method" => $method,
        "connection" => $connection
    ], JSON_UNESCAPED_SLASHES);
    exit();
}

echo json_encode([
    "status" => "success",
    "message" => "Database connected successfully",
    "method" => $method,
    "connection" => $connection
], JSON_UNESCAPED_SLASHES);
?>
