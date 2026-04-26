<?php
require_once "database.php";
require_once "utils.php";
require_once "auth.php";

$data = readJsonBody();
$name = trim((string) ($data["name"] ?? ""));
$email = trim((string) ($data["email"] ?? ""));
$password = (string) ($data["password"] ?? "");

if ($name === "" || $email === "" || $password === "") {
    respond(422, ["status" => "error", "message" => "Name, email, and password are required"]);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(422, ["status" => "error", "message" => "Invalid email address"]);
}

$hash = password_hash($password, PASSWORD_BCRYPT);

try {
    $stmt = $conn->prepare("INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $hash);
    $stmt->execute();

    $userId = (int) $conn->insert_id;
    $token = createToken($userId, $email);

    respond(201, [
        "status" => "success",
        "message" => "Account created",
        "token" => $token,
        "user" => [
            "id" => $userId,
            "name" => $name,
            "email" => $email
        ]
    ]);
} catch (mysqli_sql_exception $e) {
    respond(409, ["status" => "error", "message" => "Email already registered"]);
}
?>
