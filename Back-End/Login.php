<?php
require_once "database.php";
require_once "utils.php";
require_once "auth.php";

$data = readJsonBody();
$email = trim((string) ($data["email"] ?? ""));
$password = (string) ($data["password"] ?? "");

if ($email === "" || $password === "") {
    respond(422, [
        "status" => "error",
        "message" => "Email and password are required"
    ]);
}

$stmt = $conn->prepare("SELECT id, name, email, password_hash FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    $passwordMatches = password_verify($password, $user["password_hash"]);

    if ($passwordMatches) {
        $token = createToken((int) $user["id"], $user["email"]);
        respond(200, [
            "status" => "success",
            "message" => "Login successful",
            "token" => $token,
            "user" => [
                "id" => (int) $user["id"],
                "name" => $user["name"],
                "email" => $user["email"]
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

