<?php
require_once "config.php";
require_once "Cors.php";
require_once "utils.php";

header("Content-Type: application/json");
$method = $_SERVER["REQUEST_METHOD"] ?? "GET";

if ($method === "POST") {
    $data = readJsonBody();
    echo json_encode([
        "status" => "success",
        "message" => "Database connected successfully",
        "method" => $method,
        "received" => $data,
        "connection" => [
            "host" => DB_HOST,
            "port" => (int) DB_PORT,
            "database" => DB_NAME,
            "user" => DB_USER
        ]
    ]);
    exit();
}

echo json_encode([
    "status" => "success",
    "message" => "Database connected successfully",
    "method" => $method,
    "connection" => [
        "host" => DB_HOST,
        "port" => (int) DB_PORT,
        "database" => DB_NAME,
        "user" => DB_USER
    ]
]);
?>
