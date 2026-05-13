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
define("CORS_ALLOWED_ORIGINS", envValue("CORS_ALLOWED_ORIGINS", "http://localhost:5173,http://127.0.0.1:5173,http://localhost:3000,http://127.0.0.1:3000"));

if (APP_ENV === "production" && in_array(JWT_SECRET, ["", "change-me-in-production", "replace-with-a-strong-secret"], true)) {
    http_response_code(500);
    header("Content-Type: application/json");
    echo json_encode(["status" => "error", "message" => "Server authentication is not securely configured"]);
    exit();
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function ensureDevelopmentSchema(mysqli $conn): void
{
    $columnCheck = $conn->query("SHOW COLUMNS FROM users LIKE 'admission_number'");
    if ($columnCheck->num_rows === 0) {
        $conn->query("ALTER TABLE users ADD COLUMN admission_number VARCHAR(50) NULL UNIQUE AFTER email");
    }

    $columnCheck = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
    if ($columnCheck->num_rows === 0) {
        $conn->query("ALTER TABLE users ADD COLUMN role ENUM('admin', 'lecturer', 'student') NOT NULL DEFAULT 'student' AFTER admission_number");
    }

    $columnCheck = $conn->query("SHOW COLUMNS FROM users LIKE 'google_sub'");
    if ($columnCheck->num_rows === 0) {
        $conn->query("ALTER TABLE users ADD COLUMN google_sub VARCHAR(255) NULL UNIQUE AFTER role");
    }

    $columnCheck = $conn->query("SHOW COLUMNS FROM users LIKE 'force_password_change'");
    if ($columnCheck->num_rows === 0) {
        $conn->query("ALTER TABLE users ADD COLUMN force_password_change TINYINT(1) NOT NULL DEFAULT 0 AFTER password_hash");
    }

    $conn->query(
        "CREATE TABLE IF NOT EXISTS student_marks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            lecturer_id INT NOT NULL,
            subject VARCHAR(120) NOT NULL,
            marks DECIMAL(5,2) NOT NULL,
            grade VARCHAR(5) NOT NULL,
            remarks VARCHAR(120) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_student_subject (student_id, subject),
            KEY idx_student_marks_student (student_id),
            KEY idx_student_marks_lecturer (lecturer_id),
            FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (lecturer_id) REFERENCES users(id) ON DELETE CASCADE
        )"
    );
}

try {
    $servername = DB_HOST;
    $username = DB_USER;
    $password = DB_PASSWORD;
    $dbname = DB_NAME;
    $port = (int) DB_PORT;
    $conn = mysqli_init();

    if ($conn === false) {
        throw new RuntimeException("Failed to initialize mysqli.");
    }

    $conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, max(1, DB_CONNECT_TIMEOUT));
    $conn->real_connect($servername, $username, $password, $dbname, $port);
    $conn->set_charset(DB_CHARSET);

    if (DB_AUTO_MIGRATE) {
        ensureDevelopmentSchema($conn);
    }
} catch (Exception $e) {
    http_response_code(500);
    header("Content-Type: application/json");
    $payload = [
        "status" => "error",
        "message" => "Database connection failed"
    ];

    if (APP_DEBUG) {
        $payload["details"] = $e->getMessage();
        $payload["connection"] = [
            "host" => DB_HOST,
            "port" => (int) DB_PORT,
            "database" => DB_NAME,
            "user" => DB_USER
        ];
    }

    echo json_encode($payload);
    exit();
}
?>
