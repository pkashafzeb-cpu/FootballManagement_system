<?php
// Database Configuration
// Edit these values to match your environment

$DB_HOST = 'localhost';
$DB_NAME = 'FootballTournamentDB';
$DB_USER = 'root';
$DB_PASS = '';

// Create connection using MySQLi with error reporting
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if ($conn->connect_error) {
    die("Database Connection failed: " . $conn->connect_error . "<br>Please make sure:<br>1. MySQL/XAMPP is running<br>2. Database 'FootballTournamentDB' exists<br>3. Credentials in config.php are correct");
}

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");

// Session management
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Helper function: Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['username']);
}

/**
 * Helper function: Require login - redirect to login page if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

/**
 * Helper function: Sanitize output for HTML
 */
function e($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
?>
