<?php
require_once "config.php";
require_once "utils.php";
require_once "auth.php";

$data = readJsonBody();
$identifier = trimLimitedString($data["identifier"] ?? "", 150);
$role = normalizeRole(trim((string) ($data["role"] ?? "student")));
$currentPassword = (string) ($data["currentPassword"] ?? ($data["rememberedPassword"] ?? ""));
$newPassword = (string) ($data["newPassword"] ?? "");

if ($identifier === "" || $currentPassword === "" || $newPassword === "") {
    respond(422, [
        "status" => "error",
        "message" => "Email/admission number, remembered password, and new password are required"
    ]);
}

if (strlen($newPassword) < 6) {
    respond(422, ["status" => "error", "message" => "Password must be at least 6 characters"]);
}

if (strlen($currentPassword) > 1024 || strlen($newPassword) > 1024) {
    respond(422, ["status" => "error", "message" => "Password is too long"]);
}

$userStmt = $conn->prepare(
    "SELECT id, password_hash
     FROM users
     WHERE role = ? AND (email = ? OR admission_number = ?)"
);
$userStmt->bind_param("sss", $role, $identifier, $identifier);
$userStmt->execute();
$result = $userStmt->get_result();

if ($result->num_rows !== 1) {
    respond(404, ["status" => "error", "message" => "No matching account found"]);
}

$user = $result->fetch_assoc();

if (!password_verify($currentPassword, $user["password_hash"])) {
    respond(403, [
        "status" => "error",
        "message" => "That password does not match this account. Please contact an admin to reset it for you."
    ]);
}

$hash = password_hash($newPassword, PASSWORD_BCRYPT);
$stmt = $conn->prepare("UPDATE users SET password_hash = ?, force_password_change = 0 WHERE id = ?");
$stmt->bind_param("si", $hash, $user["id"]);
$stmt->execute();

respond(200, ["status" => "success", "message" => "Password reset successful"]);
?>
