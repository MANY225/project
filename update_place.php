<?php
session_start();
$mysqli = new mysqli("localhost", "root", "", "health_checkin_db");
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] !== true) {
    header("Location: index.php");
    exit();
}

// รับค่าจากฟอร์ม
$id = $_POST['id'];
$name = $_POST['name'];
$province = $_POST['province'];
$tags = $_POST['tags'];
$lat = $_POST['lat'];
$lng = $_POST['lng'];
$description = $_POST['description'];

$imageFileName = null;
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $imageTmp = $_FILES['image']['tmp_name'];
    $imageName = basename($_FILES['image']['name']);
    $targetDir = "uploads/";
    $targetFile = $targetDir . uniqid('place_') . "_" . $imageName;

    // ย้ายไฟล์รูปไปที่โฟลเดอร์ uploads
    if (move_uploaded_file($imageTmp, $targetFile)) {
        $imageFileName = basename($targetFile); // เก็บชื่อไฟล์ไว้สำหรับบันทึกในฐานข้อมูล
    }
}

// อัปเดตข้อมูลลงฐานข้อมูล
if ($imageFileName) {
    $stmt = $mysqli->prepare("UPDATE places SET name = ?, province = ?, tags = ?, lat = ?, lng = ?, description = ?, image = ? WHERE id = ?");
    $stmt->bind_param("sssddssi", $name, $province, $tags, $lat, $lng, $description, $imageFileName, $id);
} else {
    $stmt = $mysqli->prepare("UPDATE places SET name = ?, province = ?, tags = ?, lat = ?, lng = ?, description = ? WHERE id = ?");
    $stmt->bind_param("sssddsi", $name, $province, $tags, $lat, $lng, $description, $id);
}

$stmt->execute();
$stmt->close();

header("Location: admin.php");
exit();
