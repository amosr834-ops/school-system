<?php
function readJsonBody(): array
{
    $contentLength = (int) ($_SERVER["CONTENT_LENGTH"] ?? 0);
    if ($contentLength > 1048576) {
        respond(413, ["status" => "error", "message" => "Request body is too large"]);
    }

    $raw = file_get_contents("php://input");
    if ($raw !== false && trim($raw) !== "") {
        $decoded = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            respond(400, ["status" => "error", "message" => "Invalid JSON request body"]);
        }

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
    header("Content-Type: application/json");
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit();
}

function trimLimitedString(mixed $value, int $maxLength): string
{
    $trimmed = trim((string) $value);
    if (strlen($trimmed) > $maxLength) {
        respond(422, ["status" => "error", "message" => "One or more fields are too long"]);
    }

    return $trimmed;
}

function isValidDateString(?string $date): bool
{
    if ($date === null || $date === "") {
        return true;
    }

    $parsed = DateTime::createFromFormat("Y-m-d", $date);
    return $parsed !== false && $parsed->format("Y-m-d") === $date;
}

function isAllowedValue(string $value, array $allowed): bool
{
    return in_array($value, $allowed, true);
}
?>
