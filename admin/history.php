<?php
// admin/history.php - ประวัติการสร้างและการสแกน QR Code
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

requireLogin();

$pdo = getDBConnection();
$currentUser = getLoggedInUser();

$stmt = $pdo->query("SELECT l.*, q.title, q.target_url 
                     FROM qr_logs l 
                     LEFT JOIN qr_items q ON (l.qr_id = q.id OR l.personnel_id = q.id)
                     ORDER BY l.id DESC LIMIT 100");
$logs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติการสร้างและการสแกน - ระบบสร้าง QR-Code คน สกร.</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-slate-100/90 text-slate-800 antialiased min-h-screen flex flex-col lg:flex-row">

    <!-- Sidebar Navigation -->
    <aside class="hidden lg:flex flex-col w-64 sidebar-gradient text-white flex-shrink-0 relative overflow-hidden">
        <div class="p-5 flex items-center space-x-3 border-b border-white/10">
            <div class="w-12 h-12 rounded-xl bg-white/10 flex items-center justify-center p-1.5 shadow-inner">
                <img src="../assets/images/logo-sgr.png" alt="ตราสัญลักษณ์ สกร." class="max-h-full object-contain">
            </div>
            <div>
                <h1 class="text-xl font-bold tracking-tight leading-none text-white">สกร.</h1>
                <p class="text-[11px] text-blue-100 font-light mt-1 leading-snug">สำนักงานส่งเสริมการเรียนรู้<br>ประจำจังหวัด</p>
            </div>
        </div>

        <nav class="flex-1 px-3 py-5 space-y-1.5 text-sm font-medium">
            <a href="../index.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-blue-100 hover:bg-white/10 transition">
                <svg class="w-5 h-5 opacity-80" fill="currentColor" viewBox="0 0 20 20"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path></svg>
                <span>หน้าแรก (สร้าง QR)</span>
            </a>
            <a href="history.php" class="flex items-center justify-between px-4 py-3 rounded-xl bg-white/20 text-white font-semibold shadow-sm transition">
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>ประวัติการสร้าง</span>
                </div>
            </a>
            <a href="settings.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-blue-100 hover:bg-white/10 transition">
                <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path></svg>
                <span>ตั้งค่า</span>
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col min-w-0 overflow-y-auto p-4 md:p-8">
        <div class="max-w-7xl mx-auto w-full space-y-6">
            <div>
                <h2 class="text-2xl font-bold text-slate-800 font-heading">ประวัติการสร้างและสแกน QR-Code</h2>
                <p class="text-xs text-slate-500 mt-1">บันทึกกิจกรรมการสร้างโค้ดและการเปิดอ่านข้อมูลผ่าน QR Code</p>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 text-xs font-semibold uppercase">
                                <th class="py-3.5 px-4">วันที่ / เวลา</th>
                                <th class="py-3.5 px-4">กิจกรรม</th>
                                <th class="py-3.5 px-4">ชื่อ QR-Code / ลิงก์</th>
                                <th class="py-3.5 px-4">IP Address</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="4" class="py-8 text-center text-slate-400">ยังไม่มีบันทึกประวัติกิจกรรม</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr class="hover:bg-slate-50/80 transition">
                                        <td class="py-3.5 px-4 text-xs font-mono text-slate-500"><?= formatThaiDateTime($log['created_at']) ?></td>
                                        <td class="py-3.5 px-4">
                                            <?php if ($log['action'] === 'create_qr'): ?>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700">
                                                    ➕ สร้าง QR Code ใหม่
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">
                                                    📱 สแกนเปิดลิงก์
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3.5 px-4 font-medium text-slate-800">
                                            <?= htmlspecialchars($log['title'] ?: 'QR Code') ?>
                                            <?php if ($log['target_url']): ?>
                                                <div class="text-xs text-blue-600 font-mono truncate max-w-sm"><?= htmlspecialchars($log['target_url']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3.5 px-4 text-xs font-mono text-slate-500"><?= htmlspecialchars($log['ip_address'] ?: '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
