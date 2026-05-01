<?php
require_once "config.php";
require_once "utils.php";
require_once "auth.php";

$data = readJsonBody();
$identifier = trim((string) ($data["identifier"] ?? ($data["email"] ?? "")));
$role = normalizeRole(trim((string) ($data["role"] ?? "student")));
$password = (string) ($data["password"] ?? "");

if ($identifier === "" || $password === "") {
    respond(422, [
        "status" => "error",
        "message" => "Email/admission number and password are required"
    ]);
}

$stmt = $conn->prepare(
    "SELECT id, name, email, admission_number, role, password_hash
     FROM users
     WHERE role = ? AND (email = ? OR admission_number = ?)"
);
$stmt->bind_param("sss", $role, $identifier, $identifier);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    $passwordMatches = password_verify($password, $user["password_hash"]);

    if ($passwordMatches) {
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
                "role" => $user["role"]
            ]
        ]);
    }
}

respond(401, [
    "status" => "error",
    "message" => "Invalid credentials"
]);

$stmt->close();
$conn->close();
?>
