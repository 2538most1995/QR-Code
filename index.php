<?php
// index.php - ระบบสร้าง QR-Code คน สกร. (Responsive ทุกขนาดหน้าจอ มือถือ แท็บเล็ต iPad คอมพิวเตอร์)
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

requireLogin();

$pdo = getDBConnection();
$currentUser = getLoggedInUser();

// ดึงการตั้งค่าหน่วยงาน
$orgShortName = getSystemSetting('org_short_name', 'สกร.');
$orgName = getSystemSetting('org_name', 'สำนักงานส่งเสริมการเรียนรู้');
$orgSub = getSystemSetting('org_sub', 'ประจำจังหวัด');
$orgLogo = getSystemSetting('org_logo', 'assets/images/logo-sgr.png');

// ดึงรายการล่าสุด 10 รายการ
$stmt = $pdo->query("SELECT * FROM qr_items ORDER BY id DESC LIMIT 10");
$recentList = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>ระบบสร้าง QR-Code คน <?= htmlspecialchars($orgShortName) ?> - <?= htmlspecialchars($orgName) ?></title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="assets/css/style.css?v=<?= time() ?>" rel="stylesheet">
    <!-- Standalone True ISO QR Code Generator (Offline Bundled) -->
    <script src="assets/js/qrcode.min.js?v=<?= time() ?>"></script>
    <script>
        window.DEFAULT_ORG_LOGO = '<?= htmlspecialchars($orgLogo) ?>';
        window.ORG_SHORT_NAME = '<?= htmlspecialchars($orgShortName) ?>';
        window.ORG_NAME = '<?= htmlspecialchars($orgName) ?>';
        window.ORG_SUB = '<?= htmlspecialchars($orgSub) ?>';

        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        sgr: {
                            50: '#f0f7ff',
                            100: '#e0effe',
                            500: '#0b57d0',
                            600: '#0052cc',
                            700: '#0041a8',
                            800: '#003387',
                            900: '#002566',
                        }
                    },
                    screens: {
                        'xs': '480px',
                        'sm': '640px',
                        'md': '768px',
                        'lg': '1024px',
                        'xl': '1280px',
                        '2xl': '1536px',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-slate-100/90 text-slate-800 antialiased min-h-screen flex flex-col lg:flex-row">

    <!-- Mobile/Tablet Top Navigation Bar -->
    <div class="lg:hidden bg-sgr-600 text-white p-3.5 sm:p-4 flex items-center justify-between sticky top-0 z-50 shadow-md">
        <div class="flex items-center space-x-3">
            <img src="<?= htmlspecialchars($orgLogo) ?>" alt="ตราสัญลักษณ์" class="w-9 h-9 object-contain bg-white/10 rounded-full p-1">
            <div>
                <div class="font-bold text-base leading-tight"><?= htmlspecialchars($orgShortName) ?></div>
                <div class="text-[10px] text-blue-100 leading-tight"><?= htmlspecialchars($orgName) ?></div>
            </div>
        </div>
        <button id="mobileMenuToggle" class="p-2 rounded-xl bg-white/10 hover:bg-white/20 text-white focus:outline-none transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>
    </div>

    <!-- Mobile Drawer Backdrop Overlay -->
    <div id="mobileBackdrop" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-40 lg:hidden transition-opacity"></div>

    <!-- Sidebar Navigation (Desktop Fixed + Mobile Slide-over Drawer) -->
    <aside id="sidebar" class="hidden lg:flex flex-col w-64 sidebar-gradient text-white flex-shrink-0 relative overflow-hidden transition-all duration-300 z-50">
        <!-- Logo Section -->
        <div class="p-5 flex items-center space-x-3 border-b border-white/10">
            <div class="w-12 h-12 rounded-xl bg-white/10 flex items-center justify-center p-1.5 shadow-inner flex-shrink-0">
                <img src="<?= htmlspecialchars($orgLogo) ?>" alt="ตราสัญลักษณ์" class="max-h-full object-contain">
            </div>
            <div class="min-w-0">
                <h1 class="text-xl font-bold tracking-tight leading-none text-white truncate"><?= htmlspecialchars($orgShortName) ?></h1>
                <p class="text-[11px] text-blue-100 font-light mt-1 leading-snug truncate"><?= htmlspecialchars($orgName) ?><br><?= htmlspecialchars($orgSub) ?></p>
            </div>
        </div>

        <!-- Navigation Links -->
        <nav class="flex-1 px-3 py-5 space-y-1.5 text-sm font-medium z-10">
            <a href="index.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl bg-white/20 text-white font-semibold shadow-sm transition">
                <svg class="w-5 h-5 text-white flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                </svg>
                <span>หน้าแรก (สร้าง QR)</span>
            </a>

            <a href="admin/history.php" class="flex items-center justify-between px-4 py-3 rounded-xl text-blue-100 hover:bg-white/10 hover:text-white transition">
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5 opacity-80 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>ประวัติการสร้าง</span>
                </div>
                <svg class="w-4 h-4 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            </a>

            <?php if (in_array($currentUser['role'], ['ผู้ดูแลระบบสูงสุด', 'ผู้ดูแลระบบ'])): ?>
            <a href="admin/users.php" class="flex items-center justify-between px-4 py-3 rounded-xl text-blue-100 hover:bg-white/10 hover:text-white transition">
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5 opacity-80 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    <span>จัดการผู้ใช้งาน</span>
                </div>
                <svg class="w-4 h-4 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            </a>
            <?php endif; ?>

            <?php if ($currentUser['role'] === 'ผู้ดูแลระบบสูงสุด'): ?>
            <a href="admin/settings.php" class="flex items-center justify-between px-4 py-3 rounded-xl text-blue-100 hover:bg-white/10 hover:text-white transition">
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5 opacity-80 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <span>ตั้งค่าหน่วยงาน</span>
                </div>
                <svg class="w-4 h-4 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            </a>
            <?php endif; ?>
        </nav>

        <!-- Sidebar Watermark & Version -->
        <div class="p-4 relative mt-auto text-center z-10">
            <div class="inline-block px-3 py-1 bg-white/10 rounded-full text-[11px] text-blue-200 backdrop-blur-sm">
                เวอร์ชัน 1.2.0 (Responsive)
            </div>
        </div>

        <div class="absolute -bottom-6 -left-6 opacity-20 pointer-events-none z-0">
            <img src="assets/images/sidebar-watermark.png" alt="watermark" class="w-48 object-contain">
        </div>
    </aside>

    <!-- Main Content Container -->
    <main class="flex-1 flex flex-col min-w-0 overflow-y-auto">

        <!-- Top Header Bar -->
        <header class="bg-white border-b border-slate-200 px-4 sm:px-6 py-4 flex flex-wrap items-center justify-between gap-3 shadow-sm">
            <div class="min-w-0">
                <h2 class="text-xl sm:text-2xl font-bold text-slate-800 font-heading truncate">ระบบสร้าง QR-Code คน <?= htmlspecialchars($orgShortName) ?></h2>
                <div class="flex items-center space-x-1.5 text-blue-600 text-xs sm:text-sm font-medium mt-0.5 truncate">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                    <span class="truncate"><?= htmlspecialchars($orgName) ?> <?= htmlspecialchars($orgSub) ?></span>
                </div>
            </div>

            <!-- Header Right Section -->
            <div class="flex items-center space-x-3 sm:space-x-4">
                <a href="admin/settings.php" title="ตั้งค่าชื่อหน่วยงาน & โลโก้" class="hidden sm:inline-flex items-center space-x-1.5 px-3 py-2 text-slate-600 hover:text-blue-600 hover:bg-blue-50 rounded-xl border border-slate-200 transition text-xs font-semibold">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path></svg>
                    <span>ตั้งค่าหน่วยงาน</span>
                </a>

                <div class="flex items-center space-x-2.5 sm:space-x-3 pl-2 sm:pl-3 border-l border-slate-200">
                    <img src="<?= (strpos($currentUser['avatar'], 'http') === 0) ? htmlspecialchars($currentUser['avatar']) : htmlspecialchars($currentUser['avatar']) ?>" alt="Avatar" class="w-9 h-9 sm:w-10 sm:h-10 rounded-full object-cover border-2 border-blue-500 shadow-sm flex-shrink-0">
                    <div class="hidden md:block text-left">
                        <div class="text-sm font-semibold text-slate-800 leading-tight"><?= htmlspecialchars($currentUser['fullname']) ?></div>
                        <div class="text-[11px] text-slate-500"><?= htmlspecialchars($currentUser['role']) ?></div>
                    </div>
                    <a href="admin/logout.php" title="ออกจากระบบ" class="text-slate-400 hover:text-rose-600 transition p-1.5 hover:bg-rose-50 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                    </a>
                </div>
            </div>
        </header>

        <!-- Main Dashboard Body -->
        <div class="p-3.5 sm:p-5 md:p-6 space-y-6 max-w-[1600px] mx-auto w-full">

            <!-- Edit Notice Banner (shown when editing an item) -->
            <div id="edit_notice_banner" class="hidden bg-amber-50 border border-amber-200 rounded-2xl p-4 flex flex-wrap items-center justify-between gap-2 shadow-sm">
                <div class="flex items-center space-x-2.5 text-amber-800 text-sm font-medium">
                    <span class="text-base">✏️</span>
                    <span>กำลังอยู่ในโหมดแก้ไข QR-Code: <strong id="edit_item_title" class="font-bold text-amber-900 underline"></strong></span>
                </div>
                <button type="button" onclick="cancelEditMode()" class="px-3.5 py-1.5 bg-white hover:bg-amber-100 text-amber-800 border border-amber-300 rounded-xl text-xs font-semibold transition cursor-pointer">
                    ✕ ยกเลิกการแก้ไข
                </button>
            </div>

            <!-- Responsive 3-Card Grid (1 col on mobile, 2 col on tablet/iPad, 3 col on large desktop) -->
            <form id="qrForm" onsubmit="event.preventDefault(); submitCreateForm();" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5 items-stretch">
                
                <!-- CARD 1: ข้อมูล QR-Code -->
                <div class="bg-white rounded-3xl p-5 sm:p-6 shadow-sm border border-slate-200/80 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center space-x-2 text-slate-800 font-bold text-base sm:text-lg mb-4 pb-3 border-b border-slate-100">
                            <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs sm:text-sm">1</div>
                            <span>ข้อมูล QR-Code</span>
                        </div>

                        <div class="space-y-4">
                            <!-- Field 1: ชื่อ QR-Code -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    ชื่อ QR-Code / หัวข้อ <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                                    </div>
                                    <input type="text" id="title" name="title" value="แผนม.ต้น ภาคเรียนที่ 1 ปีการศึกษา 2569" placeholder="ระบุชื่อ QR Code เช่น แผนการสอน, แบบสอบถาม" required class="w-full pl-10 pr-4 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white focus:outline-none transition font-medium">
                                </div>
                            </div>

                            <!-- Field 2: แนบลิงก์ URL -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    แนบลิงก์ (URL ที่จะให้เปิดเมื่อสแกน) <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-blue-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                                    </div>
                                    <input type="text" id="target_url" name="target_url" value="https://drive.google.com/file/d/sample" placeholder="https://drive.google.com/... หรือ https://..." required class="w-full pl-10 pr-4 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white focus:outline-none transition font-mono text-xs sm:text-sm">
                                </div>
                                <p class="text-[11px] text-slate-400 mt-1">รองรับทุกลิงก์ เช่น เว็บไซต์, Google Drive, Google Form, Canva, โซเชียล ฯลฯ</p>
                            </div>

                            <!-- Field 3: หมวดหมู่ -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">หมวดหมู่ / ประเภท</label>
                                <select id="category" name="category" class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white focus:outline-none transition">
                                    <option value="เอกสารเผยแพร่ / Drive" selected>📁 เอกสารเผยแพร่ / Google Drive / PDF</option>
                                    <option value="แบบฟอร์ม / Google Form">📝 แบบฟอร์ม / Google Form / แบบประเมิน</option>
                                    <option value="เว็บไซต์หน่วยงาน">🌐 เว็บไซต์หน่วยงาน / Portal</option>
                                    <option value="การอบรม / กิจกรรม">🎓 การอบรม / โครงการ / กิจกรรม</option>
                                    <option value="โซเชียลมีเดีย / Line">💬 โซเชียลมีเดีย / LINE Official / Facebook</option>
                                    <option value="ลิงก์ทั่วไป">🔗 ลิงก์ทั่วไป</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CARD 2: ปรับแต่งโทนสี & รูปตรงกลาง -->
                <div class="bg-white rounded-3xl p-5 sm:p-6 shadow-sm border border-slate-200/80 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center space-x-2 text-slate-800 font-bold text-base sm:text-lg mb-4 pb-3 border-b border-slate-100">
                            <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs sm:text-sm">2</div>
                            <span>ปรับแต่งโทนสี & รูปตรงกลาง</span>
                        </div>

                        <div class="space-y-4">
                            <!-- 1. โทนสียอดนิยม (12 เฉดสี + หลอดดูดสี) -->
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <label class="text-xs font-semibold text-slate-700">โทนสี QR Code</label>
                                    <button type="button" onclick="pickColorWithEyedropper()" class="inline-flex items-center space-x-1 px-2.5 py-1 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg text-xs font-semibold border border-blue-200 transition" title="คลิกเพื่อดูดสีจากหน้าจอ">
                                        <span>🎨 หลอดดูดสี</span>
                                    </button>
                                </div>

                                <input type="hidden" id="qr_color" name="qr_color" value="#0284c7">
                                <input type="hidden" id="qr_style" name="qr_style" value="blue">

                                <!-- 12 Color Preset Palette Circles -->
                                <div class="grid grid-cols-6 gap-2 mb-2.5">
                                    <button type="button" data-color="#0284c7" class="qr-color-btn w-7 h-7 rounded-full bg-[#0284c7] flex items-center justify-center ring-2 ring-offset-2 ring-blue-600 scale-110 shadow-sm transition" title="น้ำเงิน สกร.">
                                        <svg class="check-icon w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                    </button>
                                    <button type="button" data-color="#1e40af" class="qr-color-btn w-7 h-7 rounded-full bg-[#1e40af] flex items-center justify-center shadow-sm transition" title="ฟ้าครามเข้ม">
                                        <svg class="check-icon hidden w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                    </button>
                                    <button type="button" data-color="#059669" class="qr-color-btn w-7 h-7 rounded-full bg-[#059669] flex items-center justify-center shadow-sm transition" title="เขียวมรกต">
                                        <svg class="check-icon hidden w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                    </button>
                                    <button type="button" data-color="#16a34a" class="qr-color-btn w-7 h-7 rounded-full bg-[#16a34a] flex items-center justify-center shadow-sm transition" title="เขียวใบไม้">
                                        <svg class="check-icon hidden w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                    </button>
                                    <button type="button" data-color="#7c3aed" class="qr-color-btn w-7 h-7 rounded-full bg-[#7c3aed] flex items-center justify-center shadow-sm transition" title="ม่วงรอยัล">
                                        <svg class="check-icon hidden w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                    </button>
                                    <button type="button" data-color="#9333ea" class="qr-color-btn w-7 h-7 rounded-full bg-[#9333ea] flex items-center justify-center shadow-sm transition" title="ม่วงพาสเทล">
                                        <svg class="check-icon hidden w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                    </button>
                                    <button type="button" data-color="#ea580c" class="qr-color-btn w-7 h-7 rounded-full bg-[#ea580c] flex items-center justify-center shadow-sm transition" title="ส้มสดใส">
                                        <svg class="check-icon hidden w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                    </button>
                                    <button type="button" data-color="#dc2626" class="qr-color-btn w-7 h-7 rounded-full bg-[#dc2626] flex items-center justify-center shadow-sm transition" title="แดงทับทิม">
                                        <svg class="check-icon hidden w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                    </button>
                                    <button type="button" data-color="#db2777" class="qr-color-btn w-7 h-7 rounded-full bg-[#db2777] flex items-center justify-center shadow-sm transition" title="ชมพูบานเย็น">
                                        <svg class="check-icon hidden w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                    </button>
                                    <button type="button" data-color="#b45309" class="qr-color-btn w-7 h-7 rounded-full bg-[#b45309] flex items-center justify-center shadow-sm transition" title="น้ำตาลทอง">
                                        <svg class="check-icon hidden w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                    </button>
                                    <button type="button" data-color="#1e293b" class="qr-color-btn w-7 h-7 rounded-full bg-[#1e293b] flex items-center justify-center shadow-sm transition" title="เทาดำพรีเมียม">
                                        <svg class="check-icon hidden w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                    </button>
                                    <button type="button" data-color="#000000" class="qr-color-btn w-7 h-7 rounded-full bg-[#000000] flex items-center justify-center shadow-sm transition" title="ดำสนิท">
                                        <svg class="check-icon hidden w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                    </button>
                                </div>

                                <!-- Custom Color Picker & HEX Input -->
                                <div class="flex items-center space-x-2 bg-slate-50 p-2 rounded-xl border border-slate-200">
                                    <input type="color" id="custom_color_picker" value="#0284c7" class="w-8 h-8 rounded-lg border-0 cursor-pointer p-0 bg-transparent" title="เลือกสีอิสระ">
                                    <div class="flex-1 text-xs">
                                        <span class="text-slate-400 text-[10px] block">รหัสสี HEX:</span>
                                        <input type="text" id="hex_color_input" value="#0284C7" class="w-full bg-white border border-slate-200 rounded px-2 py-0.5 font-mono text-xs font-bold text-slate-700 uppercase focus:outline-none">
                                    </div>
                                </div>
                            </div>

                            <!-- 2. เลือกโลโก้ / ไอคอนกึ่งกลาง -->
                            <div class="pt-2 border-t border-slate-100">
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    รูปภาพ / ไอคอนกึ่งกลาง QR
                                </label>
                                <div class="grid grid-cols-4 gap-1.5 sm:gap-2">
                                    <!-- ตราประจำหน่วยงาน (Dynamic) -->
                                    <button type="button" data-logo="<?= htmlspecialchars($orgLogo) ?>" class="logo-preset-btn flex flex-col items-center justify-center p-1.5 rounded-xl border border-blue-600 bg-blue-50 ring-2 ring-blue-600 transition" title="ตรา <?= htmlspecialchars($orgShortName) ?>">
                                        <img src="<?= htmlspecialchars($orgLogo) ?>" class="w-5 h-5 object-contain">
                                        <span class="text-[9px] font-medium text-slate-700 mt-1 truncate max-w-full px-1">ตรา <?= htmlspecialchars($orgShortName) ?></span>
                                    </button>
                                    <button type="button" data-logo="assets/images/icons/icon-drive.svg" class="logo-preset-btn flex flex-col items-center justify-center p-1.5 rounded-xl border border-slate-200 bg-white transition" title="Drive">
                                        <img src="assets/images/icons/icon-drive.svg" class="w-5 h-5 object-contain">
                                        <span class="text-[9px] font-medium text-slate-700 mt-1">Drive</span>
                                    </button>
                                    <button type="button" data-logo="assets/images/icons/icon-form.svg" class="logo-preset-btn flex flex-col items-center justify-center p-1.5 rounded-xl border border-slate-200 bg-white transition" title="ฟอร์ม">
                                        <img src="assets/images/icons/icon-form.svg" class="w-5 h-5 object-contain">
                                        <span class="text-[9px] font-medium text-slate-700 mt-1">ฟอร์ม</span>
                                    </button>
                                    <button type="button" data-logo="assets/images/icons/icon-web.svg" class="logo-preset-btn flex flex-col items-center justify-center p-1.5 rounded-xl border border-slate-200 bg-white transition" title="เว็บ">
                                        <img src="assets/images/icons/icon-web.svg" class="w-5 h-5 object-contain">
                                        <span class="text-[9px] font-medium text-slate-700 mt-1">เว็บ</span>
                                    </button>
                                    <button type="button" data-logo="assets/images/icons/icon-line.svg" class="logo-preset-btn flex flex-col items-center justify-center p-1.5 rounded-xl border border-slate-200 bg-white transition" title="LINE">
                                        <img src="assets/images/icons/icon-line.svg" class="w-5 h-5 object-contain">
                                        <span class="text-[9px] font-medium text-slate-700 mt-1">LINE</span>
                                    </button>
                                    <button type="button" data-logo="assets/images/icons/icon-facebook.svg" class="logo-preset-btn flex flex-col items-center justify-center p-1.5 rounded-xl border border-slate-200 bg-white transition" title="Facebook">
                                        <img src="assets/images/icons/icon-facebook.svg" class="w-5 h-5 object-contain">
                                        <span class="text-[9px] font-medium text-slate-700 mt-1">Facebook</span>
                                    </button>
                                    <button type="button" data-logo="assets/images/icons/icon-doc.svg" class="logo-preset-btn flex flex-col items-center justify-center p-1.5 rounded-xl border border-slate-200 bg-white transition" title="เอกสาร">
                                        <img src="assets/images/icons/icon-doc.svg" class="w-5 h-5 object-contain">
                                        <span class="text-[9px] font-medium text-slate-700 mt-1">เอกสาร</span>
                                    </button>
                                    <button type="button" data-logo="none" class="logo-preset-btn flex flex-col items-center justify-center p-1.5 rounded-xl border border-slate-200 bg-white transition" title="ไม่มีรูป">
                                        <span class="w-5 h-5 rounded-full border border-dashed border-slate-400 flex items-center justify-center text-slate-400 text-xs">✕</span>
                                        <span class="text-[9px] font-medium text-slate-700 mt-1">ไม่มีรูป</span>
                                    </button>
                                </div>

                                <!-- Custom Image Upload Button -->
                                <div class="mt-2.5 flex items-center gap-2">
                                    <label class="flex-1 inline-flex items-center justify-center space-x-1.5 px-3 py-1.5 bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-xl text-xs font-semibold cursor-pointer transition">
                                        <svg class="w-4 h-4 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                        <span>📤 อัปโหลดรูปเอง</span>
                                        <input type="file" id="custom_logo_input" accept="image/*" class="hidden">
                                    </label>
                                    <div id="custom_logo_preview_wrap" class="hidden flex items-center space-x-1 px-2 py-1 bg-blue-50 border border-blue-200 rounded-xl">
                                        <img id="custom_logo_thumb" src="" class="w-5 h-5 rounded-full object-cover border border-white">
                                        <button type="button" onclick="removeCustomLogo()" class="text-xs text-rose-500 hover:text-rose-700 font-bold ml-1">✕</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CARD 3: ตัวอย่าง QR-Code & แผงดาวน์โหลดและสั่งพิมพ์ (Spans 2 cols on Tablet, 1 col on Large Desktop) -->
                <div class="md:col-span-2 xl:col-span-1 bg-white rounded-3xl p-5 sm:p-6 shadow-sm border border-slate-200/80 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                            <div class="flex items-center space-x-2 text-slate-800 font-bold text-base sm:text-lg">
                                <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs sm:text-sm">3</div>
                                <span>ตัวอย่าง & ดาวน์โหลด</span>
                            </div>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                ✓ สแกนติด 100%
                            </span>
                        </div>

                        <!-- Top Half: Preview QR & Live Info -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 items-center mb-4">
                            <!-- Live QR Code Canvas Box -->
                            <div class="flex flex-col items-center">
                                <div id="qrcode_container" class="w-36 h-36 bg-white p-2 border-2 border-slate-100 rounded-2xl shadow-sm flex items-center justify-center">
                                    <!-- Canvas rendered by JS -->
                                </div>
                                <div class="mt-2 inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <span>🛡️ ใช้งานถาวร</span>
                                </div>
                            </div>

                            <!-- Live Link Summary Preview -->
                            <div class="bg-slate-50 border border-slate-200/90 rounded-2xl p-3.5 text-center flex flex-col justify-between shadow-sm min-h-[140px]">
                                <div>
                                    <span id="preview_category" class="inline-block px-2 py-0.5 bg-blue-100 text-blue-700 text-[10px] font-semibold rounded-full mb-1.5">
                                        เอกสารเผยแพร่ / Drive
                                    </span>
                                    <h4 id="preview_title" class="font-bold text-xs sm:text-sm text-slate-800 leading-snug break-words">
                                        แผนม.ต้น ภาคเรียนที่ 1 ปีการศึกษา 2569
                                    </h4>
                                </div>

                                <div class="mt-2 pt-2 border-t border-slate-200 text-left">
                                    <div class="text-[9px] text-slate-400 font-semibold uppercase">ลิงก์ปลายทาง:</div>
                                    <a id="preview_url" href="https://drive.google.com/file/d/sample" target="_blank" class="text-[11px] text-blue-600 hover:underline font-mono truncate block mt-0.5">
                                        https://drive.google.com/file/d/sample
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Bottom Half Inside Card 3: Download & Action Controls -->
                        <div class="bg-slate-50 rounded-2xl p-3.5 sm:p-4 border border-slate-200 space-y-3">
                            
                            <!-- Selectors: Format & Size -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1 text-[11px]">รูปแบบไฟล์:</label>
                                    <select id="download_format_select" class="w-full bg-white border border-slate-200 rounded-xl px-2.5 py-2 font-medium text-slate-800 focus:outline-none text-xs">
                                        <option value="png" selected>PNG (คมชัด/โปร่งใส)</option>
                                        <option value="jpg">JPG / JPEG (ภาพถ่าย)</option>
                                        <option value="svg">SVG (เวกเตอร์)</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1 text-[11px]">ขนาดภาพ:</label>
                                    <select id="download_size_select" class="w-full bg-white border border-slate-200 rounded-xl px-2.5 py-2 font-medium text-slate-800 focus:outline-none text-xs">
                                        <option value="500">500 x 500 px (เล็ก)</option>
                                        <option value="1000" selected>1,000 x 1,000 px (คมชัดสูง)</option>
                                        <option value="2000">2,000 x 2,000 px (ใหญ่พิเศษ)</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Big Download Button -->
                            <button type="button" onclick="executeDownload()" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-bold rounded-xl shadow-md shadow-emerald-600/20 transition text-xs flex items-center justify-center space-x-2 cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                <span>ดาวน์โหลดรูป QR-Code ทันที</span>
                            </button>

                            <!-- Create & Secondary Action Buttons -->
                            <div class="grid grid-cols-2 gap-2 pt-1 border-t border-slate-200">
                                <!-- Create or Save in Database -->
                                <button type="button" onclick="submitCreateForm()" id="btn_submit_create" class="col-span-2 py-2.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-bold rounded-xl shadow-md shadow-blue-600/20 transition text-xs flex items-center justify-center space-x-1.5 cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                    <span>สร้าง & บันทึกลงระบบ</span>
                                </button>
                                
                                <button type="button" id="btn_cancel_edit" onclick="cancelEditMode()" class="hidden col-span-2 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold rounded-xl transition text-xs flex items-center justify-center space-x-1 cursor-pointer">
                                    <span>✕ ยกเลิกการแก้ไข</span>
                                </button>

                                <!-- Print -->
                                <button type="button" onclick="printCard()" class="py-2 bg-white hover:bg-slate-100 text-slate-700 font-semibold rounded-xl border border-slate-200 transition text-[11px] flex items-center justify-center space-x-1 cursor-pointer">
                                    <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                    <span>พิมพ์เอกสาร</span>
                                </button>

                                <!-- Share -->
                                <button type="button" onclick="shareLink()" class="py-2 bg-white hover:bg-slate-100 text-slate-700 font-semibold rounded-xl border border-slate-200 transition text-[11px] flex items-center justify-center space-x-1 cursor-pointer">
                                    <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path></svg>
                                    <span>แชร์ลิงก์</span>
                                </button>
                            </div>

                        </div>
                    </div>
                </div>

            </form>

            <!-- BOTTOM SECTION: รายการล่าสุด Table -->
            <div class="bg-white rounded-3xl shadow-sm border border-slate-200/80 overflow-hidden">
                <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center space-x-2">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <h3 class="font-bold text-slate-800 text-sm sm:text-base">รายการ QR-Code ล่าสุด (กดแก้ไขหรือดาวน์โหลดได้ทันที)</h3>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[640px]">
                        <thead>
                            <tr class="bg-slate-50/70 border-b border-slate-100 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                                <th class="py-3 px-4">ชื่อ QR-Code / ลิงก์ปลายทาง</th>
                                <th class="py-3 px-4">ประเภท</th>
                                <th class="py-3 px-4">วันที่สร้าง</th>
                                <th class="py-3 px-4">สร้างโดย</th>
                                <th class="py-3 px-4">สถานะ</th>
                                <th class="py-3 px-4 text-center">การจัดการ</th>
                            </tr>
                        </thead>
                        <tbody id="recent_tbody" class="divide-y divide-slate-100 text-sm">
                            <?php if (empty($recentList)): ?>
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-slate-400">ยังไม่มี QR-Code ในระบบ</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentList as $item): ?>
                                    <tr class="hover:bg-slate-50/80 transition">
                                        <td class="py-3.5 px-4">
                                            <div class="flex items-center space-x-3">
                                                <div class="w-8 h-8 rounded-lg bg-blue-50 border border-blue-200 flex items-center justify-center text-blue-600 font-bold text-xs flex-shrink-0">
                                                    QR
                                                </div>
                                                <div class="min-w-0">
                                                    <div class="font-medium text-slate-800 truncate"><?= htmlspecialchars($item['title']) ?></div>
                                                    <a href="<?= htmlspecialchars($item['target_url']) ?>" target="_blank" class="text-xs text-blue-600 hover:underline truncate max-w-xs block font-mono"><?= htmlspecialchars($item['target_url']) ?></a>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-3.5 px-4 text-slate-600">
                                            <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                                                <?= htmlspecialchars($item['category'] ?: 'ทั่วไป') ?>
                                            </span>
                                        </td>
                                        <td class="py-3.5 px-4 text-slate-500 text-xs"><?= formatThaiDateTime($item['created_at']) ?></td>
                                        <td class="py-3.5 px-4 text-slate-600 text-xs"><?= htmlspecialchars($item['created_by'] ?: 'ผู้ดูแลระบบ') ?></td>
                                        <td class="py-3.5 px-4">
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 1.944A11.954 11.954 0 012.166 5C2.056 5.649 2 6.319 2 7c0 5.225 3.34 9.67 8 11.317C14.66 16.67 18 12.225 18 7c0-.682-.057-1.35-.166-2.001A11.954 11.954 0 0110 1.944zM11 14a1 1 0 11-2 0 1 1 0 012 0zm0-7a1 1 0 10-2 0v3a1 1 0 102 0V7z" clip-rule="evenodd"></path></svg>
                                                ใช้งานถาวร
                                            </span>
                                        </td>
                                        <td class="py-3.5 px-4 text-center">
                                            <div class="flex items-center justify-center space-x-1 text-slate-400">
                                                <button type="button" onclick="editQRItem(<?= $item['id'] ?>)" title="แก้ไขข้อมูล QR" class="p-1.5 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition cursor-pointer">
                                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                                </button>
                                                <a href="<?= htmlspecialchars($item['target_url']) ?>" target="_blank" title="เปิดลิงก์" class="p-1.5 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                                </a>
                                                <button type="button" onclick="downloadDirectQR('<?= htmlspecialchars($item['target_url'], ENT_QUOTES) ?>', '<?= htmlspecialchars($item['title'], ENT_QUOTES) ?>', '<?= $item['qr_color'] ?>')" title="ดาวน์โหลด QR" class="p-1.5 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition cursor-pointer">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                                </button>
                                                <button type="button" onclick="deleteQRItem(<?= $item['id'] ?>)" title="ลบข้อมูล" class="p-1.5 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition cursor-pointer">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

    <!-- Mobile Drawer Script -->
    <script>
        const toggleBtn = document.getElementById('mobileMenuToggle');
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('mobileBackdrop');

        function toggleSidebar() {
            if (sidebar.classList.contains('hidden')) {
                sidebar.classList.remove('hidden');
                sidebar.classList.add('fixed', 'inset-y-0', 'left-0', 'w-64', 'shadow-2xl');
                backdrop.classList.remove('hidden');
            } else {
                sidebar.classList.add('hidden');
                sidebar.classList.remove('fixed', 'inset-y-0', 'left-0', 'w-64', 'shadow-2xl');
                backdrop.classList.add('hidden');
            }
        }

        toggleBtn?.addEventListener('click', toggleSidebar);
        backdrop?.addEventListener('click', toggleSidebar);
    </script>
    <script src="assets/js/app.js?v=<?= time() ?>"></script>
</body>
</html>
