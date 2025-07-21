<?php
session_start();
if (!($_SESSION['is_admin'] ?? false)) {
    header("Location: index.php");
    exit();
}

$mysqli = new mysqli("localhost", "root", "", "health_checkin_db");
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$startDate = $_GET['start'] ?? date('Y-m-01');
$endDate = $_GET['end'] ?? date('Y-m-d');

$totalUsers = $mysqli->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()['total'];
$totalPlaces = $mysqli->query("SELECT COUNT(*) AS total FROM places")->fetch_assoc()['total'];
$totalCheckins = $mysqli->query("SELECT COUNT(*) AS total FROM checkins WHERE DATE(created_at) BETWEEN '$startDate' AND '$endDate'")->fetch_assoc()['total'];
$recentCheckins = $mysqli->query("SELECT * FROM checkins WHERE DATE(created_at) BETWEEN '$startDate' AND '$endDate' ORDER BY created_at DESC LIMIT 10");

$chartData = [];
$labels = [];
$result = $mysqli->query("SELECT DATE(created_at) as date, COUNT(*) as total FROM checkins WHERE DATE(created_at) BETWEEN '$startDate' AND '$endDate' GROUP BY DATE(created_at) ORDER BY date ASC");
while ($row = $result->fetch_assoc()) {
    $chartData[] = $row['total'];
    $labels[] = $row['date'];
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แดชบอร์ดสถิติ</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
    <h2 class="text-3xl font-bold mb-6">📊 แดชบอร์ดสถิติ</h2>

    <form method="get" class="flex flex-wrap gap-4 items-end mb-6">
        <div>
            <label for="start" class="block mb-1 font-medium">เริ่มต้น</label>
            <input type="date" class="border p-2 rounded" name="start" id="start" value="<?= $startDate ?>">
        </div>
        <div>
            <label for="end" class="block mb-1 font-medium">สิ้นสุด</label>
            <input type="date" class="border p-2 rounded" name="end" id="end" value="<?= $endDate ?>">
        </div>
        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">🔍 ค้นหา</button>
    </form>

    <!-- สถิติรวม -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white p-6 rounded shadow text-center">
            <h4 class="text-xl font-semibold mb-2">👥 ผู้ใช้</h4>
            <p class="text-3xl font-bold"><?= $totalUsers ?></p>
        </div>
        <div class="bg-white p-6 rounded shadow text-center">
            <h4 class="text-xl font-semibold mb-2">📍 สถานที่</h4>
            <p class="text-3xl font-bold"><?= $totalPlaces ?></p>
        </div>
        <div class="bg-white p-6 rounded shadow text-center">
            <h4 class="text-xl font-semibold mb-2">🕘 เช็คอิน</h4>
            <p class="text-3xl font-bold"><?= $totalCheckins ?></p>
        </div>
    </div>

    <!-- กราฟ -->
    <div class="bg-white p-6 rounded shadow mb-6">
        <h5 class="text-lg font-semibold mb-4">📈 กราฟจำนวนเช็คอินรายวัน</h5>
        <canvas id="checkinChart" height="100"></canvas>
        <script>
        const ctx = document.getElementById('checkinChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                    label: 'จำนวนเช็คอิน',
                    data: <?= json_encode($chartData) ?>,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        </script>
    </div>

    <!-- รายการเช็คอินล่าสุด -->
    <div class="bg-white p-6 rounded shadow">
        <h5 class="text-lg font-semibold mb-4">📝 เช็คอินล่าสุด</h5>
        <div class="overflow-x-auto">
            <table class="min-w-full border">
                <thead>
                    <tr class="bg-gray-200 text-left">
                        <th class="py-2 px-4 border-b">ชื่อสถานที่</th>
                        <th class="py-2 px-4 border-b">หมายเหตุ</th>
                        <th class="py-2 px-4 border-b">วันที่</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $recentCheckins->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-100">
                            <td class="py-2 px-4 border-b"><?= htmlspecialchars($row['name']) ?></td>
                            <td class="py-2 px-4 border-b"><?= htmlspecialchars($row['note']) ?></td>
                            <td class="py-2 px-4 border-b"><?= $row['created_at'] ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>


