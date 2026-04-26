<?php
require_once "database.php";
require_once "utils.php";
require_once "auth.php";

$payload = requireAuth();
$userId = (int) $payload["sub"];

$stmt = $conn->prepare("SELECT id, name, email FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    respond(404, ["status" => "error", "message" => "User not found"]);
}

$user = $result->fetch_assoc();
respond(200, ["status" => "success", "user" => $user]);
?>
