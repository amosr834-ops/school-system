<?php
require_once "config.php";
require_once "utils.php";
require_once "auth.php";

$data = readJsonBody();
$identifier = trim((string) ($data["identifier"] ?? ""));
$role = normalizeRole(trim((string) ($data["role"] ?? "student")));
$newPassword = (string) ($data["newPassword"] ?? "");

if ($identifier === "" || $newPassword === "") {
    respond(422, [
        "status" => "error",
        "message" => "Email/admission number and new password are required"
    ]);
}

if (strlen($newPassword) < 4) {
    respond(422, ["status" => "error", "message" => "Password must be at least 4 characters"]);
}

$hash = password_hash($newPassword, PASSWORD_BCRYPT);
$stmt = $conn->prepare(
    "UPDATE users
     SET password_hash = ?
     WHERE role = ? AND (email = ? OR admission_number = ?)"
);
$stmt->bind_param("ssss", $hash, $role, $identifier, $identifier);
$stmt->execute();

if ($stmt->affected_rows < 1) {
    respond(404, ["status" => "error", "message" => "No matching account found"]);
}

respond(200, ["status" => "success", "message" => "Password reset successful"]);
?>
