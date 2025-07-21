<?php
session_start();
if (!($_SESSION['is_admin'] ?? false)) {
    exit("ไม่อนุญาตให้เข้าถึงหน้านี้");
}

$mysqli = new mysqli("localhost", "root", "", "health_checkin_db");
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// ลบสมาชิก
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    if ($delete_id !== $_SESSION['user_id']) {
        $stmt = $mysqli->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();
        $stmt->close();
        header("Location: manage_users.php");
        exit();
    } else {
        $error = "ไม่สามารถลบตัวเองได้";
    }
}

// เปลี่ยนบทบาท
if (isset($_POST['change_role_id'], $_POST['new_role'])) {
    $change_id = intval($_POST['change_role_id']);
    $new_role = $_POST['new_role'] === 'admin' ? 'admin' : 'user';
    if ($change_id !== $_SESSION['user_id']) {
        $stmt = $mysqli->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->bind_param("si", $new_role, $change_id);
        $stmt->execute();
        $stmt->close();
        header("Location: manage_users.php");
        exit();
    } else {
        $error = "ไม่สามารถเปลี่ยนสิทธิ์ตัวเองได้";
    }
}

$result = $mysqli->query("SELECT id, username, role FROM users ORDER BY id ASC");
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8" />
    <title>จัดการสมาชิก</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
    <h1 class="text-3xl font-bold mb-6">🧑‍💼 จัดการสมาชิก</h1>

    <?php if (isset($error)): ?>
        <div class="bg-red-100 text-red-700 p-4 rounded mb-4"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="overflow-x-auto">
        <table class="min-w-full bg-white rounded shadow text-sm">
            <thead class="bg-gray-200 text-left">
                <tr>
                    <th class="py-3 px-4 border-b">#</th>
                    <th class="py-3 px-4 border-b">ชื่อผู้ใช้</th>
                    <th class="py-3 px-4 border-b">บทบาท</th>
                    <th class="py-3 px-4 border-b">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($user = $result->fetch_assoc()): ?>
                    <?php if ($user['id'] === $_SESSION['user_id']) continue; ?>
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 px-4 border-b"><?= $user['id'] ?></td>
                        <td class="py-2 px-4 border-b"><?= htmlspecialchars($user['username']) ?></td>
                        <td class="py-2 px-4 border-b">
                            <form method="POST">
                                <input type="hidden" name="change_role_id" value="<?= $user['id'] ?>">
                                <select name="new_role" class="border rounded p-1 text-sm" onchange="this.form.submit()">
                                    <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>ผู้ใช้</option>
                                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>แอดมิน</option>
                                </select>
                            </form>
                        </td>
                        <td class="py-2 px-4 border-b">
                            <a href="manage_users.php?delete_id=<?= $user['id'] ?>"
                               class="text-red-600 hover:underline"
                               onclick="return confirm('แน่ใจหรือไม่ที่จะลบสมาชิกนี้?');">ลบ</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
