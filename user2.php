<?php
session_start();
$mysqli = new mysqli("localhost", "root", "", "health_checkin_db");
if (!isset($_SESSION['user_id']) || $_SESSION['is_admin']) {
    header("Location: index.php");
    exit();
}

$isLoggedIn = true;
$username = $_SESSION['username'];

// Process check-in
$checkinName = $_POST['checkin_name'] ?? '';
$checkinNote = $_POST['checkin_note'] ?? '';
$lat = isset($_POST['lat']) && is_numeric($_POST['lat']) ? floatval($_POST['lat']) : null;
$lng = isset($_POST['lng']) && is_numeric($_POST['lng']) ? floatval($_POST['lng']) : null;
$checkedIn = false;

if (!empty($checkinName)) {
    $stmt = $mysqli->prepare("INSERT INTO checkins (name, place, note, lat, lng, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssdd", $username, $checkinName, $checkinNote, $lat, $lng);
    $stmt->execute();
    $stmt->close();
    $checkedIn = true;
}

// Filter and fetch places
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
    <title>แดชบอร์ดผู้ใช้งาน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>👤 สวัสดีคุณ <?= htmlspecialchars($username) ?></h3>
        <div>
            <a href="index.php?logout=1" class="btn btn-outline-danger">ออกจากระบบ</a>
        </div>
    </div>

    <?php if (!$checkedIn): ?>
        <form method="POST" class="bg-white rounded shadow p-4 mb-4">
            <h5 class="mb-3">📍 เช็คอินสถานที่</h5>
            <input type="text" name="checkin_name" class="form-control mb-2" placeholder="ชื่อสถานที่ที่คุณอยู่" required>
            <input type="text" name="checkin_note" class="form-control mb-2" placeholder="หมายเหตุเพิ่มเติม (ถ้ามี)">
            <input type="hidden" name="lat" id="lat">
            <input type="hidden" name="lng" id="lng">
            <button type="submit" class="btn btn-success w-100">บันทึกเช็คอิน</button>
        </form>

        <script>
            $(function () {
                navigator.geolocation.getCurrentPosition(pos => {
                    $('#lat').val(pos.coords.latitude);
                    $('#lng').val(pos.coords.longitude);
                });

                $('input[name="checkin_name"]').autocomplete({
                    source: 'search_places.php',
                    minLength: 2
                });
            });
        </script>
    <?php else: ?>
        <div class="alert alert-success">
            ✅ เช็คอินเรียบร้อยแล้ว<?= !empty($checkinName) ? ' ที่ <strong>' . htmlspecialchars($checkinName) . '</strong>' : '' ?>!
        </div>
    <?php endif; ?>

    <div class="bg-white rounded p-4 shadow-sm mb-4">
    <h5 class="mb-3">🧘‍♀️ แหล่งท่องเที่ยวเชิงสุขภาพ</h5>
    <form method="GET" class="mb-3">
        <input type="text" name="filter" class="form-control" placeholder="ค้นหาด้วยคำว่า โยคะ, สมาธิ..." value="<?= htmlspecialchars($filter) ?>">
    </form>
    <div class="row">
        <?php
        $found = false;
        foreach ($places as $place):
            $matched = !$filter || stripos($place['name'], $filter) !== false || array_filter($place['tags'], fn($tag) => stripos($tag, $filter) !== false);
            if ($matched):
                $found = true;
        ?>
            <div class="col-md-4 mb-3">
                <div class="border rounded p-3 h-100">
                    <?php if (!empty($place['image'])): ?>
                        <img src="uploads/<?= htmlspecialchars($place['image']) ?>" class="img-fluid mb-2 rounded" style="max-height: 150px; object-fit: cover; width: 100%;">
                    <?php endif; ?>
                    <h5><?= htmlspecialchars($place['name']) ?></h5>
                    <p class="text-muted"><?= htmlspecialchars($place['description']) ?></p>
                    <small>📍 <?= htmlspecialchars($place['province']) ?></small>
                    <div class="mt-2 mb-2">
                        <?php foreach ($place['tags'] as $tag): ?>
                            <span class="badge bg-success me-1">#<?= htmlspecialchars(trim($tag)) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="checkin_name" value="<?= htmlspecialchars($place['name']) ?>">
                        <input type="hidden" name="checkin_note" value="">
                        <input type="hidden" name="lat" value="<?= htmlspecialchars($place['lat']) ?>">
                        <input type="hidden" name="lng" value="<?= htmlspecialchars($place['lng']) ?>">
                        <button type="submit" class="btn btn-sm btn-outline-primary w-100">เช็คอินที่นี่</button>
                    </form>
                </div>
            </div>
        <?php endif; endforeach;
        if (!$found): ?>
            <p class="text-center text-muted">ไม่พบผลลัพธ์</p>
        <?php endif; ?>
    </div>
</div>
<div class="bg-white rounded p-4 shadow-sm mb-4">
    <h5 class="mb-3">🕘 ประวัติการเช็คอินของคุณ</h5>
    <ul class="list-group">
        <?php
        // ดึงข้อมูลจาก checkins พร้อมชื่อและภาพจาก places
        $stmt = $mysqli->prepare("
            SELECT c.place, c.note, c.created_at, p.image 
            FROM checkins c 
            LEFT JOIN places p ON c.place = p.name 
            WHERE c.name = ? 
            ORDER BY c.created_at DESC 
            LIMIT 5
        ");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $res = $stmt->get_result();

        while ($row = $res->fetch_assoc()):
        ?>
            <li class="list-group-item d-flex align-items-start">
                <?php if (!empty($row['image'])): ?>
                    <img src="uploads/<?= htmlspecialchars($row['image']) ?>" class="me-3 rounded" style="width: 80px; height: 80px; object-fit: cover;">
                <?php else: ?>
                    <div class="me-3 rounded bg-secondary" style="width: 80px; height: 80px;"></div>
                <?php endif; ?>

                <div>
                    <strong><?= htmlspecialchars($row['created_at']) ?></strong><br>
                    📍 <strong><?= htmlspecialchars($row['place']) ?></strong><br>
                    <?php if (!empty($row['note'])): ?>
                        <em><?= htmlspecialchars($row['note']) ?></em>
                    <?php endif; ?>
                </div>
            </li>
        <?php endwhile; ?>
    </ul>
</div>

    <div id="map" style="height: 400px;" class="rounded shadow"></div>
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
