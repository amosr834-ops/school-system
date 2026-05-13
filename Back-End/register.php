<?php
require_once "config.php";
require_once "utils.php";
require_once "auth.php";

$data = readJsonBody();
$name = trim((string) ($data["name"] ?? ""));
$email = trim((string) ($data["email"] ?? ""));
$admissionNumber = trim((string) ($data["admissionNumber"] ?? ($data["admission_number"] ?? "")));
$role = normalizeRole(trim((string) ($data["role"] ?? "student")));
$password = (string) ($data["password"] ?? "");

if ($role !== "student") {
    respond(403, ["status" => "error", "message" => "Lecturer and admin accounts must be created by an admin"]);
}

if ($name === "" || $email === "" || $password === "") {
    respond(422, ["status" => "error", "message" => "Name, email, and password are required"]);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(422, ["status" => "error", "message" => "Invalid email address"]);
}

if (strlen($password) < 6) {
    respond(422, ["status" => "error", "message" => "Password must be at least 6 characters"]);
}

$hash = password_hash($password, PASSWORD_BCRYPT);
$admissionNumber = $admissionNumber === "" ? null : $admissionNumber;

try {
    $stmt = $conn->prepare("INSERT INTO users (name, email, admission_number, role, password_hash) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $email, $admissionNumber, $role, $hash);
    $stmt->execute();

    $userId = (int) $conn->insert_id;
    $token = createToken($userId, $email, $role);

    respond(201, [
        "status" => "success",
        "message" => "Account created",
        "token" => $token,
        "user" => [
            "id" => $userId,
            "name" => $name,
            "email" => $email,
            "admission_number" => $admissionNumber,
            "role" => $role
        ]
    ]);
} catch (mysqli_sql_exception $e) {
    respond(409, ["status" => "error", "message" => "Email or admission number already registered"]);
}
?>
