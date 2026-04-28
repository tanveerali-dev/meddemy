<?php
session_start();
include("../includes/db.php");
if (!isset($_SESSION['admin_id'])) { header("Location: ../login.php"); exit(); }

$id = (int)($_GET['id'] ?? 0);
if ($id) {
    $b = $conn->prepare("SELECT image FROM book WHERE book_id=?");
    $b->bind_param("i",$id); $b->execute();
    $row = $b->get_result()->fetch_assoc();
    if ($row && $row['image']) {
        $path = "../assets/uploads/books/" . $row['image'];
        if (file_exists($path)) @unlink($path);
    }
    $conn->query("DELETE FROM book WHERE book_id=$id");
}
header("Location: manage_books.php?deleted=1");
exit();