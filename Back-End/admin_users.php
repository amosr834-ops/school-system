<?php
require_once "config.php";
require_once "utils.php";
require_once "auth.php";

$payload = requireAuth();
$adminId = (int) $payload["sub"];
$method = $_SERVER["REQUEST_METHOD"];

$adminStmt = $conn->prepare("SELECT id, role FROM users WHERE id = ?");
$adminStmt->bind_param("i", $adminId);
$adminStmt->execute();
$adminResult = $adminStmt->get_result();

if ($adminResult->num_rows !== 1) {
    respond(404, ["status" => "error", "message" => "Admin account not found"]);
}

$admin = $adminResult->fetch_assoc();
if ($admin["role"] !== "admin") {
    respond(403, ["status" => "error", "message" => "Only admins can manage users"]);
}

if ($method === "GET") {
    $result = $conn->query(
        "SELECT id, name, email, admission_number, role, force_password_change, created_at
         FROM users
         ORDER BY role ASC, name ASC"
    );

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $row["force_password_change"] = (bool) $row["force_password_change"];
        $users[] = $row;
    }

    respond(200, ["status" => "success", "users" => $users]);
}

if ($method === "POST") {
    $data = readJsonBody();
    $action = trimLimitedString($data["action"] ?? "create", 40);

    if ($action === "issue_temp_password") {
        $targetUserId = (int) ($data["userId"] ?? 0);
        $tempPassword = (string) ($data["tempPassword"] ?? ($data["newPassword"] ?? ""));

        if ($targetUserId < 1 || strlen($tempPassword) < 6 || strlen($tempPassword) > 1024) {
            respond(422, ["status" => "error", "message" => "User and a temporary password of at least 6 characters are required"]);
        }

        $hash = password_hash($tempPassword, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE users SET password_hash = ?, force_password_change = 1 WHERE id = ?");
        $stmt->bind_param("si", $hash, $targetUserId);
        $stmt->execute();

        if ($stmt->affected_rows < 1) {
            respond(404, ["status" => "error", "message" => "User not found"]);
        }

        respond(200, ["status" => "success", "message" => "Temporary password issued. The user must change it at next login."]);
    }

    $name = trimLimitedString($data["name"] ?? "", 120);
    $email = trimLimitedString($data["email"] ?? "", 150);
    $admissionNumber = trimLimitedString($data["admissionNumber"] ?? ($data["admission_number"] ?? ""), 50);
    $role = normalizeRole(trim((string) ($data["role"] ?? "student")));
    $password = (string) ($data["password"] ?? "");

    if ($name === "" || $email === "" || $password === "") {
        respond(422, ["status" => "error", "message" => "Name, email, and password are required"]);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respond(422, ["status" => "error", "message" => "Invalid email address"]);
    }

    if (strlen($password) < 6) {
        respond(422, ["status" => "error", "message" => "Password must be at least 6 characters"]);
    }

    if (strlen($password) > 1024) {
        respond(422, ["status" => "error", "message" => "Password is too long"]);
    }

    $admissionNumber = $admissionNumber === "" ? null : $admissionNumber;
    if ($role === "student" && $admissionNumber === null) {
        respond(422, ["status" => "error", "message" => "Admission number is required for students"]);
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);

    try {
        $stmt = $conn->prepare("INSERT INTO users (name, email, admission_number, role, password_hash, force_password_change) VALUES (?, ?, ?, ?, ?, 1)");
        $stmt->bind_param("sssss", $name, $email, $admissionNumber, $role, $hash);
        $stmt->execute();

        respond(201, [
            "status" => "success",
            "message" => ucfirst($role) . " account created. They must change the initial password at first login.",
            "user" => [
                "id" => (int) $conn->insert_id,
                "name" => $name,
                "email" => $email,
                "admission_number" => $admissionNumber,
                "role" => $role
            ]
        ]);
    } catch (mysqli_sql_exception $e) {
        respond(409, ["status" => "error", "message" => "Email or admission number already registered"]);
    }
}

respond(405, ["status" => "error", "message" => "Method not allowed"]);
?>
