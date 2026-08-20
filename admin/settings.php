<?php
// admin/settings.php - ตั้งค่าระบบ, ข้อมูลหน่วยงาน และ Google OAuth / LINE Login API
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/oauth_config.php';

requireLogin();

$pdo = getDBConnection();
$currentUser = getLoggedInUser();

// ตรวจสอบสิทธิ์ - อนุญาตเฉพาะ "ผู้ดูแลระบบสูงสุด" เท่านั้น
if ($currentUser['role'] !== 'ผู้ดูแลระบบสูงสุด') {
    die("<div style='font-family: sans-serif; padding: 25px; background: #fee2e2; border: 1px solid #ef4444; border-radius: 16px; max-width: 500px; margin: 50px auto; text-align: center;'>
        <h3 style='color: #b91c1c; margin-top: 0;'>⛔ ไม่มีสิทธิ์เข้าถึง</h3>
        <p style='color: #4b5563; font-size: 14px;'>เมนูตั้งค่าระบบอนุญาตให้เฉพาะ <b>ผู้ดูแลระบบสูงสุด</b> เข้าใช้งานได้เท่านั้น</p>
        <a href='../index.php' style='display: inline-block; margin-top: 15px; padding: 8px 18px; background: #2563eb; color: white; border-radius: 8px; text-decoration: none; font-size: 13px;'>กลับหน้าหลัก</a>
    </div>");
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['setting_type'] ?? 'org';

    if ($action === 'org') {
        $shortName = trim($_POST['org_short_name'] ?? 'สกร.');
        $orgName = trim($_POST['org_name'] ?? 'สำนักงานส่งเสริมการเรียนรู้');
        $orgSub = trim($_POST['org_sub'] ?? 'ประจำจังหวัด');
        
        setSystemSetting('org_short_name', $shortName);
        setSystemSetting('org_name', $orgName);
        setSystemSetting('org_sub', $orgSub);

        // อัปโหลดตราสัญลักษณ์หน่วยงาน
        if (isset($_FILES['org_logo_file']) && $_FILES['org_logo_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['org_logo_file'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['png', 'jpg', 'jpeg', 'svg', 'webp'];
            
            if (in_array($ext, $allowed)) {
                $newFileName = 'logo_org_' . time() . '.' . $ext;
                $destPath = __DIR__ . '/../assets/uploads/' . $newFileName;
                if (move_uploaded_file($file['tmp_name'], $destPath)) {
                    setSystemSetting('org_logo', 'assets/uploads/' . $newFileName);
                }
            } else {
                $error = 'ไฟล์รูปภาพต้องเป็นนามสกุล PNG, JPG, JPEG, SVG หรือ WEBP เท่านั้น';
            }
        }

        if (!$error) $message = 'บันทึกการตั้งค่าชื่อหน่วยงานและตราสัญลักษณ์เรียบร้อยแล้ว';

    } elseif ($action === 'oauth') {
        $oauthBaseUrl = rtrim(trim($_POST['oauth_base_url'] ?? 'http://localhost:8888'), '/');
        $googleClientId = trim($_POST['google_client_id'] ?? '');
        $googleClientSecret = trim($_POST['google_client_secret'] ?? '');
        $lineChannelId = trim($_POST['line_channel_id'] ?? '');
        $lineChannelSecret = trim($_POST['line_channel_secret'] ?? '');

        setSystemSetting('oauth_base_url', $oauthBaseUrl);
        setSystemSetting('google_client_id', $googleClientId);
        setSystemSetting('google_client_secret', $googleClientSecret);
        setSystemSetting('line_channel_id', $lineChannelId);
        setSystemSetting('line_channel_secret', $lineChannelSecret);

        $message = 'บันทึกการตั้งค่า Google OAuth และ LINE Login เรียบร้อยแล้ว พร้อมใช้งานจริงทันที!';
    }
}

$orgShortName = getSystemSetting('org_short_name', 'สกร.');
$orgName = getSystemSetting('org_name', 'สำนักงานส่งเสริมการเรียนรู้');
$orgSub = getSystemSetting('org_sub', 'ประจำจังหวัด');
$orgLogo = getSystemSetting('org_logo', 'assets/images/logo-sgr.png');

$googleClientId = getSystemSetting('google_client_id', '');
$googleClientSecret = getSystemSetting('google_client_secret', '');
$lineChannelId = getSystemSetting('line_channel_id', '');
$lineChannelSecret = getSystemSetting('line_channel_secret', '');

$googleRedirectUri = getGoogleRedirectUri();
$lineRedirectUri = getLineRedirectUri();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งค่าระบบ - ระบบสร้าง QR-Code คน <?= htmlspecialchars($orgShortName) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="../assets/css/style.css?v=<?= time() ?>" rel="stylesheet">
</head>
<body class="bg-slate-100/90 text-slate-800 antialiased min-h-screen flex flex-col lg:flex-row">

    <!-- Sidebar Navigation -->
    <aside class="hidden lg:flex flex-col w-64 sidebar-gradient text-white flex-shrink-0 relative overflow-hidden">
        <div class="p-5 flex items-center space-x-3 border-b border-white/10">
            <div class="w-12 h-12 rounded-xl bg-white/10 flex items-center justify-center p-1.5 shadow-inner">
                <img src="../<?= htmlspecialchars($orgLogo) ?>" alt="ตราสัญลักษณ์" class="max-h-full object-contain">
            </div>
            <div>
                <h1 class="text-xl font-bold tracking-tight leading-none text-white"><?= htmlspecialchars($orgShortName) ?></h1>
                <p class="text-[11px] text-blue-100 font-light mt-1 leading-snug"><?= htmlspecialchars($orgName) ?><br><?= htmlspecialchars($orgSub) ?></p>
            </div>
        </div>

        <nav class="flex-1 px-3 py-5 space-y-1.5 text-sm font-medium">
            <a href="../index.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-blue-100 hover:bg-white/10 transition">
                <svg class="w-5 h-5 opacity-80" fill="currentColor" viewBox="0 0 20 20"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path></svg>
                <span>หน้าแรก (สร้าง QR)</span>
            </a>
            <a href="history.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-blue-100 hover:bg-white/10 transition">
                <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>ประวัติการสร้าง</span>
            </a>
            <a href="users.php" class="flex items-center justify-between px-4 py-3 rounded-xl text-blue-100 hover:bg-white/10 transition">
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    <span>จัดการผู้ใช้งาน</span>
                </div>
            </a>
            <a href="settings.php" class="flex items-center justify-between px-4 py-3 rounded-xl bg-white/20 text-white font-semibold shadow-sm transition">
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path></svg>
                    <span>ตั้งค่าระบบ</span>
                </div>
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col min-w-0 overflow-y-auto p-4 md:p-8">
        <div class="max-w-4xl mx-auto w-full space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-slate-800 font-heading">ตั้งค่าระบบ & Social Login</h2>
                    <p class="text-xs text-slate-500 mt-1">กำหนดชื่อหน่วยงาน ตราสัญลักษณ์ และการเชื่อมต่อ Google OAuth / LINE Developers</p>
                </div>
                <a href="../index.php" class="inline-flex items-center space-x-1 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl text-xs transition">
                    <span>← กลับหน้าสร้าง QR</span>
                </a>
            </div>

            <?php if ($message): ?>
                <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-2xl text-sm font-semibold flex items-center space-x-2">
                    <span>✓</span><span><?= htmlspecialchars($message) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="p-4 bg-rose-50 border border-rose-200 text-rose-700 rounded-2xl text-sm font-semibold flex items-center space-x-2">
                    <span>✕</span><span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <!-- TAB 1: ข้อมูลหน่วยงาน & โลโก้ -->
            <div class="bg-white rounded-3xl shadow-sm border border-slate-200/80 p-6 md:p-8">
                <div class="flex items-center space-x-2 pb-3 mb-5 border-b border-slate-100">
                    <span class="text-lg">🏛️</span>
                    <h3 class="font-bold text-slate-800 text-base">ข้อมูลหน่วยงาน & ตราสัญลักษณ์</h3>
                </div>

                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="setting_type" value="org">
                    
                    <div class="flex items-center space-x-5 pb-4 border-b border-slate-100">
                        <div class="w-16 h-16 rounded-2xl bg-slate-50 border-2 border-slate-200 p-2 flex items-center justify-center shadow-inner">
                            <img id="logo_preview" src="../<?= htmlspecialchars($orgLogo) ?>" class="max-h-full object-contain">
                        </div>
                        <div class="flex-1">
                            <label class="block text-xs font-bold text-slate-700 mb-1">ตราสัญลักษณ์ประจำหน่วยงาน (Logo)</label>
                            <label class="inline-flex items-center space-x-1.5 px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-xl text-xs font-semibold cursor-pointer border border-blue-200 transition">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                <span>📤 เลือกรูปตราสัญลักษณ์ใหม่</span>
                                <input type="file" name="org_logo_file" accept="image/*" class="hidden" onchange="previewLogo(this)">
                            </label>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">ชื่อย่อหน่วยงาน</label>
                            <input type="text" name="org_short_name" value="<?= htmlspecialchars($orgShortName) ?>" required class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white focus:outline-none transition font-semibold">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">ชื่อเต็มหน่วยงาน</label>
                            <input type="text" name="org_name" value="<?= htmlspecialchars($orgName) ?>" required class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white focus:outline-none transition">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">สังกัด / ข้อความบรรทัดสอง</label>
                            <input type="text" name="org_sub" value="<?= htmlspecialchars($orgSub) ?>" required class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white focus:outline-none transition">
                        </div>
                    </div>

                    <div class="pt-2 flex justify-end">
                        <button type="submit" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-xs shadow-md transition">
                            💾 บันทึกข้อมูลหน่วยงาน
                        </button>
                    </div>
                </form>
            </div>

            <!-- TAB 2: ตั้งค่า Google OAuth & LINE Developers API (ใช้งานได้จริง) -->
            <div id="oauth_section" class="bg-white rounded-3xl shadow-sm border border-slate-200/80 p-6 md:p-8">
                <div class="flex items-center space-x-2 pb-3 mb-5 border-b border-slate-100">
                    <span class="text-lg">🔐</span>
                    <h3 class="font-bold text-slate-800 text-base">ตั้งค่า Google OAuth 2.0 & LINE Developers Login (ใช้งานจริง)</h3>
                </div>

                <form method="POST" class="space-y-6">
                    <input type="hidden" name="setting_type" value="oauth">

                    <!-- Base URL Config Box -->
                    <div class="p-5 bg-orange-50 rounded-2xl border border-orange-200 space-y-3">
                        <div class="flex items-center space-x-2">
                            <span class="font-bold text-orange-800 text-sm">🌍 Base URL ของระบบ (ใช้สำหรับ Redirect URI)</span>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-orange-700 mb-1">
                                โดเมนปัจจุบันของระบบคุณ (ถ้าใช้ localhost กรุณาระบุ Port ด้วย เช่น http://localhost:8888)
                            </label>
                            <input type="text" name="oauth_base_url" value="<?= htmlspecialchars(getSystemSetting('oauth_base_url', 'http://localhost:8888')) ?>" required class="w-full px-3 py-2 text-xs bg-white border border-orange-300 rounded-xl focus:ring-2 focus:ring-orange-500 font-mono text-slate-800">
                        </div>
                    </div>

                    <!-- Google OAuth Config Box -->
                    <div class="p-5 bg-slate-50 rounded-2xl border border-slate-200 space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <svg class="w-5 h-5" viewBox="0 0 24 24">
                                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                                </svg>
                                <span class="font-bold text-slate-800 text-sm">Google Cloud Console OAuth 2.0 Client</span>
                            </div>
                            <a href="https://console.cloud.google.com/apis/credentials" target="_blank" class="text-xs text-blue-600 hover:underline font-semibold">เปิด Google Cloud Console ↗</a>
                        </div>

                        <!-- Authorized Redirect URI -->
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">
                                Authorized Redirect URI (นำ URL นี้ไปใส่ใน Google Cloud Console):
                            </label>
                            <div class="flex items-center gap-1.5">
                                <input type="text" readonly id="googleUriBox" value="<?= htmlspecialchars($googleRedirectUri) ?>" class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-xl font-mono text-xs text-slate-700 select-all">
                                <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('googleUriBox').value); alert('คัดลอก Google Redirect URI แล้ว!');" class="px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 rounded-xl text-xs font-semibold flex-shrink-0 cursor-pointer">
                                    คัดลอก
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Google Client ID</label>
                                <input type="text" name="google_client_id" value="<?= htmlspecialchars($googleClientId) ?>" placeholder="xxxxxx.apps.googleusercontent.com" class="w-full px-3 py-2 text-xs bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 font-mono">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Google Client Secret</label>
                                <input type="password" name="google_client_secret" value="<?= htmlspecialchars($googleClientSecret) ?>" placeholder="GOCSPX-xxxxxx" class="w-full px-3 py-2 text-xs bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 font-mono">
                            </div>
                        </div>
                    </div>

                    <!-- LINE Login Config Box -->
                    <div class="p-5 bg-emerald-50/60 rounded-2xl border border-emerald-200 space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <img src="../assets/images/icons/icon-line.svg" class="w-5 h-5">
                                <span class="font-bold text-emerald-900 text-sm">LINE Developers Console (LINE Login 2.1)</span>
                            </div>
                            <a href="https://developers.line.biz/console/" target="_blank" class="text-xs text-emerald-700 hover:underline font-semibold">เปิด LINE Developers Console ↗</a>
                        </div>

                        <!-- Callback URL -->
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">
                                Callback URL (นำ URL นี้ไปใส่ใน LINE Developers Console ในแท็บ LINE Login):
                            </label>
                            <div class="flex items-center gap-1.5">
                                <input type="text" readonly id="lineUriBox" value="<?= htmlspecialchars($lineRedirectUri) ?>" class="w-full px-3 py-1.5 bg-white border border-emerald-300 rounded-xl font-mono text-xs text-slate-700 select-all">
                                <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('lineUriBox').value); alert('คัดลอก LINE Callback URL แล้ว!');" class="px-3 py-1.5 bg-emerald-100 hover:bg-emerald-200 text-emerald-800 border border-emerald-300 rounded-xl text-xs font-semibold flex-shrink-0 cursor-pointer">
                                    คัดลอก
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">LINE Channel ID</label>
                                <input type="text" name="line_channel_id" value="<?= htmlspecialchars($lineChannelId) ?>" placeholder="200xxxxxxx" class="w-full px-3 py-2 text-xs bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 font-mono">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">LINE Channel Secret</label>
                                <input type="password" name="line_channel_secret" value="<?= htmlspecialchars($lineChannelSecret) ?>" placeholder="xxxxxx" class="w-full px-3 py-2 text-xs bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 font-mono">
                            </div>
                        </div>
                    </div>

                    <div class="pt-2 flex justify-end">
                        <button type="submit" class="px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-md shadow-emerald-600/20 transition cursor-pointer">
                            💾 บันทึกการตั้งค่า Google & LINE Login
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        function previewLogo(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('logo_preview').src = e.target.result;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>
