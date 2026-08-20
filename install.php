<?php
// install.php - หน้าติดตั้งระบบและฐานข้อมูลอัตโนมัติ
require_once __DIR__ . '/config/database.php';

$message = '';
$status = 'info';

if (isset($_POST['install']) || isset($_GET['auto'])) {
    try {
        $pdo = getDBConnection();
        // Schema is initialized inside getDBConnection()
        $message = 'ระบบและฐานข้อมูลถูกติดตั้งเรียบร้อยแล้ว! ข้อมูลตัวอย่างและบัญชีผู้ดูแลระบบพร้อมใช้งาน';
        $status = 'success';
    } catch (Exception $e) {
        $message = 'เกิดข้อผิดพลาดในการติดตั้ง: ' . $e->getMessage();
        $status = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ติดตั้งระบบสร้าง QR-Code สกร.</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; }
    </style>
</head>
<body class="bg-slate-100 flex items-center justify-center min-h-screen p-4">
    <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-8 border border-slate-200 text-center">
        <div class="w-20 h-20 bg-blue-600 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg shadow-blue-500/30">
            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
            </svg>
        </div>
        
        <h1 class="text-2xl font-bold text-slate-800 mb-1">ติดตั้งระบบสร้าง QR-Code สกร.</h1>
        <p class="text-slate-500 text-sm mb-6">ระบบจัดการข้อมูลและ QR-Code บุคลากร สำนักงาน สกร.</p>

        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-xl text-sm font-medium <?= $status === 'success' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
            <?php if ($status === 'success'): ?>
                <div class="bg-slate-50 rounded-xl p-4 mb-6 text-left text-xs text-slate-600 space-y-1">
                    <p class="font-bold text-slate-800 text-sm mb-2">ข้อมูลผู้ดูแลระบบเริ่มต้น (Default Admin):</p>
                    <p><span class="font-medium text-slate-700">ชื่อผู้ใช้:</span> admin</p>
                    <p><span class="font-medium text-slate-700">รหัสผ่าน:</span> admin1234</p>
                </div>
                <a href="index.php" class="block w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition shadow-lg shadow-blue-600/30">
                    เข้าสู่หน้าระบบหลัก &rarr;
                </a>
            <?php endif; ?>
        <?php else: ?>
            <form method="POST">
                <button type="submit" name="install" class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition shadow-lg shadow-blue-600/30">
                    เริ่มการติดตั้งฐานข้อมูล (Install Database)
                </button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
