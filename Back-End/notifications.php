<?php
require_once "database.php";
require_once "utils.php";
require_once "auth.php";

$payload = requireAuth();
$userId = (int) $payload["sub"];
$method = $_SERVER["REQUEST_METHOD"];

if ($method === "GET") {
    $stmt = $conn->prepare(
        "SELECT id, message, is_read, created_at
         FROM notifications
         WHERE user_id = ?
         ORDER BY created_at DESC
         LIMIT 100"
    );
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    respond(200, ["status" => "success", "notifications" => $notifications]);
}

if ($method === "POST") {
    $data = readJsonBody();
    $notificationId = (int) ($data["notificationId"] ?? 0);
    if ($notificationId < 1) {
        respond(422, ["status" => "error", "message" => "notificationId is required"]);
    }

    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $notificationId, $userId);
    $stmt->execute();

    respond(200, ["status" => "success", "message" => "Notification updated"]);
}

respond(405, ["status" => "error", "message" => "Method not allowed"]);
?>
