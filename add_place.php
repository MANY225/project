<?php
session_start();
if (!($_SESSION['is_admin'] ?? false)) {
    exit("ไม่อนุญาตให้เข้าถึง");
}

$mysqli = new mysqli("localhost", "root", "", "health_checkin_db");
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $province = $_POST['province'] ?? '';
    $tags = $_POST['tags'] ?? '';
    $lat = isset($_POST['lat']) ? floatval($_POST['lat']) : null;
    $lng = isset($_POST['lng']) ? floatval($_POST['lng']) : null;

    $imageName = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $imageName = uniqid() . '_' . basename($_FILES['image']['name']);
        $targetFile = $uploadDir . $imageName;

        if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            die("เกิดข้อผิดพลาดในการอัปโหลดไฟล์");
        }
    }

    if ($name && $description && $province && $tags) {
        $stmt = $mysqli->prepare("INSERT INTO places (name, description, province, tags, lat, lng, image) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssdds", $name, $description, $province, $tags, $lat, $lng, $imageName);
        $stmt->execute();
        $stmt->close();
    }
}

header("Location: admin.php");
exit();
?>
