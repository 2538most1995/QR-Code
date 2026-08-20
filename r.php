<?php
// r.php - Direct redirect and click tracking when scanned
require_once __DIR__ . '/config/database.php';

$token = trim($_GET['token'] ?? '');
$targetUrl = '';

if (!empty($token)) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM qr_items WHERE token = ?");
        $stmt->execute([$token]);
        $item = $stmt->fetch();

        if ($item && !empty($item['target_url'])) {
            // อัปเดตยอดคลิก/สแกน
            $upd = $pdo->prepare("UPDATE qr_items SET clicks = clicks + 1 WHERE id = ?");
            $upd->execute([$item['id']]);

            // บันทึก Log
            $log = $pdo->prepare("INSERT INTO qr_logs (qr_id, action, ip_address, user_agent) VALUES (?, 'scan_redirect', ?, ?)");
            $log->execute([$item['id'], $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '']);

            $targetUrl = $item['target_url'];
            header("Location: " . $targetUrl);
            exit;
        }
    } catch (Exception $e) {}
}

if (empty($targetUrl)) {
    header("Location: index.php");
    exit;
}
