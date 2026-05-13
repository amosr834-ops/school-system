<?php
require_once "config.php";
require_once "utils.php";
require_once "auth.php";

$data = readJsonBody();
$action = trim((string) ($data["action"] ?? "self_reset"));

function updateUserPassword(mysqli $conn, int $userId, string $password, bool $forceChange): void
{
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $forcePasswordChange = $forceChange ? 1 : 0;
    $stmt = $conn->prepare("UPDATE users SET password_hash = ?, force_password_change = ? WHERE id = ?");
    $stmt->bind_param("sii", $hash, $forcePasswordChange, $userId);
    $stmt->execute();

    if ($stmt->affected_rows < 1) {
        respond(404, ["status" => "error", "message" => "User not found"]);
    }
}

if ($action === "issue_temp_password") {
    $payload = requireAuth();
    $adminId = (int) $payload["sub"];
    $targetUserId = (int) ($data["userId"] ?? 0);
    $temporaryPassword = (string) ($data["temporaryPassword"] ?? ($data["tempPassword"] ?? ""));

    if ($targetUserId < 1 || strlen($temporaryPassword) < 6) {
        respond(422, [
            "status" => "error",
            "message" => "User and a temporary password of at least 6 characters are required"
        ]);
    }

    $adminStmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $adminStmt->bind_param("i", $adminId);
    $adminStmt->execute();
    $adminResult = $adminStmt->get_result();

    if ($adminResult->num_rows !== 1 || $adminResult->fetch_assoc()["role"] !== "admin") {
        respond(403, ["status" => "error", "message" => "Only admins can issue temporary passwords"]);
    }

    updateUserPassword($conn, $targetUserId, $temporaryPassword, true);
    respond(200, [
        "status" => "success",
        "message" => "Temporary password issued. The user must change it at next login."
    ]);
}

$identifier = trim((string) ($data["identifier"] ?? ""));
$role = normalizeRole(trim((string) ($data["role"] ?? "student")));
$rememberedPassword = (string) ($data["rememberedPassword"] ?? ($data["currentPassword"] ?? ""));
$newPassword = (string) ($data["newPassword"] ?? "");

if ($identifier === "" || $rememberedPassword === "" || $newPassword === "") {
    respond(422, [
        "status" => "error",
        "message" => "Email/admission number, remembered password, and new password are required"
    ]);
}

if (strlen($newPassword) < 6) {
    respond(422, ["status" => "error", "message" => "Password must be at least 6 characters"]);
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

if (!password_verify($rememberedPassword, $user["password_hash"])) {
    respond(403, [
        "status" => "error",
        "message" => "That password does not match this account. Please contact an admin for a temporary password."
    ]);
}

updateUserPassword($conn, (int) $user["id"], $newPassword, false);
respond(200, ["status" => "success", "message" => "Password reset successful"]);
?>
