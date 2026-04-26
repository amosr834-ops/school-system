<?php
declare(strict_types=1);

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

$autoDbHost = isDockerRuntime() ? "db" : "localhost";
$autoDbPassword = isDockerRuntime() ? "root" : "";

define("DB_HOST", hasEnv("DB_HOST") ? envValue("DB_HOST") : $autoDbHost);
define("DB_PORT", hasEnv("DB_PORT") ? envValue("DB_PORT") : "3306");
define("DB_NAME", hasEnv("DB_NAME") ? envValue("DB_NAME") : "collaborative_tasks");
define("DB_USER", hasEnv("DB_USER") ? envValue("DB_USER") : "root");
define("DB_PASSWORD", hasEnv("DB_PASSWORD") ? envValue("DB_PASSWORD") : $autoDbPassword);
define("JWT_SECRET", envValue("JWT_SECRET", "change-me-in-production"));
define("TOKEN_TTL_SECONDS", (int) envValue("TOKEN_TTL_SECONDS", "86400"));
?>
