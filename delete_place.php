<?php
session_start();
if (!($_SESSION['is_admin'] ?? false)) {
    exit("ไม่อนุญาตให้เข้าถึง");
}

$mysqli = new mysqli("localhost", "root", "", "health_checkin_db");
$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
    $stmt = $mysqli->prepare("DELETE FROM places WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}

header("Location: admin.php");
exit();
