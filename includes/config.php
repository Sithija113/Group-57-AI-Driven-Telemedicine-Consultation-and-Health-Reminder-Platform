<?php
// includes/config.php
$host = 'localhost';
$username = 'root';
$password = 'dilupa123';
$database = 'tele_medicine_db';  // Change this to your actual database name

// Create connection
$conn = mysqli_connect($host, $username, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset
mysqli_set_charset($conn, "utf8");

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>