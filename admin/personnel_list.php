<?php
// admin/personnel_list.php - จัดการข้อมูลบุคลากรทั้งหมด
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

requireLogin();

$pdo = getDBConnection();
$currentUser = getLoggedInUser();

$search = trim($_GET['search'] ?? '');
if ($search !== '') {
    $stmt = $pdo->prepare("SELECT * FROM personnel WHERE fullname LIKE ? OR card_id LIKE ? OR department LIKE ? OR position LIKE ? ORDER BY id DESC");
    $term = "%{$search}%";
    $stmt->execute([$term, $term, $term, $term]);
} else {
    $stmt = $pdo->query("SELECT * FROM personnel ORDER BY id DESC");
}
$personnelList = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ข้อมูลบุคคลทั้งหมด - ระบบสร้าง QR-Code คน สกร.</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-slate-100/90 text-slate-800 antialiased min-h-screen flex flex-col lg:flex-row">

    <!-- Mobile Header -->
    <div class="lg:hidden bg-blue-600 text-white p-4 flex items-center justify-between sticky top-0 z-50">
        <span class="font-bold">ระบบสร้าง QR-Code คน สกร.</span>
        <a href="../index.php" class="text-xs bg-white/20 px-3 py-1.5 rounded-lg">กลับหน้าแรก</a>
    </div>

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
                <span>หน้าแรก</span>
            </a>

            <a href="personnel_list.php" class="flex items-center justify-between px-4 py-3 rounded-xl bg-white/20 text-white font-semibold shadow-sm transition">
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    <span>ข้อมูลบุคคล</span>
                </div>
            </a>

            <a href="../index.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-blue-100 hover:bg-white/10 transition">
                <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                <span>สร้าง QR-Code</span>
            </a>

            <a href="history.php" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-blue-100 hover:bg-white/10 transition">
                <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>ประวัติการสร้าง</span>
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
            
            <!-- Page Header -->
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-slate-800 font-heading">ข้อมูลบุคลากรทั้งหมด</h2>
                    <p class="text-xs text-slate-500 mt-1">จัดการรายชื่อบุคลากร, แก้ไขข้อมูล, พิมพ์บัตร และดาวน์โหลด QR-Code</p>
                </div>
                <div class="flex items-center space-x-3">
                    <a href="../index.php" class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded-xl shadow-lg shadow-blue-600/30 transition flex items-center space-x-2">
                        <span>+ เพิ่มบุคลากร / สร้าง QR</span>
                    </a>
                </div>
            </div>

            <!-- Search & Filters -->
            <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-200 flex flex-wrap items-center justify-between gap-3">
                <form method="GET" class="flex-1 min-w-[280px] max-w-md relative">
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="ค้นหาชื่อ, เลขประจำตัว, ตำแหน่ง, หน่วยงาน..." class="w-full pl-10 pr-4 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white focus:outline-none transition">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                </form>
                <div class="text-xs text-slate-500">
                    พบทั้งหมด <span class="font-bold text-slate-800"><?= count($personnelList) ?></span> รายการ
                </div>
            </div>

            <!-- Table Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 text-xs font-semibold uppercase">
                                <th class="py-3.5 px-4">รูปถ่าย / ชื่อ-สกุล</th>
                                <th class="py-3.5 px-4">เลขประจำตัว</th>
                                <th class="py-3.5 px-4">ตำแหน่ง</th>
                                <th class="py-3.5 px-4">หน่วยงาน</th>
                                <th class="py-3.5 px-4">ติดต่อ</th>
                                <th class="py-3.5 px-4 text-center">การจัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            <?php if (empty($personnelList)): ?>
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-slate-400">ไม่พบข้อมูลที่ค้นหา</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($personnelList as $item): ?>
                                    <tr class="hover:bg-slate-50/80 transition">
                                        <td class="py-3.5 px-4">
                                            <div class="flex items-center space-x-3">
                                                <img src="../<?= htmlspecialchars($item['photo'] ?: 'assets/images/default-avatar.png') ?>" class="w-9 h-9 rounded-full object-cover border border-slate-200 shadow-sm" alt="<?= htmlspecialchars($item['fullname']) ?>">
                                                <span class="font-semibold text-slate-800"><?= htmlspecialchars($item['fullname']) ?></span>
                                            </div>
                                        </td>
                                        <td class="py-3.5 px-4 font-mono text-slate-600 text-xs"><?= htmlspecialchars($item['card_id']) ?></td>
                                        <td class="py-3.5 px-4">
                                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                                <?= htmlspecialchars($item['position']) ?>
                                            </span>
                                        </td>
                                        <td class="py-3.5 px-4 text-slate-600 text-xs"><?= htmlspecialchars($item['department']) ?></td>
                                        <td class="py-3.5 px-4 text-xs space-y-0.5 text-slate-500">
                                            <div>📞 <?= htmlspecialchars($item['phone'] ?: '-') ?></div>
                                            <div>✉️ <?= htmlspecialchars($item['email'] ?: '-') ?></div>
                                        </td>
                                        <td class="py-3.5 px-4 text-center">
                                            <div class="flex items-center justify-center space-x-2 text-slate-400">
                                                <a href="../profile.php?token=<?= urlencode($item['token']) ?>" target="_blank" class="p-2 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition" title="เปิดดูบัตร">
                                                    👁️
                                                </a>
                                                <a href="../print_card.php?id=<?= $item['id'] ?>" target="_blank" class="p-2 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition" title="พิมพ์บัตร">
                                                    🖨️
                                                </a>
                                                <button onclick="deleteRow(<?= $item['id'] ?>)" class="p-2 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition" title="ลบข้อมูล">
                                                    🗑️
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

    <script>
        function deleteRow(id) {
            if (!confirm('คุณต้องการลบข้อมูลบุคลากรนี้ใช่หรือไม่?')) return;
            fetch(`../api/personnel.php?action=delete&id=${id}`)
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        alert('ลบข้อมูลเรียบร้อยแล้ว');
                        location.reload();
                    } else {
                        alert(res.message || 'เกิดข้อผิดพลาด');
                    }
                });
        }
    </script>
</body>
</html>
