<?php
/**
 * download_material.php
 * Secure file download — checks:
 *   1. Student logged in
 *   2. Free material → allow
 *   3. Paid material → check material_access table
 */
session_start();
include("../includes/db.php");

// Free materials: no login needed
// Paid materials: login + access check required
$student_id  = isset($_SESSION['student_id']) ? (int)$_SESSION['student_id'] : 0;
$material_id = (int)($_GET['id'] ?? 0);

if (!$material_id) {
    header("Location: materials.php");
    exit();
}

// Fetch material
$stmt = $conn->prepare("SELECT * FROM material WHERE material_id=?");
$stmt->bind_param("i", $material_id);
$stmt->execute();
$mat = $stmt->get_result()->fetch_assoc();

if (!$mat) {
    header("Location: materials.php");
    exit();
}

// Access check
if ($mat['type'] === 'paid') {
    if (!$student_id) {
        // Not logged in
        header("Location: ../login.php");
        exit();
    }
    $chk = $conn->prepare("SELECT 1 FROM material_access WHERE student_id=? AND material_id=?");
    $chk->bind_param("ii", $student_id, $material_id);
    $chk->execute();
    if ($chk->get_result()->num_rows === 0) {
        header("Location: materials.php?no_access=1");
        exit();
    }
}
// Free material — allow without login

// Serve the file
$file_path = "../assets/uploads/materials/" . $mat['file_name'];

if (!file_exists($file_path)) {
    header("Location: materials.php?file_error=1");
    exit();
}

$ext      = strtolower(pathinfo($mat['file_name'], PATHINFO_EXTENSION));
$mime_map = [
    'pdf'  => 'application/pdf',
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'ppt'  => 'application/vnd.ms-powerpoint',
    'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
];
$mime = $mime_map[$ext] ?? 'application/octet-stream';

// Clean filename for download
$download_name = preg_replace('/^mat_[a-f0-9]+_/', '', $mat['file_name']);

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $download_name . '"');
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: no-store, no-cache');
header('Pragma: no-cache');
readfile($file_path);
exit();