<?php
$host = 'localhost';       // Database host
$dbname = 'helpdesk_db';   // Database name
$username = 'root';        // Database username (default for XAMPP)
$password = '';            // Database password (default for XAMPP is empty)

try {
    // Create a PDO connection
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Uncomment the line below to test the connection
    // echo "Connected successfully!";
} catch (PDOException $e) {
    // Handle connection errors
    die("Database connection failed: " . $e->getMessage());
}
?>