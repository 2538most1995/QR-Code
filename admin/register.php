<?php
// admin/register.php - สมัครสมาชิกผู้ใช้งานระบบ สกร.
require_once __DIR__ . '/../config/database.php';

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: ../index.php");
    exit;
}

$pdo = getDBConnection();
$orgShortName = getSystemSetting('org_short_name', 'สกร.');
$orgName = getSystemSetting('org_name', 'สำนักงานส่งเสริมการเรียนรู้');
$orgLogo = getSystemSetting('org_logo', 'assets/images/logo-sgr.png');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $password_confirm = trim($_POST['password_confirm'] ?? '');

    if (empty($fullname) || empty($username) || empty($password)) {
        $error = 'กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน';
    } elseif ($password !== $password_confirm) {
        $error = 'รหัสผ่านยืนยันไม่ตรงกัน';
    } elseif (strlen($password) < 4) {
        $error = 'รหัสผ่านต้องมีความยาวอย่างน้อย 4 ตัวอักษร';
    } else {
        // ตรวจสอบ username ซ้ำ
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            $error = 'ชื่อผู้ใช้นี้มีอยู่ในระบบแล้ว กรุณาใช้ชื่ออื่น';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $ins = $pdo->prepare("INSERT INTO admins (username, password, fullname, email, role, avatar, oauth_provider, status, created_at) VALUES (?, ?, ?, ?, 'เจ้าหน้าที่', 'assets/images/default-avatar.png', 'local', 'active', NOW())");
            $ins->execute([$username, $hashed, $fullname, $email]);

            $success = 'สมัครสมาชิกสำเร็จแล้ว! กำลังเข้าสู่ระบบ...';

            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $pdo->lastInsertId();
            $_SESSION['admin_username'] = $username;
            $_SESSION['admin_fullname'] = $fullname;
            $_SESSION['admin_role'] = 'เจ้าหน้าที่';
            $_SESSION['admin_avatar'] = 'assets/images/default-avatar.png';

            header("refresh:1.5;url=../index.php");
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก - ระบบสร้าง QR-Code คน <?= htmlspecialchars($orgShortName) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="../assets/css/style.css?v=<?= time() ?>" rel="stylesheet">
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full my-6">
        <!-- Brand Header -->
        <div class="text-center mb-6">
            <div class="w-16 h-16 bg-blue-600/10 border-2 border-blue-600/30 rounded-2xl flex items-center justify-center mx-auto mb-3 p-2 shadow-lg shadow-blue-500/10">
                <img src="../<?= htmlspecialchars($orgLogo) ?>" alt="สกร." class="max-h-full object-contain">
            </div>
            <h1 class="text-2xl font-bold text-slate-800 font-heading">ระบบสร้าง QR-Code คน <?= htmlspecialchars($orgShortName) ?></h1>
            <p class="text-xs text-slate-500 mt-1"><?= htmlspecialchars($orgName) ?></p>
        </div>

        <!-- Register Card -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-xl border border-slate-200/80">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-lg font-bold text-slate-800">สมัครใช้งานระบบ</h2>
                    <p class="text-xs text-slate-500">สร้างบัญชีผู้ใช้งานสำหรับบุคลากร</p>
                </div>
                <a href="login.php" class="text-xs text-blue-600 hover:underline font-semibold">เข้าสู่ระบบ</a>
            </div>

            <!-- Social 1-Click Registration / Login -->
            <div class="space-y-2.5 mb-5">
                <a href="oauth.php?provider=google&name=บุคลากร+สกร.+(Google)" class="w-full flex items-center justify-center space-x-3 py-2.5 px-4 bg-white hover:bg-slate-50 border border-slate-300 rounded-xl shadow-sm transition text-xs font-semibold text-slate-700 cursor-pointer">
                    <svg class="w-4 h-4" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                    </svg>
                    <span>สมัครใช้งานด้วย Google</span>
                </a>

                <a href="oauth.php?provider=line&name=บุคลากร+สกร.+(LINE)" class="w-full flex items-center justify-center space-x-3 py-2.5 px-4 bg-[#06C755] hover:bg-[#05b34c] text-white rounded-xl shadow-sm transition text-xs font-semibold cursor-pointer">
                    <img src="../assets/images/icons/icon-line.svg" class="w-4 h-4 rounded-full bg-white p-0.5">
                    <span>สมัครใช้งานด้วย LINE</span>
                </a>
            </div>

            <div class="relative flex items-center justify-center mb-5">
                <div class="border-t border-slate-200 w-full"></div>
                <div class="bg-white px-3 text-[11px] text-slate-400 absolute">หรือกรอกข้อมูลสมัคร</div>
            </div>

            <?php if ($error): ?>
                <div class="mb-4 p-3 bg-rose-50 border border-rose-200 text-rose-600 rounded-xl text-xs font-semibold flex items-center space-x-2">
                    <span>✕</span><span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl text-xs font-semibold flex items-center space-x-2">
                    <span>✓</span><span><?= htmlspecialchars($success) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-3.5">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">ชื่อ-นามสกุล <span class="text-rose-500">*</span></label>
                    <input type="text" name="fullname" placeholder="นายสมชาย ใจดี" required class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white focus:outline-none transition">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">ชื่อผู้ใช้งาน (Username) <span class="text-rose-500">*</span></label>
                    <input type="text" name="username" placeholder="somchai_sgr" required class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white focus:outline-none transition font-mono">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">อีเมล</label>
                    <input type="email" name="email" placeholder="somchai@sgr.go.th" class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white focus:outline-none transition">
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">รหัสผ่าน <span class="text-rose-500">*</span></label>
                        <input type="password" name="password" required placeholder="••••••••" class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white focus:outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">ยืนยันรหัสผ่าน <span class="text-rose-500">*</span></label>
                        <input type="password" name="password_confirm" required placeholder="••••••••" class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white focus:outline-none transition">
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-bold rounded-xl shadow-md shadow-blue-600/25 transition text-sm cursor-pointer">
                        สมัครสมาชิก
                    </button>
                </div>
            </form>

            <div class="mt-4 pt-3 border-t border-slate-100 text-center text-xs text-slate-500">
                มีบัญชีอยู่แล้ว? <a href="login.php" class="text-blue-600 font-semibold hover:underline">เข้าสู่ระบบที่นี่</a>
            </div>
        </div>
    </div>
</body>
</html>
