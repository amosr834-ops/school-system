<?php
require_once "config.php";
require_once "Cors.php";

header("Content-Type: application/json");

function base64UrlEncode(string $data): string
{
    return rtrim(strtr(base64_encode($data), "+/", "-_"), "=");
}

function base64UrlDecode(string $data): string
{
    $padding = strlen($data) % 4;
    if ($padding > 0) {
        $data .= str_repeat("=", 4 - $padding);
    }
    return base64_decode(strtr($data, "-_", "+/")) ?: "";
}

function createToken(int $userId, string $email, string $role = "student"): string
{
    $header = ["alg" => "HS256", "typ" => "JWT"];
    $now = time();
    $payload = [
        "sub" => $userId,
        "email" => $email,
        "role" => normalizeRole($role),
        "iat" => $now,
        "exp" => $now + TOKEN_TTL_SECONDS
    ];

    $headerEncoded = base64UrlEncode(json_encode($header));
    $payloadEncoded = base64UrlEncode(json_encode($payload));
    $signature = hash_hmac("sha256", $headerEncoded . "." . $payloadEncoded, JWT_SECRET, true);

    return $headerEncoded . "." . $payloadEncoded . "." . base64UrlEncode($signature);
}

function normalizeRole(string $role): string
{
    return in_array($role, ["admin", "lecturer", "student"], true) ? $role : "student";
}

function parseAuthorizationHeader(): string
{
    $headers = getallheaders();
    $authHeader = $headers["Authorization"] ?? $headers["authorization"] ?? "";

    if (!preg_match("/Bearer\\s+(.*)$/i", $authHeader, $matches)) {
        return "";
    }

    return trim($matches[1]);
}

function decodeToken(string $token): ?array
{
    $parts = explode(".", $token);
    if (count($parts) !== 3) {
        return null;
    }

    [$headerEncoded, $payloadEncoded, $signatureEncoded] = $parts;
    $expectedSignature = hash_hmac("sha256", $headerEncoded . "." . $payloadEncoded, JWT_SECRET, true);
    $receivedSignature = base64UrlDecode($signatureEncoded);

    if (!hash_equals($expectedSignature, $receivedSignature)) {
        return null;
    }

    $payload = json_decode(base64UrlDecode($payloadEncoded), true);
    if (!is_array($payload) || !isset($payload["exp"]) || time() > (int) $payload["exp"]) {
        return null;
    }

    return $payload;
}

function requireAuth(): array
{
    $token = parseAuthorizationHeader();
    if ($token === "") {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Missing bearer token"]);
        exit();
    }

    $payload = decodeToken($token);
    if ($payload === null || !isset($payload["sub"])) {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Invalid or expired token"]);
        exit();
    }

    return $payload;
}
?>
