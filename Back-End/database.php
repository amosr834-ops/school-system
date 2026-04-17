<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "elimumanagementsystem";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    echo"Connection was successful!";

    

} catch (Exception $e) {
    echo "Connection failed: " . $e->getMessage();
}

?>