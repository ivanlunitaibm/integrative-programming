<?php

// Database configuration
$host = 'localhost'; // Database host
$dbname = 'courtlify'; // Database name
$username = 'root'; // Database username
$password = ''; // Database password

// Attempt to connect to the database
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    // Set PDO to throw exceptions on error
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Optionally, set fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Log the error or display a user-friendly message
    error_log("Connection failed: " . $e->getMessage());
    // Optionally, display a user-friendly message
    // die("Connection failed. Please try again later.");
}

?>
