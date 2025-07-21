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

// ดึงข้อมูลสถานที่
$filter = $_GET['filter'] ?? '';
$places = [];
$res = $mysqli->query("SELECT * FROM places");
while ($row = $res->fetch_assoc()) {
    $row['tags'] = explode(',', $row['tags']);
    $places[] = $row;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แดชบอร์ดผู้ดูแลระบบ</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body class="bg-gray-100 flex">

    <!-- Sidebar -->
    <div class="w-64 bg-gray-800 text-white min-h-screen p-4">
        <h2 class="text-2xl font-bold mb-6">ADMINISTRATOR</h2>
        <ul>
            <li class="mb-4"><a href="admin.php" class="hover:underline">➕ เพิ่มสถานที่</a></li>
            <li class="mb-4"><a href="dashboard.php" class="hover:underline">📊 แดชบอร์ด</a></li>
            <li class="mb-4"><a href="manage_users.php" class="hover:underline">🚹 จัดการสมาชิก</a></li>
            <li class="mt-8"><a href="logout.php" class="text-red-400 hover:underline"><i class="fas fa-sign-out-alt mr-2"></i>ออกจากระบบ</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="flex-1 p-6">
        <h1 class="text-3xl font-bold mb-4">จัดการสถานที่ท่องเที่ยว</h1>

        <!-- เพิ่มสถานที่ -->
        <div class="bg-white p-4 rounded shadow mb-6">
            <h2 class="text-xl font-semibold mb-2">➕ เพิ่มสถานที่ใหม่</h2>
            <form method="POST" action="add_place.php" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input type="text" name="name" placeholder="ชื่อสถานที่" class="border p-2 rounded" required />
                <input type="text" name="province" placeholder="จังหวัด" class="border p-2 rounded" required />
                <input type="text" name="tags" placeholder="คำค้น เช่น โยคะ,สมาธิ" class="border p-2 rounded" required />
                <input type="number" step="any" name="lat" placeholder="ละติจูด" class="border p-2 rounded" />
                <input type="number" step="any" name="lng" placeholder="ลองจิจูด" class="border p-2 rounded" />
                <textarea name="description" placeholder="คำอธิบาย" class="border p-2 rounded md:col-span-2" required></textarea>
                <input type="file" name="image" class="md:col-span-2" />
                <button class="bg-blue-500 text-white px-4 py-2 rounded col-span-1 md:col-span-2 hover:bg-blue-600">บันทึก</button>
            </form>
        </div>

        <!-- รายการสถานที่ -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php
            $found = false;
            foreach ($places as $place) {
                $matched = !$filter || stripos($place['name'], $filter) !== false || array_filter($place['tags'], fn($tag) => stripos($tag, $filter) !== false);
                if ($matched):
                    $found = true;
            ?>
            <div class="border p-4 rounded shadow bg-white">
                <?php if (!empty($place['image'])): ?>
                    <img src="uploads/<?= htmlspecialchars($place['image']) ?>" alt="ภาพสถานที่" class="w-full h-40 object-cover rounded mb-2">
                <?php endif; ?>
                <h3 class="font-bold text-lg mb-1"><?= htmlspecialchars($place['name']) ?></h3>
                <p class="text-sm text-gray-600 mb-2"><?= htmlspecialchars($place['description']) ?></p>
                <p class="text-sm">📍 <?= htmlspecialchars($place['province']) ?></p>
                <div class="my-2">
                    <?php foreach ($place['tags'] as $tag): ?>
                        <span class="bg-green-200 text-green-800 text-xs px-2 py-1 rounded mr-1">#<?= htmlspecialchars(trim($tag)) ?></span>
                    <?php endforeach; ?>
                </div>
                <div class="flex items-center space-x-4 mt-2">
                    <a href="edit_place.php?id=<?= $place['id'] ?>" class="text-blue-600 text-sm hover:underline">✏️ แก้ไข</a>
                    <a href="delete_place.php?id=<?= $place['id'] ?>" class="text-red-600 text-sm hover:underline" onclick="return confirm('ต้องการลบสถานที่นี้?');">🗑 ลบ</a>
                </div>
            </div>
            <?php endif; } ?>
            <?php if (!$found): ?>
                <p class="text-gray-500">ไม่พบสถานที่ที่ค้นหา</p>
            <?php endif; ?>
        </div>

        <!-- แผนที่ -->
        <div id="map" style="height: 400px;" class="rounded shadow mt-6"></div>
        <script>
            const map = L.map('map').setView([15.0, 100.75], 6);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            <?php foreach ($places as $place): ?>
            <?php if (!empty($place['lat']) && !empty($place['lng'])): ?>
                L.marker([<?= $place['lat'] ?>, <?= $place['lng'] ?>])
                    .addTo(map)
                    .bindPopup("<?= addslashes(htmlspecialchars($place['name'])) ?>");
            <?php endif; ?>
            <?php endforeach; ?>
        </script>
    </div>
</body>
</html>
