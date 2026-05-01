<?php
declare(strict_types=1);

function loadEnvFile(string $path): void
{
    if (!is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === "" || str_starts_with($trimmed, "#")) {
            continue;
        }

        $separatorIndex = strpos($trimmed, "=");
        if ($separatorIndex === false) {
            continue;
        }

        $key = trim(substr($trimmed, 0, $separatorIndex));
        $value = trim(substr($trimmed, $separatorIndex + 1));

        if ($key === "" || getenv($key) !== false) {
            continue;
        }

        if (
            (str_starts_with($value, "\"") && str_ends_with($value, "\"")) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        putenv($key . "=" . $value);
    }
}

function envValue(string $key, string $default = ""): string
{
    $value = getenv($key);
    return $value === false ? $default : $value;
}

function hasEnv(string $key): bool
{
    $value = getenv($key);
    return $value !== false && $value !== "";
}

function isDockerRuntime(): bool
{
    return file_exists("/.dockerenv");
}

loadEnvFile(__DIR__ . "/../.env");
loadEnvFile(__DIR__ . "/.env");

$autoDbHost = isDockerRuntime() ? "db" : "localhost";
$autoDbPassword = isDockerRuntime() ? "root" : "";

$dbNameFallback = hasEnv("MYSQL_DATABASE") ? envValue("MYSQL_DATABASE") : "collaborative_tasks";
$dbUserFallback = hasEnv("MYSQL_USER") ? envValue("MYSQL_USER") : "root";
$dbPasswordFallback = hasEnv("MYSQL_PASSWORD")
    ? envValue("MYSQL_PASSWORD")
    : (hasEnv("MYSQL_ROOT_PASSWORD") ? envValue("MYSQL_ROOT_PASSWORD") : $autoDbPassword);

define("DB_HOST", hasEnv("DB_HOST") ? envValue("DB_HOST") : (hasEnv("MYSQL_HOST") ? envValue("MYSQL_HOST") : $autoDbHost));
define("DB_PORT", hasEnv("DB_PORT") ? envValue("DB_PORT") : (hasEnv("MYSQL_PORT") ? envValue("MYSQL_PORT") : "3306"));
define("DB_NAME", hasEnv("DB_NAME") ? envValue("DB_NAME") : $dbNameFallback);
define("DB_USER", hasEnv("DB_USER") ? envValue("DB_USER") : $dbUserFallback);
define("DB_PASSWORD", hasEnv("DB_PASSWORD") ? envValue("DB_PASSWORD") : $dbPasswordFallback);
define("DB_CHARSET", envValue("DB_CHARSET", "utf8mb4"));
define("DB_CONNECT_TIMEOUT", (int) envValue("DB_CONNECT_TIMEOUT", "5"));
define("APP_ENV", envValue("APP_ENV", "development"));
define("APP_DEBUG", filter_var(envValue("APP_DEBUG", APP_ENV === "production" ? "0" : "1"), FILTER_VALIDATE_BOOL));
define("DB_AUTO_MIGRATE", filter_var(envValue("DB_AUTO_MIGRATE", "0"), FILTER_VALIDATE_BOOL));
define("JWT_SECRET", envValue("JWT_SECRET", "change-me-in-production"));
define("TOKEN_TTL_SECONDS", (int) envValue("TOKEN_TTL_SECONDS", "86400"));
define("GOOGLE_CLIENT_ID", envValue("GOOGLE_CLIENT_ID", ""));
?>
