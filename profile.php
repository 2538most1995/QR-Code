<?php
// profile.php - หน้าแสดงข้อมูลบัตรประจำตัวดิจิทัลเมื่อสแกน QR Code (Public Mobile Profile)
require_once __DIR__ . '/config/database.php';

$pdo = getDBConnection();
$token = $_GET['token'] ?? '';
$id = (int)($_GET['id'] ?? 0);

if (!empty($token)) {
    $stmt = $pdo->prepare("SELECT * FROM personnel WHERE token = ?");
    $stmt->execute([$token]);
} elseif ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM personnel WHERE id = ?");
    $stmt->execute([$id]);
} else {
    // ดึงรายการแรกเป็นตัวอย่างพรีวิว
    $stmt = $pdo->query("SELECT * FROM personnel ORDER BY id DESC LIMIT 1");
}

$person = $stmt->fetch();

// บันทึก Log การสแกน
if ($person) {
    $logStmt = $pdo->prepare("INSERT INTO qr_logs (personnel_id, action, ip_address, user_agent) VALUES (?, 'scan_view', ?, ?)");
    $logStmt->execute([$person['id'], $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '']);
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $person ? htmlspecialchars($person['fullname']) : 'ไม่พบข้อมูล' ?> - บัตรประจำตัวดิจิทัล สกร.</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="assets/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-100 min-h-screen py-8 px-4 flex flex-col items-center justify-center">

    <?php if (!$person): ?>
        <div class="bg-white rounded-3xl p-8 max-w-sm w-full text-center shadow-xl border border-slate-200">
            <div class="w-16 h-16 bg-rose-100 text-rose-500 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl">
                ✕
            </div>
            <h2 class="text-xl font-bold text-slate-800 mb-2">ไม่พบข้อมูลบุคลากร</h2>
            <p class="text-sm text-slate-500 mb-6">รหัส QR-Code นี้อาจถูกยกเลิกหรือไม่มีอยู่ในระบบ</p>
            <a href="index.php" class="inline-block px-5 py-2.5 bg-blue-600 text-white font-medium rounded-xl text-sm shadow-md">กลับสู่หน้าหลัก</a>
        </div>
    <?php else: ?>
        <div class="max-w-md w-full">
            <!-- Digital ID Card Container -->
            <div class="bg-white rounded-3xl shadow-2xl overflow-hidden border border-slate-200/90 relative">
                
                <!-- Card Header with SGR Gradient & Emblem -->
                <div class="bg-gradient-to-r from-blue-700 via-blue-600 to-indigo-700 text-white p-6 pb-14 text-center relative">
                    <div class="flex items-center justify-center space-x-2.5 mb-2">
                        <img src="assets/images/logo-sgr.png" alt="สกร." class="w-10 h-10 object-contain drop-shadow">
                        <div class="text-left">
                            <h3 class="font-bold text-base leading-tight tracking-wide">กรมส่งเสริมการเรียนรู้</h3>
                            <p class="text-[11px] text-blue-100 font-light">Department of Learning Encouragement</p>
                        </div>
                    </div>

                    <!-- Verified Badge -->
                    <div class="inline-flex items-center gap-1.5 px-3 py-1 bg-white/20 backdrop-blur-md rounded-full text-xs font-semibold text-emerald-300 border border-white/20 shadow-sm mt-1">
                        <svg class="w-4 h-4 text-emerald-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 1.944A11.954 11.954 0 012.166 5C2.056 5.649 2 6.319 2 7c0 5.225 3.34 9.67 8 11.317C14.66 16.67 18 12.225 18 7c0-.682-.057-1.35-.166-2.001A11.954 11.954 0 0110 1.944zM11 14a1 1 0 11-2 0 1 1 0 012 0zm0-7a1 1 0 10-2 0v3a1 1 0 102 0V7z" clip-rule="evenodd"></path></svg>
                        <span>ข้อมูลผ่านการตรวจสอบแล้ว (Verified)</span>
                    </div>
                </div>

                <!-- Avatar Section (Overlapping Header) -->
                <div class="flex flex-col items-center -mt-12 px-6">
                    <div class="relative">
                        <img src="<?= htmlspecialchars($person['photo'] ?: 'assets/images/default-avatar.png') ?>" alt="<?= htmlspecialchars($person['fullname']) ?>" class="w-24 h-24 rounded-full object-cover border-4 border-white shadow-xl bg-slate-100">
                        <span class="absolute bottom-1 right-1 bg-emerald-500 w-5 h-5 rounded-full border-2 border-white flex items-center justify-center text-white text-[10px]" title="ใช้งานถาวร">✓</span>
                    </div>

                    <h1 class="text-xl font-bold text-slate-800 mt-3 text-center"><?= htmlspecialchars($person['fullname']) ?></h1>
                    <span class="inline-block px-3 py-1 bg-blue-50 text-blue-700 text-xs font-bold rounded-full border border-blue-200 mt-1">
                        <?= htmlspecialchars($person['position']) ?>
                    </span>
                </div>

                <!-- Card Details Body -->
                <div class="p-6 space-y-4 text-sm">
                    <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100 space-y-3">
                        <div class="flex items-start space-x-3">
                            <span class="text-blue-600 text-base mt-0.5">🏢</span>
                            <div>
                                <div class="text-[11px] font-semibold text-slate-400 uppercase">หน่วยงาน / สังกัด</div>
                                <div class="font-medium text-slate-700"><?= htmlspecialchars($person['department']) ?></div>
                            </div>
                        </div>

                        <div class="flex items-start space-x-3 border-t border-slate-200/60 pt-2.5">
                            <span class="text-blue-600 text-base mt-0.5">🪪</span>
                            <div>
                                <div class="text-[11px] font-semibold text-slate-400 uppercase">เลขประจำตัว</div>
                                <div class="font-mono font-medium text-slate-700"><?= htmlspecialchars($person['card_id']) ?></div>
                            </div>
                        </div>

                        <?php if (!empty($person['phone'])): ?>
                            <div class="flex items-start space-x-3 border-t border-slate-200/60 pt-2.5">
                                <span class="text-blue-600 text-base mt-0.5">📞</span>
                                <div>
                                    <div class="text-[11px] font-semibold text-slate-400 uppercase">เบอร์โทรศัพท์</div>
                                    <a href="tel:<?= htmlspecialchars($person['phone']) ?>" class="font-medium text-blue-600 hover:underline"><?= htmlspecialchars($person['phone']) ?></a>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($person['email'])): ?>
                            <div class="flex items-start space-x-3 border-t border-slate-200/60 pt-2.5">
                                <span class="text-blue-600 text-base mt-0.5">✉️</span>
                                <div>
                                    <div class="text-[11px] font-semibold text-slate-400 uppercase">อีเมล</div>
                                    <a href="mailto:<?= htmlspecialchars($person['email']) ?>" class="font-medium text-blue-600 hover:underline break-all"><?= htmlspecialchars($person['email']) ?></a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Quick Action Buttons -->
                    <div class="grid grid-cols-2 gap-3 pt-2">
                        <?php if (!empty($person['phone'])): ?>
                            <a href="tel:<?= htmlspecialchars($person['phone']) ?>" class="flex items-center justify-center space-x-2 py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl shadow-md transition text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                <span>โทรออก</span>
                            </a>
                        <?php endif; ?>

                        <button onclick="saveContact()" class="flex items-center justify-center space-x-2 py-3 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl shadow-md transition text-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            <span>บันทึกผู้ติดต่อ</span>
                        </button>
                    </div>

                    <div class="text-center pt-2">
                        <p class="text-[11px] text-slate-400">
                            ออกบัตรเมื่อ: <?= formatThaiDateTime($person['created_at']) ?> &bull; รหัส: <?= htmlspecialchars($person['token']) ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Back link -->
            <div class="text-center mt-6">
                <a href="index.php" class="text-xs font-semibold text-slate-500 hover:text-blue-600 transition">&larr; ระบบจัดการ QR-Code สกร.</a>
            </div>
        </div>

        <script>
            function saveContact() {
                const vcard = `BEGIN:VCARD
VERSION:3.0
FN:<?= addslashes($person['fullname']) ?>
ORG:<?= addslashes($person['department']) ?>
TITLE:<?= addslashes($person['position']) ?>
TEL;TYPE=CELL:<?= addslashes($person['phone']) ?>
EMAIL:<?= addslashes($person['email']) ?>
NOTE:เลขประจำตัว <?= addslashes($person['card_id']) ?>
END:VCARD`;

                const blob = new Blob([vcard], { type: 'text/vcard;charset=utf-8;' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `<?= preg_replace('/\s+/', '_', $person['fullname']) ?>.vcf`;
                a.click();
            }
        </script>
    <?php endif; ?>

</body>
</html>
