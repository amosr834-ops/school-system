<?php
require_once "config.php";

header("Content-Type: application/json");

$conn = @new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, (int) DB_PORT);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Database connection failed",
        "details" => $conn->connect_error
    ]);
    exit();
}

echo json_encode([
    "status" => "success",
    "message" => "Database connected successfully",
    "connection" => [
        "host" => DB_HOST,
        "port" => (int) DB_PORT,
        "database" => DB_NAME,
        "user" => DB_USER
    ]
]);

$conn->close();
?>
