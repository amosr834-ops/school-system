<?php
require_once "config.php";
require_once "utils.php";
require_once "auth.php";

function loginRateLimitPath(string $identifier): string
{
    $remoteAddress = $_SERVER["REMOTE_ADDR"] ?? "unknown";
    return sys_get_temp_dir() . "/school_login_" . hash("sha256", strtolower($identifier) . "|" . $remoteAddress) . ".json";
}

function readLoginRateLimit(string $identifier): array
{
    $path = loginRateLimitPath($identifier);
    if (!is_readable($path)) {
        return ["count" => 0, "first_failed_at" => time()];
    }

    $decoded = json_decode((string) file_get_contents($path), true);
    return is_array($decoded) ? $decoded : ["count" => 0, "first_failed_at" => time()];
}

function recordFailedLogin(string $identifier): void
{
    $state = readLoginRateLimit($identifier);
    $now = time();
    if ($now - (int) ($state["first_failed_at"] ?? $now) > 900) {
        $state = ["count" => 0, "first_failed_at" => $now];
    }

    $state["count"] = (int) ($state["count"] ?? 0) + 1;
    @file_put_contents(loginRateLimitPath($identifier), json_encode($state), LOCK_EX);
}

function clearFailedLogins(string $identifier): void
{
    $path = loginRateLimitPath($identifier);
    if (is_file($path)) {
        @unlink($path);
    }
}

$data = readJsonBody();
$identifier = trimLimitedString($data["identifier"] ?? ($data["email"] ?? ""), 150);
$role = normalizeRole(trim((string) ($data["role"] ?? "student")));
$password = (string) ($data["password"] ?? "");

if ($identifier === "" || $password === "") {
    respond(422, [
        "status" => "error",
        "message" => "Email/admission number and password are required"
    ]);
}

if (strlen($password) > 1024) {
    respond(422, ["status" => "error", "message" => "Invalid credentials"]);
}

$rateLimit = readLoginRateLimit($identifier);
if ((int) ($rateLimit["count"] ?? 0) >= 5 && time() - (int) ($rateLimit["first_failed_at"] ?? time()) <= 900) {
    respond(429, ["status" => "error", "message" => "Too many failed login attempts. Please try again later."]);
}

$stmt = $conn->prepare(
    "SELECT id, name, email, admission_number, role, password_hash, force_password_change
     FROM users
     WHERE role = ? AND (email = ? OR admission_number = ?)"
);
$stmt->bind_param("sss", $role, $identifier, $identifier);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    $storedPassword = (string) $user["password_hash"];
    $passwordMatches = password_verify($password, $storedPassword);
    $storedPasswordInfo = password_get_info($storedPassword);

    if (!$passwordMatches && (int) $storedPasswordInfo["algo"] === 0) {
        $passwordMatches = hash_equals($storedPassword, $password);
    }

    if ($passwordMatches) {
        clearFailedLogins($identifier);

        if ((int) $storedPasswordInfo["algo"] === 0 || password_needs_rehash($storedPassword, PASSWORD_BCRYPT)) {
            $newHash = password_hash($password, PASSWORD_BCRYPT);
            $rehashStmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $rehashStmt->bind_param("si", $newHash, $user["id"]);
            $rehashStmt->execute();
            $rehashStmt->close();
        }

        $token = createToken((int) $user["id"], $user["email"], $user["role"]);
        respond(200, [
            "status" => "success",
            "message" => "Login successful",
            "token" => $token,
            "user" => [
                "id" => (int) $user["id"],
                "name" => $user["name"],
                "email" => $user["email"],
                "admission_number" => $user["admission_number"],
                "role" => $user["role"],
                "force_password_change" => (bool) $user["force_password_change"]
            ]
        ]);
    }
}

recordFailedLogin($identifier);
respond(401, [
    "status" => "error",
    "message" => "Invalid credentials"
]);

$stmt->close();
$conn->close();
?>
