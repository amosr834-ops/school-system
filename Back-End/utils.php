<?php
function readJsonBody(): array
{
    $raw = file_get_contents("php://input");
    $decoded = json_decode($raw ?: "", true);
    if (is_array($decoded)) {
        return $decoded;
    }

    // Fallback for form-urlencoded or multipart form submissions.
    if (!empty($_POST) && is_array($_POST)) {
        return $_POST;
    }

    return [];
}

function respond(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit();
}
?>
