<?php
$host = getenv('DB_HOST') ?: "localhost";
$port = (int) (getenv('DB_PORT') ?: 3306);
$user = getenv('DB_USER') ?: "root";
$password = getenv('DB_PASSWORD');
$database = getenv('DB_NAME') ?: "mydatabase";

if ($password === false) {
    $password = "";
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $user, $password, $database, $port);
    $conn->set_charset("utf8");
} catch (Exception $e) {
    die("Database connection failed. Check your Render environment variables.");
}
?>