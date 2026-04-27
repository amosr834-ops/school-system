<?php
require_once "config.php";
require_once "Cors.php";
require_once "utils.php";

header("Content-Type: application/json");
$method = $_SERVER["REQUEST_METHOD"] ?? "GET";

$conn = @new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, (int) DB_PORT);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Database connection failed",
        "method" => $method,
        "details" => $conn->connect_error
    ]);
    exit();
}

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
    $conn->close();
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

$conn->close();
?>
