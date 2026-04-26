<?php
declare(strict_types=1);

function envValue(string $key, string $default = ""): string
{
    $value = getenv($key);
    return $value === false ? $default : $value;
}

define("DB_HOST", envValue("DB_HOST", "db"));
define("DB_PORT", envValue("DB_PORT", "3306"));
define("DB_NAME", envValue("DB_NAME", "collaborative_tasks"));
define("DB_USER", envValue("DB_USER", "root"));
define("DB_PASSWORD", envValue("DB_PASSWORD", "root"));
define("JWT_SECRET", envValue("JWT_SECRET", "change-me-in-production"));
define("TOKEN_TTL_SECONDS", (int) envValue("TOKEN_TTL_SECONDS", "86400"));
?>
