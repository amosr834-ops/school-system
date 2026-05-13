<?php
require_once "config.php";
require_once "utils.php";
require_once "auth.php";

$payload = requireAuth();
$userId = (int) $payload["sub"];

$stmt = $conn->prepare("SELECT id, name, email, admission_number, role, force_password_change FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    respond(404, ["status" => "error", "message" => "User not found"]);
}

$user = $result->fetch_assoc();
$user["force_password_change"] = (bool) $user["force_password_change"];
respond(200, ["status" => "success", "user" => $user]);
?>
