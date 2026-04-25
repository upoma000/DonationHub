<?php
$host     = "localhost";
$user     = "root";
$password = "";
$database = "mydatabase";


mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $user, $password, $database);
    $conn->set_charset("utf8");
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>