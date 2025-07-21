<?php
session_start();
$mysqli = new mysqli("localhost", "root", "", "health_checkin_db");
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = $_SESSION['is_admin'] ?? false;

// Login
if (isset($_POST['username'], $_POST['password'])) {
    $u = $_POST['username'];
    $p = $_POST['password'];

    $stmt = $mysqli->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $u);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $user = $res->fetch_assoc();
        if (password_verify($p, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = ($user['role'] === 'admin');
            header("Location: index2.php");
            exit();
        } else {
            $login_error = "รหัสผ่านไม่ถูกต้อง";
        }
    } else {
        $login_error = "ไม่พบผู้ใช้งานนี้";
    }
    $stmt->close();
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index2.php");
    exit();
}

// Checkin process
$checkinName = $_POST['checkin_name'] ?? '';
$checkinNote = $_POST['checkin_note'] ?? '';
$lat = isset($_POST['lat']) ? floatval($_POST['lat']) : null;
$lng = isset($_POST['lng']) ? floatval($_POST['lng']) : null;
$checkedIn = false;

if ($isLoggedIn && !empty($checkinName)) {
    $stmt = $mysqli->prepare("INSERT INTO checkins (name, note, lat, lng, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssdd", $checkinName, $checkinNote, $lat, $lng);
    $stmt->execute();
    $stmt->close();
    $checkedIn = true;
}

// Filter for places
$filter = $_GET['filter'] ?? '';

// Fetch places
$demoPlaces = [];
$res = $mysqli->query("SELECT * FROM places");
while ($row = $res->fetch_assoc()) {
    $row['tags'] = explode(',', $row['tags']);
    $demoPlaces[] = $row;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8" />
    <title>เช็คอินท่องเที่ยวสุขภาพ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body class="bg-light">
<div class="container py-5">
    <h1 class="mb-4 text-center">🧘‍♀️ Health Tourism Check-In</h1>

    <?php if (!$isLoggedIn): ?>
        <form method="POST" class="bg-white rounded p-4 shadow-sm mb-4">
            <h4 class="mb-3">🔐 เข้าสู่ระบบ</h4>
            <?php if (isset($login_error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($login_error) ?></div>
            <?php endif; ?>
            <input type="text" name="username" class="form-control mb-2" placeholder="Username" required />
            <input type="password" name="password" class="form-control mb-3" placeholder="Password" required />
            <button class="btn btn-primary w-100">เข้าสู่ระบบ</button>
            <div class="text-center mt-2"><a href="register.php">📌 สมัครสมาชิก</a></div>
        </form>
    <?php else: ?>
        <div class="d-flex justify-content-between mb-3">
            <div>👋 ยินดีต้อนรับ, <?= htmlspecialchars($_SESSION['username']) ?></div>
            <a href="?logout=1" class="btn btn-sm btn-outline-danger">ออกจากระบบ</a>
             <a href="dashboard.php" class="btn btn-sm btn-outline-danger">ดูข้อมูล</a>
        </div>

        <?php if (!$checkedIn): ?>
            <form method="POST" class="bg-white rounded p-4 shadow-sm mb-4">
                <h4 class="mb-3">📍 เช็คอินสถานที่</h4>
                <input type="text" name="checkin_name" class="form-control mb-2" placeholder="ชื่อสถานที่ที่คุณอยู่" required />
                <input type="text" name="checkin_note" class="form-control mb-2" placeholder="หมายเหตุเพิ่มเติม (ถ้ามี)" />
                <input type="hidden" name="lat" id="lat" />
                <input type="hidden" name="lng" id="lng" />
                <button type="submit" class="btn btn-success w-100">บันทึกเช็คอิน</button>
            </form>
            <script>
                navigator.geolocation.getCurrentPosition(pos => {
                    document.getElementById('lat').value = pos.coords.latitude;
                    document.getElementById('lng').value = pos.coords.longitude;
                });
            </script>
        <?php else: ?>
            <div class="alert alert-success mb-4">✅ เช็คอินเรียบร้อยแล้ว!</div>
        <?php endif; ?>

        <!-- Filter + Places -->
        <div class="bg-white rounded p-4 shadow-sm mb-4">
            <h4 class="mb-3">💡 แนะนำแหล่งท่องเที่ยวเชิงสุขภาพ</h4>
            <form method="GET" class="mb-3">
                <input type="text" name="filter" value="<?= htmlspecialchars($filter) ?>" class="form-control" placeholder="ค้นหาด้วยคำว่า โยคะ, สมาธิ..." />
            </form>
            <div class="row">
                <?php
                $found = false;
                foreach ($demoPlaces as $place) {
                    $matched = !$filter || stripos($place['name'], $filter) !== false || array_filter($place['tags'], fn($tag) => stripos($tag, $filter) !== false);
                    if ($matched):
                        $found = true;
                ?>
                    <div class="col-md-4 mb-3">
                        <div class="border rounded p-3 h-100">
                            <h5><?= htmlspecialchars($place['name']) ?></h5>
                            <p class="text-muted"><?= htmlspecialchars($place['description']) ?></p>
                            <p><small>📍 <?= htmlspecialchars($place['province']) ?></small></p>
                            <div>
                                <?php foreach ($place['tags'] as $tag): ?>
                                    <span class="badge bg-success me-1">#<?= htmlspecialchars(trim($tag)) ?></span>
                                <?php endforeach; ?>
                            </div>
                            <?php if ($isAdmin): ?>
                                <a href="delete_place.php?id=<?= $place['id'] ?>" class="text-danger" onclick="return confirm('คุณต้องการลบสถานที่นี้?');">🗑 ลบ</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php
                    endif;
                }
                if (!$found):
                ?>
                    <p class="text-center text-muted">ไม่พบผลลัพธ์</p>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($isAdmin): ?>
        <!-- Admin: Add Place -->
        <div class="bg-white rounded p-4 shadow-sm mb-4">
            <h5 class="mb-3">🧑‍💼 เพิ่มสถานที่ท่องเที่ยว (Admin)</h5>
            <form method="POST" action="add_place.php">
                <input type="text" name="name" class="form-control mb-2" placeholder="ชื่อสถานที่" required />
                <textarea name="description" class="form-control mb-2" placeholder="คำอธิบาย" required></textarea>
                <input type="text" name="province" class="form-control mb-2" placeholder="จังหวัด" required />
                <input type="text" name="tags" class="form-control mb-2" placeholder="คำค้น เช่น โยคะ,สปา" required />
                <input type="text" name="lat" class="form-control mb-2" placeholder="ละติจูด" />
                <input type="text" name="lng" class="form-control mb-2" placeholder="ลองจิจูด" />
                <button class="btn btn-outline-primary w-100">เพิ่มสถานที่</button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Check-in history -->
        <div class="bg-white rounded p-4 shadow-sm mb-4">
            <h5 class="mb-3">🕘 ประวัติการเช็คอินของคุณ</h5>
            <ul class="list-group">
                <?php
                $stmt = $mysqli->prepare("SELECT name, note, created_at FROM checkins WHERE name = ? ORDER BY created_at DESC LIMIT 5");
                $stmt->bind_param("s", $_SESSION['username']);
                $stmt->execute();
                $res = $stmt->get_result();
                while ($row = $res->fetch_assoc()):
                ?>
                    <li class="list-group-item">
                        <strong><?= htmlspecialchars($row['name']) ?></strong>
                        <small class="text-muted"><?= $row['created_at'] ?></small><br />
                        <em><?= htmlspecialchars($row['note']) ?></em>
                    </li>
                <?php endwhile; ?>
            </ul>
        </div>

        <!-- Map -->
        <div id="map" class="mt-4" style="height: 400px;"></div>
        <script>
            const map = L.map('map').setView([18.796143, 98.979263], 6);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap'
            }).addTo(map);

            <?php foreach ($demoPlaces as $place): ?>
                L.marker([<?= $place['lat'] ?>, <?= $place['lng'] ?>])
                 .addTo(map)
                 .bindPopup("<?= addslashes(htmlspecialchars($place['name'])) ?>");
            <?php endforeach; ?>
        </script>
    <?php endif; ?>
</div>
</body>
</html>
