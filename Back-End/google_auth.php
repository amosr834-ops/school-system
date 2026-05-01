<?php
require_once "config.php";
require_once "utils.php";
require_once "auth.php";

function fetchGoogleTokenInfo(string $idToken): ?array
{
    $url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . urlencode($idToken);
    $context = stream_context_create([
        "http" => [
            "method" => "GET",
            "timeout" => 5,
            "ignore_errors" => true
        ]
    ]);

    $response = file_get_contents($url, false, $context);
    if ($response === false) {
        return null;
    }

    $decoded = json_decode($response, true);
    return is_array($decoded) ? $decoded : null;
}

function respondWithUser(array $user): void
{
    $token = createToken((int) $user["id"], (string) $user["email"], (string) $user["role"]);
    respond(200, [
        "status" => "success",
        "message" => "Google login successful",
        "token" => $token,
        "user" => [
            "id" => (int) $user["id"],
            "name" => $user["name"],
            "email" => $user["email"],
            "admission_number" => $user["admission_number"],
            "role" => $user["role"]
        ]
    ]);
}

if (GOOGLE_CLIENT_ID === "") {
    respond(500, ["status" => "error", "message" => "Google authentication is not configured"]);
}

$data = readJsonBody();
$idToken = trim((string) ($data["idToken"] ?? ""));
$role = normalizeRole(trim((string) ($data["role"] ?? "student")));

if ($idToken === "") {
    respond(422, ["status" => "error", "message" => "Google ID token is required"]);
}

$tokenInfo = fetchGoogleTokenInfo($idToken);
if ($tokenInfo === null || ($tokenInfo["aud"] ?? "") !== GOOGLE_CLIENT_ID) {
    respond(401, ["status" => "error", "message" => "Invalid Google token"]);
}

$email = trim((string) ($tokenInfo["email"] ?? ""));
$googleSub = trim((string) ($tokenInfo["sub"] ?? ""));
$emailVerified = ($tokenInfo["email_verified"] ?? "") === "true" || ($tokenInfo["email_verified"] ?? "") === true;
$name = trim((string) ($tokenInfo["name"] ?? ""));

if ($email === "" || $googleSub === "" || !$emailVerified) {
    respond(401, ["status" => "error", "message" => "Google account email could not be verified"]);
}

$stmt = $conn->prepare(
    "SELECT id, name, email, admission_number, role, google_sub
     FROM users
     WHERE google_sub = ? OR email = ?
     LIMIT 1"
);
$stmt->bind_param("ss", $googleSub, $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    if ($user["role"] !== $role) {
        respond(403, ["status" => "error", "message" => "Please use the correct login role for this account"]);
    }

    if ($user["google_sub"] !== $googleSub) {
        $updateStmt = $conn->prepare("UPDATE users SET google_sub = ? WHERE id = ?");
        $userId = (int) $user["id"];
        $updateStmt->bind_param("si", $googleSub, $userId);
        $updateStmt->execute();
        $user["google_sub"] = $googleSub;
    }

    respondWithUser($user);
}

if ($role !== "student") {
    respond(403, ["status" => "error", "message" => "This Google account must be added by an admin before using this role"]);
}

$passwordHash = password_hash(bin2hex(random_bytes(24)), PASSWORD_BCRYPT);
$displayName = $name !== "" ? $name : $email;
$stmt = $conn->prepare(
    "INSERT INTO users (name, email, admission_number, role, google_sub, password_hash)
     VALUES (?, ?, NULL, ?, ?, ?)"
);
$stmt->bind_param("sssss", $displayName, $email, $role, $googleSub, $passwordHash);
$stmt->execute();

$user = [
    "id" => (int) $conn->insert_id,
    "name" => $displayName,
    "email" => $email,
    "admission_number" => null,
    "role" => $role,
    "google_sub" => $googleSub
];

respondWithUser($user);
?>
