<?php
session_start();
$mysqli = new mysqli("localhost", "root", "", "health_checkin_db");

// Redirect if not logged in or is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['is_admin']) {
    header("Location: index.php");
    exit();
}

$isLoggedIn = true;
$username = $_SESSION['username'];

// Process check-in
$checkinName = $_POST['checkin_name'] ?? '';
$checkinNote = $_POST['checkin_note'] ?? '';
// Lat and Lng will now only come from the place selection, not automatic geolocation
$lat = isset($_POST['lat']) && is_numeric($_POST['lat']) ? floatval($_POST['lat']) : null;
$lng = isset($_POST['lng']) && is_numeric($_POST['lng']) ? floatval($_POST['lng']) : null;
$checkedIn = false;

if (!empty($checkinName)) {
    // Check if the place exists in the 'places' table to get its lat/lng
    // If lat/lng were not set by "check-in here" buttons, try to get from places table
    if ($lat === null || $lng === null) {
        $stmtPlace = $mysqli->prepare("SELECT lat, lng FROM places WHERE name = ? LIMIT 1");
        $stmtPlace->bind_param("s", $checkinName);
        $stmtPlace->execute();
        $resultPlace = $stmtPlace->get_result();
        if ($rowPlace = $resultPlace->fetch_assoc()) {
            $lat = $rowPlace['lat'];
            $lng = $rowPlace['lng'];
        }
        $stmtPlace->close();
    }

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แดชบอร์ดผู้ใช้งาน</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        /* Custom styles for the background image and overlay */
        .hero-section {
            background-image: url('img/co.jpg'); /* ใช้ภาพ co.jpg เป็นพื้นหลัง */
            background-size: cover;
            background-position: center right; /* จัดตำแหน่งภาพไปทางขวาเพื่อให้เห็นคนเล่นโยคะ */
            position: relative;
            z-index: 0;
            padding-top: 10rem; /* ปรับ padding ด้านบนให้มีพื้นที่สำหรับข้อความ */
            padding-bottom: 10rem; /* ปรับ padding ด้านล่าง */
            overflow: hidden; /* ป้องกันภาพล้น */
        }
        .hero-overlay {
            background-color: rgba(0, 0, 0, 0.3); /* Dark overlay */
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 1;
        }
        .hero-content {
            position: relative;
            z-index: 2;
        }
        /* Removed the separate yoga person image (it's now part of the background) */
        .yoga-person-img {
            display: none;
        }
        /* Adjust autocomplete z-index */
        .ui-autocomplete {
            z-index: 1000; /* Ensure autocomplete dropdown appears above other elements */
        }
        /* Custom scrollbar for history list to match image */
        .history-list-scroll {
            max-height: 480px; /* Adjust as needed, should be similar to map height */
            overflow-y: auto;
            scrollbar-width: thin; /* Firefox */
            scrollbar-color: #a0aec0 #edf2f7; /* thumb and track color */
        }
        .history-list-scroll::-webkit-scrollbar {
            width: 8px;
        }
        .history-list-scroll::-webkit-scrollbar-track {
            background: #edf2f7; /* Light gray track */
            border-radius: 10px;
        }
        .history-list-scroll::-webkit-scrollbar-thumb {
            background-color: #a0aec0; /* Gray thumb */
            border-radius: 10px;
            border: 2px solid #edf2f7; /* Space around thumb */
        }
    </style>
</head>
<body class="bg-gray-100 font-sans antialiased">

    <nav class="bg-transparent p-4 flex justify-between items-center relative z-10">
        <div class="text-xl font-bold text-black">LOGO</div>
        <div class="flex items-center space-x-4">
            <span class="text-black hidden md:block">👤 สวัสดีคุณ <span class="font-semibold text-black"><?= htmlspecialchars($username) ?></span></span>
            <a href="index.php?logout=1" class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600 transition duration-300">ออกจากระบบ</a>
        </div>
    </nav>

    <div class="hero-section text-white px-4 md:px-8 relative overflow-hidden">
        <div class="hero-overlay"></div>
        <div class="hero-content max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between">
            <div class="text-center md:text-left md:w-1/2 mb-8 md:mb-0">
                <h1 class="text-4xl md:text-5xl font-extrabold leading-tight mb-4 drop-shadow-lg">WHERE WOULD YOU LIKE TO CHECK IN TODAY?</h1>
                <?php if (!$checkedIn): ?>
                    <form method="POST" class="bg-white bg-opacity-20 backdrop-filter backdrop-blur-sm rounded-lg p-6 space-y-4 md:max-w-md mx-auto md:mx-0">
                        <input type="text" name="checkin_name" id="checkin_name" class="w-full px-4 py-2 bg-white bg-opacity-80 text-gray-800 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 placeholder-gray-600" placeholder="ชื่อสถานที่ที่คุณอยู่" required>
                        <input type="text" name="checkin_note" class="w-full px-4 py-2 bg-white bg-opacity-80 text-gray-800 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 placeholder-gray-600" placeholder="หมายเหตุเพิ่มเติม (ถ้ามี)">
                        <input type="hidden" name="lat" id="lat_from_place">
                        <input type="hidden" name="lng" id="lng_from_place">
                        <button type="submit" class="w-full px-6 py-3 bg-blue-600 text-white font-semibold rounded-md hover:bg-blue-700 transition duration-300 shadow-lg">บันทึกเช็คอิน</button>
                    </form>
                <?php else: ?>
                    <div class="bg-green-500 bg-opacity-80 text-white px-6 py-3 rounded-md shadow-lg text-center md:max-w-md mx-auto md:mx-0">
                        ✅ เช็คอินเรียบร้อยแล้ว<?= !empty($checkinName) ? ' ที่ <strong class="font-semibold">' . htmlspecialchars($checkinName) . '</strong>' : '' ?>!
                    </div>
                <?php endif; ?>
            </div>
            </div>
    </div>

    <div class="container mx-auto px-4 py-8">

<div class="bg-white rounded-lg shadow-lg p-3 flex flex-col md:flex-row gap-4 mb-8">
            <div id="map" class="h-[500px] w-full md:w-2/3 rounded-lg overflow-hidden border border-gray-200"></div>

            <div class="w-full md:w-1/3 p-2 border border-gray-200 rounded-lg overflow-hidden">
                <h2 class="text-xl font-bold text-gray-800 mb-4">🕘 ประวัติการเช็คอินของคุณ</h2>
                <div class="history-list-scroll"> <ul class="space-y-3">
                        <?php
                        $stmt = $mysqli->prepare("
                            SELECT c.place, c.note, c.created_at, p.image
                            FROM checkins c
                            LEFT JOIN places p ON c.place = p.name
                            WHERE c.name = ?
                            ORDER BY c.created_at DESC
                            LIMIT 5
                        "); // แสดงประวัติ 5 รายการล่าสุด
                        $stmt->bind_param("s", $username);
                        $stmt->execute();
                        $res = $stmt->get_result();

                        if ($res->num_rows > 0):
                            while ($row = $res->fetch_assoc()):
                        ?>
                                <li class="flex items-start p-2 border-b border-gray-100 last:border-b-0">
                                    <?php if (!empty($row['image'])): ?>
                                        <img src="uploads/<?= htmlspecialchars($row['image']) ?>" alt="Place Image" class="w-16 h-16 object-cover rounded-md mr-3 flex-shrink-0">
                                    <?php else: ?>
                                        <div class="w-16 h-16 bg-gray-200 rounded-md mr-3 flex-shrink-0 flex items-center justify-center text-gray-500 text-xs text-center">No Image</div>
                                    <?php endif; ?>

                                    <div>
                                        <p class="text-xs text-gray-500 mb-0.5"><?= htmlspecialchars($row['created_at']) ?></p>
                                        <p class="text-base font-semibold text-gray-800 mb-0.5">📍 <?= htmlspecialchars($row['place']) ?></p>
                                        <?php if (!empty($row['note'])): ?>
                                            <p class="text-gray-600 italic text-sm">"<?= htmlspecialchars($row['note']) ?>"</p>
                                        <?php endif; ?>
                                    </div>
                                </li>
                        <?php
                            endwhile;
                        else:
                        ?>
                            <p class="text-center text-gray-500 py-4">ยังไม่มีประวัติการเช็คอิน</p>
                        <?php
                        endif;
                        $stmt->close();
                        ?>
                    </ul>
                </div>
            </div>
        </div>


        <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
            <h2 class="text-3xl font-bold text-gray-800 mb-6">🧘‍♀️ แหล่งท่องเที่ยวเชิงสุขภาพ</h2>
            <form method="GET" class="mb-6">
                <input type="text" name="filter" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="ค้นหาด้วยคำว่า โยคะ, สมาธิ..." value="<?= htmlspecialchars($filter) ?>">
            </form>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <?php
                $found = false;
                foreach ($places as $place):
                    $matched = !$filter || stripos($place['name'], $filter) !== false || array_filter($place['tags'], fn($tag) => stripos($tag, $filter) !== false);
                    if ($matched):
                        $found = true;
                ?>
                    <div class="border border-gray-200 rounded-lg overflow-hidden shadow-sm hover:shadow-md transition duration-300 flex flex-col">
                        <?php if (!empty($place['image'])): ?>
                            <img src="uploads/<?= htmlspecialchars($place['image']) ?>" alt="<?= htmlspecialchars($place['name']) ?>" class="w-full h-40 object-cover">
                        <?php else: ?>
                            <div class="w-full h-40 bg-gray-200 flex items-center justify-center text-gray-500">No Image</div>
                        <?php endif; ?>
                        <div class="p-4 flex flex-col flex-grow">
                            <h3 class="text-xl font-semibold text-gray-800 mb-2"><?= htmlspecialchars($place['name']) ?></h3>
                            <p class="text-gray-600 text-sm mb-3 flex-grow"><?= htmlspecialchars($place['description']) ?></p>
                            <div class="text-gray-500 text-sm mb-3">📍 <?= htmlspecialchars($place['province']) ?></div>
                            <div class="mb-4">
                                <?php foreach ($place['tags'] as $tag): ?>
                                    <span class="inline-block bg-green-200 text-green-800 text-xs px-2 py-1 rounded-full mr-1 mb-1">#<?= htmlspecialchars(trim($tag)) ?></span>
                                <?php endforeach; ?>
                            </div>
                            <form method="POST" class="mt-auto">
                                <input type="hidden" name="checkin_name" value="<?= htmlspecialchars($place['name']) ?>">
                                <input type="hidden" name="checkin_note" value="">
                                <input type="hidden" name="lat" value="<?= htmlspecialchars($place['lat']) ?>">
                                <input type="hidden" name="lng" value="<?= htmlspecialchars($place['lng']) ?>">
                                <button type="submit" class="w-full px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition duration-300">เช็คอินที่นี่</button>
                            </form>
                        </div>
                    </div>
                <?php endif; endforeach;
                if (!$found): ?>
                    <p class="col-span-full text-center text-gray-500">ไม่พบผลลัพธ์</p>
                <?php endif; ?>
            </div>
        </div>

        
    </div>

    <script>
        // Autocomplete for check-in name
        $(function () {
            $('input[name="checkin_name"]').autocomplete({
                source: 'search_places.php', // This file should return JSON like [{"label": "Place Name", "value": "Place Name"}]
                minLength: 2
            });
        });

        // Leaflet Map Initialization
        // Ensure the map container has a defined height before initializing the map
        const map = L.map('map').setView([13.7563, 100.5018], 6); // Centered roughly on Thailand

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        <?php foreach ($places as $place): ?>
        <?php if (!empty($place['lat']) && !empty($place['lng'])): ?>
        L.marker([<?= $place['lat'] ?>, <?= $place['lng'] ?>])
            .addTo(map)
            .bindPopup("<?= addslashes(htmlspecialchars($place['name'])) ?>");
        <?php endif; ?>
        <?php endforeach; ?>

        // Invalidate map size after dynamic layout changes if needed
        setTimeout(function() {
            map.invalidateSize();
        }, 100);
    </script>
</body>
</html>