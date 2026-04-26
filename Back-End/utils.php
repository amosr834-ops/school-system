<?php
function readJsonBody(): array
{
    $raw = file_get_contents("php://input");
    $decoded = json_decode($raw ?: "", true);
    return is_array($decoded) ? $decoded : [];
}

function respond(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit();
}
?>
