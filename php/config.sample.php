<?php
require_once __DIR__ . '/../vendor/autoload.php';

define('GOOGLE_AI_API_KEY', 'api here');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'smartcvdb');

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

if ($conn->connect_error) {
    die("ERROR: Database connection failed: " . $conn->connect_error);
}
?>