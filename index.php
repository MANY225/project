<?php
session_start();
$mysqli = new mysqli("localhost", "root", "", "health_checkin_db");
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = $_SESSION['is_admin'] ?? false;

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

            header("Location: " . ($user['role'] === 'admin' ? "admin.php" : "user.php"));
            exit();
        } else {
            $login_error = "รหัสผ่านไม่ถูกต้อง";
        }
    } else {
        $login_error = "ไม่พบผู้ใช้งานนี้";
    }
    $stmt->close();
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เข้าสู่ระบบ</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-r from-green-400 to-yellow-300 min-h-screen flex items-center justify-center">

<div class="w-full max-w-4xl bg-white rounded-xl shadow-lg overflow-hidden grid md:grid-cols-2">
    <!-- ซ้าย: Welcome back -->
    <div class="bg-teal-900 text-white flex flex-col justify-center items-center p-10">
        <h2 class="text-3xl font-bold mb-4">Welcome Back!</h2>
        <p class="mb-6 text-center">Provide your personal details to use all features</p>
       
    </div>

    <!-- ขวา: แบบฟอร์มเข้าสู่ระบบ -->
    <div class="p-10">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">เข้าสู่ระบบ</h2>

        <?php if (isset($login_error)): ?>
            <div class="bg-red-100 text-red-700 px-4 py-2 rounded mb-4">
                <?= htmlspecialchars($login_error) ?>
            </div>
        <?php endif; ?>

        <form method="post" class="space-y-4">
            <input type="text" name="username" placeholder="ชื่อผู้ใช้" required
                   class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-green-500" />

            <input type="password" name="password" placeholder="รหัสผ่าน" required
                   class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-green-500" />

            <button type="submit"
                    class="w-full bg-yellow-400 hover:bg-yellow-500 text-white font-semibold py-2 rounded transition">
                SIGN IN
            </button>
        </form>

        <p class="text-center mt-6 text-gray-600">ยังไม่มีบัญชี? <a href="register.php" class="text-blue-600 hover:underline">สมัครสมาชิก</a></p>
    </div>
</div>

</body>
</html>

