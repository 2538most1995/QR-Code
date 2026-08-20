<?php
// admin/login.php - เข้าสู่ระบบผู้ใช้งาน & ผู้ดูแลระบบ สกร.
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($username) && !empty($password)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password'])) {
                if ($admin['status'] === 'inactive') {
                    $error = 'บัญชีผู้ใช้นี้ถูกระงับการใช้งานชั่วคราว กรุณาติดต่อผู้ดูแลระบบ';
                } else {
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    $_SESSION['admin_fullname'] = $admin['fullname'];
                    $_SESSION['admin_role'] = $admin['role'];
                    $_SESSION['admin_avatar'] = $admin['avatar'];
                    $_SESSION['admin_provider'] = $admin['oauth_provider'];

                    header("Location: ../index.php");
                    exit;
                }
            } else {
                $error = 'ชื่อผู้ใช้งานหรือรหัสผ่านไม่ถูกต้อง';
            }
        } catch (Exception $e) {
            $error = 'เกิดข้อผิดพลาดในการเข้าสู่ระบบ: ' . $e->getMessage();
        }
    } else {
        $error = 'กรุณากรอกชื่อผู้ใช้งานและรหัสผ่านให้ครบถ้วน';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - ระบบสร้าง QR-Code คน <?= htmlspecialchars($orgShortName) ?></title>
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

        <!-- Login Card -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-xl border border-slate-200/80">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-lg font-bold text-slate-800">เข้าสู่ระบบ</h2>
                    <p class="text-xs text-slate-500">กรอกข้อมูลบัญชีเพื่อเข้าสู่ระบบ</p>
                </div>
                <a href="register.php" class="text-xs text-blue-600 hover:underline font-semibold">สมัครสมาชิก</a>
            </div>

            <!-- Social 1-Click Login -->
            <div class="space-y-2.5 mb-5">
                <a href="oauth.php?provider=google&name=บุคลากร+สกร.+(Google)" class="w-full flex items-center justify-center space-x-3 py-2.5 px-4 bg-white hover:bg-slate-50 border border-slate-300 rounded-xl shadow-sm transition text-xs font-semibold text-slate-700 cursor-pointer">
                    <svg class="w-4 h-4" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                    </svg>
                    <span>เข้าสู่ระบบด้วย Google</span>
                </a>

                <a href="oauth.php?provider=line&name=บุคลากร+สกร.+(LINE)" class="w-full flex items-center justify-center space-x-3 py-2.5 px-4 bg-[#06C755] hover:bg-[#05b34c] text-white rounded-xl shadow-sm transition text-xs font-semibold cursor-pointer">
                    <img src="../assets/images/icons/icon-line.svg" class="w-4 h-4 rounded-full bg-white p-0.5">
                    <span>เข้าสู่ระบบด้วย LINE</span>
                </a>
            </div>

            <div class="relative flex items-center justify-center mb-5">
                <div class="border-t border-slate-200 w-full"></div>
                <div class="bg-white px-3 text-[11px] text-slate-400 absolute">หรือเข้าสู่ระบบด้วยรหัสผ่าน</div>
            </div>

            <?php if ($error): ?>
                <div class="mb-4 p-3 bg-rose-50 border border-rose-200 text-rose-600 rounded-xl text-xs font-semibold flex items-center space-x-2">
                    <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">ชื่อผู้ใช้งาน หรือ อีเมล</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        </div>
                        <input type="text" name="username" autocomplete="off" required class="w-full pl-10 pr-4 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white focus:outline-none transition" placeholder="ระบุชื่อผู้ใช้งาน หรืออีเมล">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">รหัสผ่าน</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                        </div>
                        <input type="password" id="password" name="password" autocomplete="new-password" required class="w-full pl-10 pr-10 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white focus:outline-none transition" placeholder="ระบุรหัสผ่าน">
                        <button type="button" onclick="togglePassVisibility()" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                        </button>
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full py-3 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-bold rounded-xl shadow-lg shadow-blue-600/30 transition text-sm cursor-pointer">
                        เข้าสู่ระบบ
                    </button>
                </div>
            </form>

            <div class="mt-5 pt-4 border-t border-slate-100 text-center text-xs text-slate-500">
                ยังไม่มีบัญชีผู้ใช้? <a href="register.php" class="text-blue-600 font-semibold hover:underline">สมัครสมาชิกที่นี่</a>
            </div>
        </div>
    </div>

    <script>
        function togglePassVisibility() {
            const passInput = document.getElementById('password');
            passInput.type = passInput.type === 'password' ? 'text' : 'password';
        }
    </script>
</body>
</html>
