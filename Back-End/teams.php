<?php
require_once "config.php";
require_once "utils.php";
require_once "auth.php";

$payload = requireAuth();
$userId = (int) $payload["sub"];
$method = $_SERVER["REQUEST_METHOD"];

if ($method === "GET") {
    $query = "
        SELECT t.id, t.name, t.owner_id
        FROM teams t
        INNER JOIN team_members tm ON tm.team_id = t.id
        WHERE tm.user_id = ?
        ORDER BY t.created_at DESC
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $teams = [];
    while ($row = $result->fetch_assoc()) {
        $teams[] = $row;
    }
    respond(200, ["status" => "success", "teams" => $teams]);
}

if ($method === "POST") {
    $data = readJsonBody();
    $action = trimLimitedString($data["action"] ?? "create", 40);

    if ($action === "create") {
        $name = trimLimitedString($data["name"] ?? "", 150);
        if ($name === "") {
            respond(422, ["status" => "error", "message" => "Team name is required"]);
        }

        $stmt = $conn->prepare("INSERT INTO teams (name, owner_id) VALUES (?, ?)");
        $stmt->bind_param("si", $name, $userId);
        $stmt->execute();

        $teamId = (int) $conn->insert_id;
        $role = "owner";
        $memberStmt = $conn->prepare("INSERT INTO team_members (team_id, user_id, role) VALUES (?, ?, ?)");
        $memberStmt->bind_param("iis", $teamId, $userId, $role);
        $memberStmt->execute();

        respond(201, ["status" => "success", "message" => "Team created", "team_id" => $teamId]);
    }

    if ($action === "add_member") {
        $teamId = (int) ($data["teamId"] ?? 0);
        $email = trimLimitedString($data["email"] ?? "", 150);
        if ($teamId < 1 || $email === "") {
            respond(422, ["status" => "error", "message" => "teamId and member email are required"]);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            respond(422, ["status" => "error", "message" => "Member email is invalid"]);
        }

        $ownerCheck = $conn->prepare("SELECT id FROM teams WHERE id = ? AND owner_id = ?");
        $ownerCheck->bind_param("ii", $teamId, $userId);
        $ownerCheck->execute();
        $ownerResult = $ownerCheck->get_result();
        if ($ownerResult->num_rows !== 1) {
            respond(403, ["status" => "error", "message" => "Only team owner can add members"]);
        }

        $userStmt = $conn->prepare("SELECT id, name FROM users WHERE email = ?");
        $userStmt->bind_param("s", $email);
        $userStmt->execute();
        $userResult = $userStmt->get_result();
        if ($userResult->num_rows !== 1) {
            respond(404, ["status" => "error", "message" => "User with that email not found"]);
        }
        $member = $userResult->fetch_assoc();
        $memberId = (int) $member["id"];
        $role = "member";

        $addStmt = $conn->prepare("INSERT IGNORE INTO team_members (team_id, user_id, role) VALUES (?, ?, ?)");
        $addStmt->bind_param("iis", $teamId, $memberId, $role);
        $addStmt->execute();

        $message = "You were added to a team";
        $nStmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        $nStmt->bind_param("is", $memberId, $message);
        $nStmt->execute();

        respond(200, ["status" => "success", "message" => "Member added"]);
    }

    respond(400, ["status" => "error", "message" => "Unsupported action"]);
}

respond(405, ["status" => "error", "message" => "Method not allowed"]);
?>
