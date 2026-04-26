<?php
require_once "config.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

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
