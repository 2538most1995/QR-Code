<?php
// print_card.php - หน้าพิมพ์บัตรประจำตัวบุคลากร สกร.
require_once __DIR__ . '/config/database.php';

$pdo = getDBConnection();
$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM personnel WHERE id = ?");
    $stmt->execute([$id]);
    $person = $stmt->fetch();
}

if (!$person) {
    $person = [
        'card_id' => '1-2345-67890-12-3',
        'fullname' => 'นายกิตติพงษ์ จันทร์ดี',
        'position' => 'ครู',
        'department' => 'สำนักงาน สกร. ประจำจังหวัดเชียงใหม่',
        'phone' => '081-234-5678',
        'email' => 'kittipong.cmf@skor.go.th',
        'photo' => 'assets/uploads/sample_person1.png',
        'token' => 'sgr_token_kittipong_01',
        'qr_color' => '#0284c7',
        'created_at' => date('Y-m-d H:i:s')
    ];
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>พิมพ์บัตรประจำตัว - <?= htmlspecialchars($person['fullname']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="assets/css/style.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; padding: 0 !important; }
            .id-card-print { box-shadow: none !important; border: 1px solid #cbd5e1 !important; }
        }
        .id-card-size {
            width: 86mm;
            height: 54mm;
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen p-6 flex flex-col items-center justify-center">

    <!-- Top Action Bar for Printing -->
    <div class="no-print mb-6 flex items-center space-x-3">
        <button onclick="window.print()" class="flex items-center space-x-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-lg shadow-blue-600/30 transition text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            <span>สั่งพิมพ์บัตร (Print Card)</span>
        </button>
        <button onclick="window.close()" class="px-4 py-2.5 bg-white text-slate-700 font-semibold rounded-xl border border-slate-200 shadow-sm text-sm">
            ปิดหน้านี้
        </button>
    </div>

    <!-- Standard ID Card Container (Front & Back) -->
    <div class="flex flex-col md:flex-row gap-6 items-center">
        
        <!-- CARD FRONT -->
        <div class="id-card-print bg-white rounded-2xl shadow-xl border border-slate-200 p-4 flex flex-col justify-between relative overflow-hidden" style="width: 340px; height: 216px;">
            <!-- Top Gradient Bar -->
            <div class="absolute top-0 left-0 right-0 h-3 bg-gradient-to-r from-blue-700 via-blue-500 to-indigo-600"></div>

            <div class="flex items-center space-x-2 pt-2 border-b border-slate-100 pb-2">
                <img src="assets/images/logo-sgr.png" alt="สกร." class="w-7 h-7 object-contain">
                <div>
                    <h3 class="text-xs font-bold text-slate-800 leading-none">สำนักงานส่งเสริมการเรียนรู้ประจำจังหวัด</h3>
                    <p class="text-[9px] text-slate-500">Department of Learning Encouragement</p>
                </div>
            </div>

            <div class="flex items-center space-x-3 my-auto">
                <div class="w-16 h-20 rounded-lg overflow-hidden border border-slate-200 flex-shrink-0 bg-slate-100 shadow-sm">
                    <img src="<?= htmlspecialchars($person['photo'] ?: 'assets/images/default-avatar.png') ?>" alt="Photo" class="w-full h-full object-cover">
                </div>
                <div class="flex-1 min-w-0 space-y-0.5 text-left">
                    <div class="text-xs font-bold text-slate-900 truncate"><?= htmlspecialchars($person['fullname']) ?></div>
                    <div class="inline-block px-1.5 py-0.5 bg-blue-50 text-blue-700 text-[10px] font-bold rounded">
                        <?= htmlspecialchars($person['position']) ?>
                    </div>
                    <div class="text-[9px] text-slate-600 truncate"><?= htmlspecialchars($person['department']) ?></div>
                    <div class="text-[9px] text-slate-500 font-mono">ID: <?= htmlspecialchars($person['card_id']) ?></div>
                </div>
            </div>

            <div class="border-t border-slate-100 pt-1.5 flex items-center justify-between text-[8px] text-slate-400">
                <span>บัตรประจำตัวเจ้าหน้าที่ สกร.</span>
                <span>ใช้งานถาวร</span>
            </div>
        </div>

        <!-- CARD BACK -->
        <div class="id-card-print bg-white rounded-2xl shadow-xl border border-slate-200 p-4 flex flex-col justify-between relative overflow-hidden" style="width: 340px; height: 216px;">
            <div class="absolute top-0 left-0 right-0 h-3 bg-gradient-to-r from-blue-700 via-blue-500 to-indigo-600"></div>

            <div class="text-center pt-2">
                <h4 class="text-[10px] font-bold text-slate-800 uppercase tracking-wider">Official Digital Identity</h4>
                <p class="text-[8px] text-slate-400">สแกน QR-Code เพื่อตรวจสอบข้อมูลยืนยันตัวตน</p>
            </div>

            <div class="flex items-center justify-center my-auto">
                <div id="print_qr_box" class="w-24 h-24 p-1 bg-white border border-slate-200 rounded-lg shadow-sm flex items-center justify-center">
                    <!-- QR rendered via JS -->
                </div>
            </div>

            <div class="border-t border-slate-100 pt-1 text-center text-[8px] text-slate-400">
                หากพบบัตรนี้กรุณาส่งคืน สำนักงาน สกร. ประจำจังหวัด
            </div>
        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const baseUrl = window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '');
            const profileUrl = `${baseUrl}/profile.php?token=<?= urlencode($person['token']) ?>`;
            
            const qrBox = document.getElementById('print_qr_box');
            if (qrBox && typeof QRCode !== 'undefined') {
                new QRCode(qrBox, {
                    text: profileUrl,
                    width: 86,
                    height: 86,
                    colorDark: "<?= $person['qr_color'] ?: '#0284c7' ?>",
                    colorLight: "#ffffff"
                });
            }
        });
    </script>
</body>
</html>
