<?php
require_once "database.php";
require_once "utils.php";
require_once "auth.php";

$payload = requireAuth();
$userId = (int) $payload["sub"];
$method = $_SERVER["REQUEST_METHOD"];

if ($method === "GET") {
    $query = "
      SELECT
        t.id, t.title, t.description, t.status, t.priority, t.due_date, t.team_id,
        t.assignee_id, t.created_by, t.created_at, t.updated_at,
        creator.name AS creator_name,
        assignee.name AS assignee_name,
        tm.team_id AS member_team
      FROM tasks t
      LEFT JOIN users creator ON creator.id = t.created_by
      LEFT JOIN users assignee ON assignee.id = t.assignee_id
      LEFT JOIN team_members tm ON tm.team_id = t.team_id AND tm.user_id = ?
      WHERE tm.user_id IS NOT NULL OR t.assignee_id = ? OR t.created_by = ?
      ORDER BY t.due_date ASC, t.created_at DESC
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iii", $userId, $userId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $tasks = [];
    while ($row = $result->fetch_assoc()) {
        unset($row["member_team"]);
        $tasks[] = $row;
    }
    respond(200, ["status" => "success", "tasks" => $tasks]);
}

if ($method === "POST") {
    $data = readJsonBody();
    $title = trim((string) ($data["title"] ?? ""));
    $description = trim((string) ($data["description"] ?? ""));
    $priority = trim((string) ($data["priority"] ?? "Medium"));
    $dueDate = trim((string) ($data["dueDate"] ?? ""));
    $teamId = (int) ($data["teamId"] ?? 0);
    $assigneeEmail = trim((string) ($data["assigneeEmail"] ?? ""));

    if ($title === "" || $teamId < 1) {
        respond(422, ["status" => "error", "message" => "Title and team are required"]);
    }

    $membershipCheck = $conn->prepare("SELECT 1 FROM team_members WHERE team_id = ? AND user_id = ?");
    $membershipCheck->bind_param("ii", $teamId, $userId);
    $membershipCheck->execute();
    if ($membershipCheck->get_result()->num_rows !== 1) {
        respond(403, ["status" => "error", "message" => "You are not a member of that team"]);
    }

    $assigneeId = null;
    if ($assigneeEmail !== "") {
        $aStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $aStmt->bind_param("s", $assigneeEmail);
        $aStmt->execute();
        $aResult = $aStmt->get_result();
        if ($aResult->num_rows === 1) {
            $assignee = $aResult->fetch_assoc();
            $assigneeId = (int) $assignee["id"];
        }
    }

    $status = "Todo";
    if ($assigneeId === null) {
        $stmt = $conn->prepare(
            "INSERT INTO tasks (title, description, status, priority, due_date, created_by, assignee_id, team_id)
             VALUES (?, ?, ?, ?, ?, ?, NULL, ?)"
        );
        $stmt->bind_param("sssssii", $title, $description, $status, $priority, $dueDate, $userId, $teamId);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO tasks (title, description, status, priority, due_date, created_by, assignee_id, team_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("sssssiii", $title, $description, $status, $priority, $dueDate, $userId, $assigneeId, $teamId);
        $stmt->execute();
    }
    $taskId = (int) $conn->insert_id;

    if ($assigneeId !== null) {
        $message = "A task was assigned to you: " . $title;
        $nStmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        $nStmt->bind_param("is", $assigneeId, $message);
        $nStmt->execute();
    }

    respond(201, ["status" => "success", "message" => "Task created", "task_id" => $taskId]);
}

if ($method === "PUT") {
    $data = readJsonBody();
    $taskId = (int) ($data["taskId"] ?? 0);
    $status = trim((string) ($data["status"] ?? ""));
    $priority = trim((string) ($data["priority"] ?? ""));
    $dueDate = (string) ($data["dueDate"] ?? "");

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
        respond(403, ["status" => "error", "message" => "No access to this task"]);
    }

    $stmt = $conn->prepare(
        "UPDATE tasks
         SET status = COALESCE(NULLIF(?, ''), status),
             priority = COALESCE(NULLIF(?, ''), priority),
             due_date = COALESCE(NULLIF(?, ''), due_date),
             updated_at = CURRENT_TIMESTAMP
         WHERE id = ?"
    );
    $stmt->bind_param("sssi", $status, $priority, $dueDate, $taskId);
    $stmt->execute();

    respond(200, ["status" => "success", "message" => "Task updated"]);
}

respond(405, ["status" => "error", "message" => "Method not allowed"]);
?>
