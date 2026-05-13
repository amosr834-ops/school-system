<?php
require_once "config.php";
require_once "utils.php";
require_once "auth.php";

$payload = requireAuth();
$userId = (int) $payload["sub"];
$method = $_SERVER["REQUEST_METHOD"];

if ($method === "GET") {
    $taskId = (int) ($_GET["taskId"] ?? 0);
    if ($taskId < 1) {
        respond(422, ["status" => "error", "message" => "taskId is required"]);
    }

    $accessCheck = $conn->prepare(
        "SELECT t.id FROM tasks t
         LEFT JOIN team_members tm ON tm.team_id = t.team_id AND tm.user_id = ?
         WHERE t.id = ? AND (tm.user_id IS NOT NULL OR t.assignee_id = ? OR t.created_by = ?)"
    );
    $accessCheck->bind_param("iiii", $userId, $taskId, $userId, $userId);
    $accessCheck->execute();
    if ($accessCheck->get_result()->num_rows !== 1) {
        respond(403, ["status" => "error", "message" => "No access to comments"]);
    }

    $stmt = $conn->prepare(
        "SELECT c.id, c.task_id, c.body, c.created_at, u.id AS user_id, u.name
         FROM task_comments c
         INNER JOIN users u ON u.id = c.user_id
         WHERE c.task_id = ?
         ORDER BY c.created_at ASC"
    );
    $stmt->bind_param("i", $taskId);
    $stmt->execute();
    $result = $stmt->get_result();
    $comments = [];
    while ($row = $result->fetch_assoc()) {
        $comments[] = $row;
    }

    respond(200, ["status" => "success", "comments" => $comments]);
}

if ($method === "POST") {
    $data = readJsonBody();
    $taskId = (int) ($data["taskId"] ?? 0);
    $body = trimLimitedString($data["body"] ?? "", 5000);

    if ($taskId < 1 || $body === "") {
        respond(422, ["status" => "error", "message" => "taskId and body are required"]);
    }

    $accessCheck = $conn->prepare(
        "SELECT t.id, t.created_by, t.assignee_id FROM tasks t
         LEFT JOIN team_members tm ON tm.team_id = t.team_id AND tm.user_id = ?
         WHERE t.id = ? AND (tm.user_id IS NOT NULL OR t.assignee_id = ? OR t.created_by = ?)"
    );
    $accessCheck->bind_param("iiii", $userId, $taskId, $userId, $userId);
    $accessCheck->execute();
    $accessResult = $accessCheck->get_result();
    if ($accessResult->num_rows !== 1) {
        respond(403, ["status" => "error", "message" => "No access to this task"]);
    }
    $task = $accessResult->fetch_assoc();

    $stmt = $conn->prepare("INSERT INTO task_comments (task_id, user_id, body) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $taskId, $userId, $body);
    $stmt->execute();

    $message = "New comment on task #" . $taskId;
    $recipients = array_unique([(int) $task["created_by"], (int) $task["assignee_id"]]);
    foreach ($recipients as $recipientId) {
        if ($recipientId > 0 && $recipientId !== $userId) {
            $nStmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
            $nStmt->bind_param("is", $recipientId, $message);
            $nStmt->execute();
        }
    }

    respond(201, ["status" => "success", "message" => "Comment added"]);
}

respond(405, ["status" => "error", "message" => "Method not allowed"]);
?>
