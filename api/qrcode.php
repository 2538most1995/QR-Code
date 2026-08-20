<?php
// api/qrcode.php - REST API สำหรับจัดการ QR Code และข้อมูลหน่วยงาน
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

$pdo = getDBConnection();
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

$response = ['success' => false, 'message' => 'Invalid action'];

try {
    switch ($action) {
        case 'list':
            $search = trim($_GET['search'] ?? '');
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
            
            if ($search !== '') {
                $stmt = $pdo->prepare("SELECT * FROM qr_items WHERE title LIKE ? OR target_url LIKE ? OR category LIKE ? ORDER BY id DESC LIMIT ?");
                $term = "%{$search}%";
                $stmt->bindValue(1, $term, PDO::PARAM_STR);
                $stmt->bindValue(2, $term, PDO::PARAM_STR);
                $stmt->bindValue(3, $term, PDO::PARAM_STR);
                $stmt->bindValue(4, $limit, PDO::PARAM_INT);
                $stmt->execute();
            } else {
                $stmt = $pdo->prepare("SELECT * FROM qr_items ORDER BY id DESC LIMIT ?");
                $stmt->bindValue(1, $limit, PDO::PARAM_INT);
                $stmt->execute();
            }
            
            $list = $stmt->fetchAll();
            foreach ($list as &$item) {
                $item['created_at_thai'] = formatThaiDateTime($item['created_at']);
            }
            
            $response = ['success' => true, 'data' => $list];
            break;

        case 'get':
            $id = (int)($_GET['id'] ?? 0);
            $token = $_GET['token'] ?? '';
            
            if ($id > 0) {
                $stmt = $pdo->prepare("SELECT * FROM qr_items WHERE id = ?");
                $stmt->execute([$id]);
            } elseif ($token !== '') {
                $stmt = $pdo->prepare("SELECT * FROM qr_items WHERE token = ?");
                $stmt->execute([$token]);
            } else {
                throw new Exception("ไม่พบรหัส QR");
            }
            
            $item = $stmt->fetch();
            if ($item) {
                $item['created_at_thai'] = formatThaiDateTime($item['created_at']);
                $response = ['success' => true, 'data' => $item];
            } else {
                $response = ['success' => false, 'message' => 'ไม่พบข้อมูล QR Code'];
            }
            break;

        case 'create':
            $title = trim($_POST['title'] ?? '');
            $target_url = trim($_POST['target_url'] ?? '');
            $category = trim($_POST['category'] ?? 'ลิงก์ทั่วไป');
            $qr_color = $_POST['qr_color'] ?? '#0284c7';
            $qr_style = $_POST['qr_style'] ?? 'blue';
            $is_permanent = isset($_POST['is_permanent']) ? 1 : 0;
            
            if (empty($title) || empty($target_url)) {
                throw new Exception("กรุณากรอก 'ชื่อ QR-Code' และ 'แนบลิงก์ URL' ให้ครบถ้วน");
            }

            if (!preg_match("~^(?:f|ht)tps?://~i", $target_url)) {
                $target_url = "https://" . $target_url;
            }

            $token = 'sgr_' . bin2hex(random_bytes(8));
            $created_by = $_SESSION['admin_fullname'] ?? 'ผู้ดูแลระบบ';

            $stmt = $pdo->prepare("INSERT INTO qr_items (title, target_url, category, qr_color, qr_style, is_permanent, token, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$title, $target_url, $category, $qr_color, $qr_style, $is_permanent, $token, $created_by]);
            
            $newId = $pdo->lastInsertId();

            try {
                $logStmt = $pdo->prepare("INSERT INTO qr_logs (qr_id, action, ip_address, user_agent) VALUES (?, 'create_qr', ?, ?)");
                $logStmt->execute([$newId, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '']);
            } catch (Exception $logErr) {}

            $response = [
                'success' => true,
                'message' => 'สร้าง QR-Code สำเร็จแล้ว!',
                'id' => $newId,
                'token' => $token,
                'title' => $title,
                'target_url' => $target_url
            ];
            break;

        case 'update':
            $id = (int)($_POST['id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $target_url = trim($_POST['target_url'] ?? '');
            $category = trim($_POST['category'] ?? 'ลิงก์ทั่วไป');
            $qr_color = $_POST['qr_color'] ?? '#0284c7';
            $qr_style = $_POST['qr_style'] ?? 'blue';

            if ($id <= 0) throw new Exception("ไม่พบรหัส QR ที่ต้องการแก้ไข");
            if (empty($title) || empty($target_url)) {
                throw new Exception("กรุณากรอก 'ชื่อ QR-Code' และ 'แนบลิงก์ URL'");
            }

            if (!preg_match("~^(?:f|ht)tps?://~i", $target_url)) {
                $target_url = "https://" . $target_url;
            }

            $stmt = $pdo->prepare("UPDATE qr_items SET title = ?, target_url = ?, category = ?, qr_color = ?, qr_style = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$title, $target_url, $category, $qr_color, $qr_style, $id]);

            $response = [
                'success' => true,
                'message' => 'แก้ไขข้อมูล QR-Code เรียบร้อยแล้ว!',
                'id' => $id,
                'title' => $title,
                'target_url' => $target_url
            ];
            break;

        case 'delete':
            $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
            if ($id <= 0) throw new Exception("รหัส QR ไม่ถูกต้อง");

            $stmt = $pdo->prepare("DELETE FROM qr_items WHERE id = ?");
            $stmt->execute([$id]);

            $response = ['success' => true, 'message' => 'ลบข้อมูล QR-Code เรียบร้อยแล้ว'];
            break;

        case 'update_settings':
            $shortName = trim($_POST['org_short_name'] ?? 'สกร.');
            $orgName = trim($_POST['org_name'] ?? 'สำนักงานส่งเสริมการเรียนรู้');
            $orgSub = trim($_POST['org_sub'] ?? 'ประจำจังหวัด');

            setSystemSetting('org_short_name', $shortName);
            setSystemSetting('org_name', $orgName);
            setSystemSetting('org_sub', $orgSub);

            // จัดการอัปโหลดไฟล์ตราสัญลักษณ์ถ้ามี
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
                }
            }

            $response = [
                'success' => true,
                'message' => 'บันทึกการตั้งค่าหน่วยงานและตราสัญลักษณ์เรียบร้อยแล้ว!',
                'org_short_name' => getSystemSetting('org_short_name', 'สกร.'),
                'org_name' => getSystemSetting('org_name', 'สำนักงานส่งเสริมการเรียนรู้'),
                'org_sub' => getSystemSetting('org_sub', 'ประจำจังหวัด'),
                'org_logo' => getSystemSetting('org_logo', 'assets/images/logo-sgr.png')
            ];
            break;

        case 'get_settings':
            $response = [
                'success' => true,
                'data' => [
                    'org_short_name' => getSystemSetting('org_short_name', 'สกร.'),
                    'org_name' => getSystemSetting('org_name', 'สำนักงานส่งเสริมการเรียนรู้'),
                    'org_sub' => getSystemSetting('org_sub', 'ประจำจังหวัด'),
                    'org_logo' => getSystemSetting('org_logo', 'assets/images/logo-sgr.png')
                ]
            ];
            break;

        default:
            $response = ['success' => false, 'message' => 'Action not supported'];
            break;
    }
} catch (Exception $e) {
    $response = ['success' => false, 'message' => $e->getMessage()];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
