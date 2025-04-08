<?php
// Set PHP default timezone (Africa/Nairobi = GMT+3)
date_default_timezone_set('Africa/Nairobi');

// Database connection settings
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'lcc_vbs';

// Create connection
$conn = new mysqli($host, $user, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set MySQL connection timezone to match (GMT+3)
$conn->query("SET time_zone = '+3:00'");

// Optional: Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>