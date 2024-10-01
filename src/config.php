<?php
$host = "localhost";
$port = "3306";
$username = "root";
$user_pass = "root";
$database_in_use = "secure_messaging";

// Create connection
$conn = new mysqli($host, $username, $user_pass, $database_in_use, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Start session and initialize CSRF token generation if not already set
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// CSRF Token Generation
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// CSRF Token functions
function generateCsrfToken() {
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    return hash_equals($_SESSION['csrf_token'], $token);
}
?>
