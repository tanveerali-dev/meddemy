<?php
session_start();
include("../includes/db.php");

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

$video_id = (int)($_GET['id'] ?? 0);
if (!$video_id) { header("Location: manage_videos.php"); exit(); }

$stmt = $conn->prepare("DELETE FROM video WHERE video_id=?");
$stmt->bind_param("i", $video_id);
$stmt->execute();

header("Location: manage_videos.php?deleted=1");
exit();