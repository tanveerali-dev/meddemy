<?php
session_start();
include("../includes/db.php");
if (!isset($_SESSION['admin_id'])) { header("Location: ../login.php"); exit(); }

$id = (int)($_GET['id'] ?? 0);
if ($id) {
    $conn->query("DELETE FROM question WHERE quiz_id=$id");
    $conn->query("DELETE FROM quiz_attempt WHERE quiz_id=$id");
    $conn->query("DELETE FROM quiz WHERE quiz_id=$id");
}
header("Location: manage_quizzes.php?deleted=1");
exit();