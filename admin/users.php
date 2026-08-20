<?php
// admin/users.php - จัดการผู้ใช้งานระบบ สกร.
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

requireLogin();

$pdo = getDBConnection();
$currentUser = getLoggedInUser();

// ตรวจสอบสิทธิ์ - อนุญาตเฉพาะ "ผู้ดูแลระบบสูงสุด" หรือ "ผู้ดูแลระบบ"
if (!in_array($currentUser['role'], ['ผู้ดูแลระบบสูงสุด', 'ผู้ดูแลระบบ'])) {
    die("<div style='font-family: sans-serif; padding: 25px; background: #fee2e2; border: 1px solid #ef4444; border-radius: 16px; max-width: 500px; margin: 50px auto; text-align: center;'>
        <h3 style='color: #b91c1c; margin-top: 0;'>⛔ ไม่มีสิทธิ์เข้าถึง</h3>
        <p style='color: #4b5563; font-size: 14px;'>เมนูจัดการผู้ใช้งานอนุญาตให้เฉพาะ <b>ผู้ดูแลระบบ</b> เข้าใช้งานได้เท่านั้น</p>
        <a href='../index.php' style='display: inline-block; margin-top: 15px; padding: 8px 18px; background: #2563eb; color: white; border-radius: 8px; text-decoration: none; font-size: 13px;'>กลับหน้าหลัก</a>
    </div>");
}
$orgShortName = getSystemSetting('org_short_name', 'สกร.');
$orgName = getSystemSetting('org_name', 'สำนักงานส่งเสริมการเรียนรู้');
$orgSub = getSystemSetting('org_sub', 'ประจำจังหวัด');
$orgLogo = getSystemSetting('org_logo', 'assets/images/logo-sgr.png');

$stmt = $pdo->query("SELECT * FROM admins ORDER BY id DESC");
$userList = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการผู้ใช้งาน - ระบบสร้าง QR-Code คน <?= htmlspecialchars($orgShortName) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="../assets/css/style.css?v=<?= time() ?>" rel="stylesheet">
</head>
<body class="bg-slate-100/90 text-slate-800 antialiased min-h-screen flex flex-col lg:flex-row">

    <!-- Mobile Header -->
    <div class="lg:hidden bg-blue-600 text-white p-4 flex items-center justify-between sticky top-0 z-50 shadow-md">
        <div class="flex items-center space-x-3">
            <img src="../<?= htmlspecialchars($orgLogo) ?>" alt="สกร." class="w-9 h-9 object-contain bg-white/10 rounded-full p-1">
            <div class="font-bold text-base leading-tight"><?= htmlspecialchars($orgShortName) ?></div>
        </div>
        <button id="mobileMenuToggle" class="p-2 rounded-lg bg-white/10 hover:bg-white/20 text-white">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
        </button>
    </div>

    <!-- Sidebar Navigation -->
    <aside id="sidebar" class="hidden lg:flex flex-col w-64 sidebar-gradient text-white flex-shrink-0 relative overflow-hidden z-40">
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
            <a href="users.php" class="flex items-center justify-between px-4 py-3 rounded-xl bg-white/20 text-white font-semibold shadow-sm transition">
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    <span>จัดการผู้ใช้งาน</span>
                </div>
            </a>
            <a href="settings.php" class="flex items-center justify-between px-4 py-3 rounded-xl text-blue-100 hover:bg-white/10 transition">
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path></svg>
                    <span>ตั้งค่าหน่วยงาน</span>
                </div>
            </a>
        </nav>
    </aside>

    <!-- Main Content Area -->
    <main class="flex-1 flex flex-col min-w-0 overflow-y-auto p-4 md:p-8">
        <div class="max-w-6xl mx-auto w-full space-y-6">
            
            <!-- Page Header -->
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-slate-800 font-heading">จัดการผู้ใช้งานระบบ</h2>
                    <p class="text-xs text-slate-500 mt-1">รายชื่อผู้ใช้งาน เจ้าหน้าที่ และผู้ดูแลระบบ</p>
                </div>
                <button type="button" onclick="openAddUserModal()" class="inline-flex items-center space-x-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-bold rounded-xl text-xs shadow-md shadow-blue-600/20 transition cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                    <span>➕ เพิ่มผู้ใช้งานใหม่</span>
                </button>
            </div>

            <!-- Users Table Card -->
            <div class="bg-white rounded-3xl shadow-sm border border-slate-200/80 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/70 border-b border-slate-100 text-slate-500 text-xs font-semibold uppercase tracking-wider">
                                <th class="py-3.5 px-4">ชื่อ-นามสกุล / ชื่อผู้ใช้</th>
                                <th class="py-3.5 px-4">อีเมล</th>
                                <th class="py-3.5 px-4">สิทธิ์การใช้งาน</th>
                                <th class="py-3.5 px-4">วิธีเข้าสู่ระบบ</th>
                                <th class="py-3.5 px-4">สถานะ</th>
                                <th class="py-3.5 px-4">วันที่ลงทะเบียน</th>
                                <th class="py-3.5 px-4 text-center">การจัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            <?php foreach ($userList as $u): ?>
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="py-3.5 px-4">
                                        <div class="flex items-center space-x-3">
                                            <img src="<?= (strpos($u['avatar'], 'http') === 0) ? htmlspecialchars($u['avatar']) : '../' . htmlspecialchars($u['avatar']) ?>" class="w-9 h-9 rounded-full object-cover border border-slate-200 shadow-sm">
                                            <div>
                                                <div class="font-bold text-slate-800"><?= htmlspecialchars($u['fullname']) ?></div>
                                                <div class="text-xs text-slate-400 font-mono">@<?= htmlspecialchars($u['username']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 text-slate-600 text-xs">
                                        <?= htmlspecialchars($u['email'] ?: '-') ?>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold <?= ($u['role'] === 'ผู้ดูแลระบบสูงสุด' || $u['role'] === 'ผู้ดูแลระบบ') ? 'bg-purple-50 text-purple-700 border border-purple-200' : 'bg-blue-50 text-blue-700 border border-blue-200' ?>">
                                            <?= htmlspecialchars($u['role']) ?>
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <?php if ($u['oauth_provider'] === 'google'): ?>
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/></svg>
                                                Google
                                            </span>
                                        <?php elseif ($u['oauth_provider'] === 'line'): ?>
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">
                                                <img src="../assets/images/icons/icon-line.svg" class="w-3.5 h-3.5">
                                                LINE
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600">
                                                🔑 รหัสผ่าน
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium <?= ($u['status'] === 'active') ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' ?>">
                                            <?= ($u['status'] === 'active') ? '✓ ใช้งานปกติ' : '✕ ระงับใช้งาน' ?>
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-xs text-slate-500"><?= formatThaiDateTime($u['created_at']) ?></td>
                                    <td class="py-3.5 px-4 text-center">
                                        <div class="flex items-center justify-center space-x-1.5 text-slate-400">
                                            <button type="button" onclick="openEditUserModal(<?= $u['id'] ?>)" title="แก้ไขผู้ใช้" class="p-1.5 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition cursor-pointer">
                                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                            </button>
                                            <?php if ($u['id'] != $currentUser['id']): ?>
                                                <button type="button" onclick="deleteUser(<?= $u['id'] ?>)" title="ลบผู้ใช้" class="p-1.5 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition cursor-pointer">
                                                    <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

    <!-- Modal: Add User -->
    <div id="addUserModal" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl p-6 max-w-md w-full shadow-2xl border border-slate-200 animate-in fade-in zoom-in-95 duration-200">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                <h3 class="font-bold text-slate-800 text-lg">➕ เพิ่มผู้ใช้งานใหม่</h3>
                <button type="button" onclick="closeAddUserModal()" class="text-slate-400 hover:text-slate-600 text-lg">✕</button>
            </div>
            <form id="addUserForm" onsubmit="event.preventDefault(); submitAddUser();" class="space-y-3.5">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">ชื่อ-นามสกุล <span class="text-rose-500">*</span></label>
                    <input type="text" id="add_fullname" required class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">ชื่อผู้ใช้งาน (Username) <span class="text-rose-500">*</span></label>
                    <input type="text" id="add_username" required class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white focus:outline-none font-mono">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">อีเมล</label>
                    <input type="email" id="add_email" class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">รหัสผ่าน <span class="text-rose-500">*</span></label>
                    <input type="password" id="add_password" required class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white focus:outline-none">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">สิทธิ์การใช้งาน</label>
                        <select id="add_role" class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
                            <option value="เจ้าหน้าที่" selected>เจ้าหน้าที่</option>
                            <option value="ผู้ดูแลระบบ">ผู้ดูแลระบบ</option>
                            <option value="ผู้ดูแลระบบสูงสุด">ผู้ดูแลระบบสูงสุด</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">สถานะ</label>
                        <select id="add_status" class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
                            <option value="active" selected>ใช้งานปกติ</option>
                            <option value="inactive">ระงับใช้งาน</option>
                        </select>
                    </div>
                </div>
                <div class="pt-3 border-t border-slate-100 flex items-center justify-end space-x-2">
                    <button type="button" onclick="closeAddUserModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold">ยกเลิก</button>
                    <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-md shadow-blue-600/20">บันทึกข้อมูล</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Edit User -->
    <div id="editUserModal" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl p-6 max-w-md w-full shadow-2xl border border-slate-200 animate-in fade-in zoom-in-95 duration-200">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                <h3 class="font-bold text-slate-800 text-lg">✏️ แก้ไขข้อมูลผู้ใช้งาน</h3>
                <button type="button" onclick="closeEditUserModal()" class="text-slate-400 hover:text-slate-600 text-lg">✕</button>
            </div>
            <form id="editUserForm" onsubmit="event.preventDefault(); submitEditUser();" class="space-y-3.5">
                <input type="hidden" id="edit_user_id">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">ชื่อ-นามสกุล <span class="text-rose-500">*</span></label>
                    <input type="text" id="edit_fullname" required class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">อีเมล</label>
                    <input type="email" id="edit_email" class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">เปลี่ยนรหัสผ่านใหม่ (เว้นว่างไว้ถ้าไม่ต้องการเปลี่ยน)</label>
                    <input type="password" id="edit_password" placeholder="••••••••" class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white focus:outline-none">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">สิทธิ์การใช้งาน</label>
                        <select id="edit_role" class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
                            <option value="เจ้าหน้าที่">เจ้าหน้าที่</option>
                            <option value="ผู้ดูแลระบบ">ผู้ดูแลระบบ</option>
                            <option value="ผู้ดูแลระบบสูงสุด">ผู้ดูแลระบบสูงสุด</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">สถานะ</label>
                        <select id="edit_status" class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none">
                            <option value="active">ใช้งานปกติ</option>
                            <option value="inactive">ระงับใช้งาน</option>
                        </select>
                    </div>
                </div>
                <div class="pt-3 border-t border-slate-100 flex items-center justify-end space-x-2">
                    <button type="button" onclick="closeEditUserModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold">ยกเลิก</button>
                    <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-md shadow-blue-600/20">บันทึกการแก้ไข</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddUserModal() {
            document.getElementById('addUserModal').classList.remove('hidden');
        }
        function closeAddUserModal() {
            document.getElementById('addUserModal').classList.add('hidden');
        }

        function openEditUserModal(id) {
            fetch(`../api/users.php?action=get&id=${id}`)
            .then(r => r.json())
            .then(res => {
                if (res.success && res.data) {
                    const u = res.data;
                    document.getElementById('edit_user_id').value = u.id;
                    document.getElementById('edit_fullname').value = u.fullname;
                    document.getElementById('edit_email').value = u.email || '';
                    document.getElementById('edit_role').value = u.role;
                    document.getElementById('edit_status').value = u.status || 'active';
                    document.getElementById('edit_password').value = '';
                    document.getElementById('editUserModal').classList.remove('hidden');
                }
            });
        }
        function closeEditUserModal() {
            document.getElementById('editUserModal').classList.add('hidden');
        }

        function submitAddUser() {
            const formData = new FormData();
            formData.append('action', 'create');
            formData.append('fullname', document.getElementById('add_fullname').value);
            formData.append('username', document.getElementById('add_username').value);
            formData.append('email', document.getElementById('add_email').value);
            formData.append('password', document.getElementById('add_password').value);
            formData.append('role', document.getElementById('add_role').value);
            formData.append('status', document.getElementById('add_status').value);

            fetch('../api/users.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    alert(res.message);
                    location.reload();
                } else {
                    alert(res.message);
                }
            });
        }

        function submitEditUser() {
            const formData = new FormData();
            formData.append('action', 'update');
            formData.append('id', document.getElementById('edit_user_id').value);
            formData.append('fullname', document.getElementById('edit_fullname').value);
            formData.append('email', document.getElementById('edit_email').value);
            formData.append('role', document.getElementById('edit_role').value);
            formData.append('status', document.getElementById('edit_status').value);
            formData.append('password', document.getElementById('edit_password').value);

            fetch('../api/users.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    alert(res.message);
                    location.reload();
                } else {
                    alert(res.message);
                }
            });
        }

        function deleteUser(id) {
            if (!confirm('คุณต้องการลบผู้ใช้งานนี้ใช่หรือไม่?')) return;
            fetch(`../api/users.php?action=delete&id=${id}`)
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    alert(res.message);
                    location.reload();
                } else {
                    alert(res.message);
                }
            });
        }
    </script>
</body>
</html>
