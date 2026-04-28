<?php
session_start();

// Delete remember cookie
if (isset($_COOKIE['meddemy_remember'])) {
    if (isset($_SESSION['student_id'])) {
        include("includes/db.php");
        $stmt = $conn->prepare("UPDATE student SET remember_token = NULL, token_expires = NULL WHERE student_id = ?");
        $stmt->bind_param("i", $_SESSION['student_id']);
        $stmt->execute();
    }
    
    setcookie('meddemy_remember', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
}

session_destroy();
header("Location: login.php");
exit();
?>