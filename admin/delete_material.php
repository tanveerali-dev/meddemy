<?php
session_start();
include("../includes/db.php");

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header("Location: manage_materials.php"); exit(); }

$stmt = $conn->prepare("SELECT * FROM material WHERE material_id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$mat = $stmt->get_result()->fetch_assoc();

if ($mat) {
    // Delete file from disk
    $path = "../assets/uploads/materials/" . $mat['file_name'];
    if (file_exists($path)) @unlink($path);

    // Delete access records
    $da = $conn->prepare("DELETE FROM material_access WHERE material_id=?");
    $da->bind_param("i", $id);
    $da->execute();

    // Delete material record
    $dm = $conn->prepare("DELETE FROM material WHERE material_id=?");
    $dm->bind_param("i", $id);
    $dm->execute();
}

header("Location: manage_materials.php?deleted=1");
exit();