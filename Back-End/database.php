<?php
require_once "config.php";

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
