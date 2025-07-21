<?php
session_start();
$mysqli = new mysqli("localhost", "root", "", "health_checkin_db");
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (empty($fullname)) $errors[] = "กรุณากรอกชื่อ-นามสกุล";
    if (empty($email)) $errors[] = "กรุณากรอกอีเมล";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "รูปแบบอีเมลไม่ถูกต้อง";
    if (empty($phone)) $errors[] = "กรุณากรอกเบอร์โทร";
    if (empty($username)) $errors[] = "กรุณากรอกชื่อผู้ใช้";
    if (empty($password)) $errors[] = "กรุณากรอกรหัสผ่าน";
    if ($password !== $password_confirm) $errors[] = "รหัสผ่านไม่ตรงกัน";

    // ตรวจสอบว่าชื่อผู้ใช้ซ้ำหรือไม่
    $stmt = $mysqli->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) $errors[] = "ชื่อผู้ใช้นี้ถูกใช้งานแล้ว";
    $stmt->close();

    if (empty($errors)) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $mysqli->prepare("INSERT INTO users (username, password, role, fullname, email, phone) VALUES (?, ?, 'user', ?, ?, ?)");
        $stmt->bind_param("sssss", $username, $passwordHash, $fullname, $email, $phone);
        if ($stmt->execute()) {
            header("Location: index.php");
            exit();
        } else {
            $errors[] = "เกิดข้อผิดพลาดในการสมัครสมาชิก";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>สมัครสมาชิก</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-r from-green-400 to-yellow-300 min-h-screen flex items-center justify-center">

<div class="w-full max-w-4xl bg-white rounded-xl shadow-lg overflow-hidden grid md:grid-cols-2">
    <!-- ซ้าย: Welcome message -->
    <div class="bg-teal-900 text-white flex flex-col justify-center items-center p-10">
        <h2 class="text-3xl font-bold mb-4">Hello, Friend!</h2>
        <p class="mb-6 text-center">หากคุณมีบัญชีอยู่แล้ว<br>สามารถเข้าสู่ระบบได้ที่นี่</p>
        <a href="index.php" class="border border-white py-2 px-6 rounded hover:bg-white hover:text-teal-900 transition">SIGN IN</a>
    </div>

    <!-- ขวา: แบบฟอร์มสมัครสมาชิก -->
    <div class="p-10">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">สมัครสมาชิก</h2>

        <?php if (!empty($errors)): ?>
            <div class="bg-red-100 text-red-700 px-4 py-2 rounded mb-4">
                <ul class="list-disc list-inside">
                    <?php foreach($errors as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <input type="text" name="fullname" placeholder="ชื่อ-นามสกุล" required
                   class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-green-500" />

            <input type="email" name="email" placeholder="อีเมล" required
                   class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-green-500" />

            <input type="tel" name="phone" placeholder="เบอร์โทร" required
                   class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-green-500" />

            <input type="text" name="username" placeholder="ชื่อผู้ใช้ (สำหรับเข้าสู่ระบบ)" required
                   class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-green-500" />

            <input type="password" name="password" placeholder="รหัสผ่าน" required
                   class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-green-500" />

            <input type="password" name="password_confirm" placeholder="ยืนยันรหัสผ่าน" required
                   class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-green-500" />

            <button type="submit"
                    class="w-full bg-yellow-400 hover:bg-yellow-500 text-white font-semibold py-2 rounded transition">
                SIGN UP
            </button>
        </form>

        <p class="text-center mt-6 text-gray-600">มีบัญชีอยู่แล้ว? <a href="index.php" class="text-blue-600 hover:underline">เข้าสู่ระบบ</a></p>
    </div>
</div>

</body>
</html>
