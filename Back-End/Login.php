<?php
require_once "/database.php";
require_once "Cors.php";
header("content-Type: application/json");

// Get JSON data from React
$data = json_decode(file_get_contents("php://input"), true);

// Validate input
if (!isset($data['admissionNumber']) || !isset($data['password'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Missing admission number or password"
    ]);
    exit();
}

$admission = $data['admissionNumber'];
$password = $data['password'];

// Prepare statement
$stmt = $conn->prepare("SELECT id, admission_number, password FROM students WHERE admission_number = ?");
$stmt->bind_param("s", $admission);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    if ($password === $user['password']) {
        echo json_encode([
            "status" => "success",
            "message" => "Login successful",
            "user" => [
                "id" => $user['id'],
                "admission_number" => $user['admission_number']
            ]
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Invalid password"
        ]);
    }
} else {
    echo json_encode([
        "status" => "error",
        "message" => "User not found"
    ]);
}

$stmt->close();
$conn->close();
?>





