<?php
// Database connection
$host = "localhost";
$user = "root";      // phpMyAdmin default user
$pass = "";          // phpMyAdmin default password (agar tumne set kiya ho to likho)
$db   = "meddemy";   // tumhara database ka naam

$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
