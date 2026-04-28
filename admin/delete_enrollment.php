<?php
session_start();
include("../includes/db.php");

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

$enroll_id = (int)($_GET['id'] ?? 0);
$redirect      = $_GET['redirect'] ?? 'manage_students';
$course_id     = (int)($_GET['course_id'] ?? 0); // for delete_course redirect

if (!$enroll_id) {
    header("Location: manage_students.php");
    exit();
}

$stmt = $conn->prepare("DELETE FROM enrollment WHERE enroll_id=?");
$stmt->bind_param("i", $enroll_id);
$stmt->execute();

// Redirect back to correct page
if ($redirect === 'delete_course' && $course_id) {
    header("Location: delete_course.php?id={$course_id}");
} elseif ($redirect === 'manage_students') {
    header("Location: manage_students.php?removed=1");
} else {
    header("Location: manage_students.php?removed=1");
}
exit();