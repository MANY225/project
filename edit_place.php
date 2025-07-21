<?php
session_start();
$mysqli = new mysqli("localhost", "root", "", "health_checkin_db");
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] !== true) {
    header("Location: index.php");
    exit();
}

// ดึงข้อมูลสถานที่จาก ID
$id = $_GET['id'] ?? null;
if (!$id) {
    echo "ไม่พบ ID ของสถานที่";
    exit();
}

$stmt = $mysqli->prepare("SELECT * FROM places WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$place = $result->fetch_assoc();

if (!$place) {
    echo "ไม่พบสถานที่ที่ต้องการแก้ไข";
    exit();
}

// แยก tags เป็น string
$tags = implode(',', explode(',', $place['tags']));
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แก้ไขสถานที่</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-3xl mx-auto bg-white p-6 rounded shadow">
        <h1 class="text-2xl font-bold mb-4">✏️ แก้ไขสถานที่: <?= htmlspecialchars($place['name']) ?></h1>

        <?php if (!empty($place['image'])): ?>
            <div class="mb-4">
                <p class="text-sm text-gray-600">ภาพปัจจุบัน:</p>
                <img src="uploads/<?= htmlspecialchars($place['image']) ?>" alt="รูปภาพสถานที่" class="w-full max-h-64 object-cover rounded shadow border" />
            </div>
        <?php endif; ?>

        <form method="POST" action="update_place.php" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <input type="hidden" name="id" value="<?= $place['id'] ?>">
            <input type="text" name="name" value="<?= htmlspecialchars($place['name']) ?>" placeholder="ชื่อสถานที่" class="border p-2 rounded" required />
            <input type="text" name="province" value="<?= htmlspecialchars($place['province']) ?>" placeholder="จังหวัด" class="border p-2 rounded" required />
            <input type="text" name="tags" value="<?= htmlspecialchars($tags) ?>" placeholder="คำค้น เช่น โยคะ,สมาธิ" class="border p-2 rounded" required />
            <input type="number" step="any" name="lat" value="<?= $place['lat'] ?>" placeholder="ละติจูด" class="border p-2 rounded" />
            <input type="number" step="any" name="lng" value="<?= $place['lng'] ?>" placeholder="ลองจิจูด" class="border p-2 rounded" />
            <textarea name="description" placeholder="คำอธิบาย" class="border p-2 rounded md:col-span-2" required><?= htmlspecialchars($place['description']) ?></textarea>

            <div class="md:col-span-2">
                <label class="block text-sm mb-1">เลือกรูปภาพใหม่ (หากต้องการเปลี่ยน)</label>
                <input type="file" name="image" accept="image/*" class="border p-2 rounded w-full">
            </div>

            <button class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 col-span-1 md:col-span-2">💾 บันทึกการแก้ไข</button>
        </form>

        <div class="mt-4">
            <a href="admin.php" class="text-blue-500 hover:underline">← กลับหน้าจัดการสถานที่</a>
        </div>
    </div>
</body>
</html>
