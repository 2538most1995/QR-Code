<?php
// api/personnel.php
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
                $stmt = $pdo->prepare("SELECT * FROM personnel WHERE fullname LIKE ? OR card_id LIKE ? OR department LIKE ? OR position LIKE ? ORDER BY id DESC LIMIT ?");
                $term = "%{$search}%";
                $stmt->bindValue(1, $term, PDO::PARAM_STR);
                $stmt->bindValue(2, $term, PDO::PARAM_STR);
                $stmt->bindValue(3, $term, PDO::PARAM_STR);
                $stmt->bindValue(4, $term, PDO::PARAM_STR);
                $stmt->bindValue(5, $limit, PDO::PARAM_INT);
                $stmt->execute();
            } else {
                $stmt = $pdo->prepare("SELECT * FROM personnel ORDER BY id DESC LIMIT ?");
                $stmt->bindValue(1, $limit, PDO::PARAM_INT);
                $stmt->execute();
            }
            
            $list = $stmt->fetchAll();
            // Format thai dates for display
            foreach ($list as &$item) {
                $item['created_at_thai'] = formatThaiDateTime($item['created_at']);
            }
            
            $response = ['success' => true, 'data' => $list];
            break;

        case 'get':
            $id = $_GET['id'] ?? 0;
            $token = $_GET['token'] ?? '';
            
            if ($id > 0) {
                $stmt = $pdo->prepare("SELECT * FROM personnel WHERE id = ?");
                $stmt->execute([$id]);
            } elseif ($token !== '') {
                $stmt = $pdo->prepare("SELECT * FROM personnel WHERE token = ?");
                $stmt->execute([$token]);
            } else {
                throw new Exception("ไม่พบรหัสบุคลากร");
            }
            
            $person = $stmt->fetch();
            if ($person) {
                $person['created_at_thai'] = formatThaiDateTime($person['created_at']);
                $response = ['success' => true, 'data' => $person];
            } else {
                $response = ['success' => false, 'message' => 'ไม่พบข้อมูลบุคลากร'];
            }
            break;

        case 'create':
            $card_id = trim($_POST['card_id'] ?? '');
            $fullname = trim($_POST['fullname'] ?? '');
            $position = trim($_POST['position'] ?? 'ครู');
            $department = trim($_POST['department'] ?? 'สำนักงาน สกร.');
            $phone = trim($_POST['phone'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $qr_color = $_POST['qr_color'] ?? '#0284c7';
            $qr_style = $_POST['qr_style'] ?? 'blue';
            $link_profile = isset($_POST['link_profile']) ? 1 : 0;
            $is_permanent = isset($_POST['is_permanent']) ? 1 : 0;
            $access_level = $_POST['access_level'] ?? 'public';
            
            if (empty($card_id) || empty($fullname) || empty($department)) {
                throw new Exception("กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน (เลขประจำตัว, ชื่อ-สกุล, หน่วยงาน)");
            }

            // จัดการอัปโหลดรูปภาพ
            $photoPath = 'assets/uploads/sample_person1.png';
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['photo'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                if (!in_array($ext, $allowed)) {
                    throw new Exception("รองรับเฉพาะไฟล์รูปภาพ JPG, PNG, WEBP เท่านั้น");
                }
                if ($file['size'] > 5 * 1024 * 1024) {
                    throw new Exception("ขนาดไฟล์ต้องไม่เกิน 5MB");
                }
                $newFilename = 'person_' . uniqid() . '.' . $ext;
                $target = __DIR__ . '/../assets/uploads/' . $newFilename;
                if (move_uploaded_file($file['tmp_name'], $target)) {
                    $photoPath = 'assets/uploads/' . $newFilename;
                }
            } elseif (!empty($_POST['existing_photo'])) {
                $photoPath = $_POST['existing_photo'];
            }

            $token = 'sgr_' . bin2hex(random_bytes(12));
            $created_by = $_SESSION['admin_fullname'] ?? 'ผู้ดูแลระบบ';

            $stmt = $pdo->prepare("INSERT INTO personnel (card_id, fullname, position, department, phone, email, photo, qr_color, qr_style, link_profile, is_permanent, access_level, token, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$card_id, $fullname, $position, $department, $phone, $email, $photoPath, $qr_color, $qr_style, $link_profile, $is_permanent, $access_level, $token, $created_by]);
            
            $newId = $pdo->lastInsertId();

            // บันทึก Log
            $logStmt = $pdo->prepare("INSERT INTO qr_logs (personnel_id, action, ip_address, user_agent) VALUES (?, 'create_qr', ?, ?)");
            $logStmt->execute([$newId, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '']);

            $response = [
                'success' => true,
                'message' => 'บันทึกและสร้าง QR-Code สำเร็จแล้ว',
                'id' => $newId,
                'token' => $token,
                'photo' => $photoPath
            ];
            break;

        case 'update':
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) throw new Exception("รหัสบุคลากรไม่ถูกต้อง");

            $card_id = trim($_POST['card_id'] ?? '');
            $fullname = trim($_POST['fullname'] ?? '');
            $position = trim($_POST['position'] ?? 'ครู');
            $department = trim($_POST['department'] ?? 'สำนักงาน สกร.');
            $phone = trim($_POST['phone'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $qr_color = $_POST['qr_color'] ?? '#0284c7';
            $qr_style = $_POST['qr_style'] ?? 'blue';
            $access_level = $_POST['access_level'] ?? 'public';

            if (empty($card_id) || empty($fullname)) {
                throw new Exception("กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน");
            }

            // ตรวจสอบรูปภาพใหม่
            $photoUpdateSql = "";
            $params = [$card_id, $fullname, $position, $department, $phone, $email, $qr_color, $qr_style, $access_level];

            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['photo'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                if (in_array($ext, $allowed) && $file['size'] <= 5 * 1024 * 1024) {
                    $newFilename = 'person_' . uniqid() . '.' . $ext;
                    $target = __DIR__ . '/../assets/uploads/' . $newFilename;
                    if (move_uploaded_file($file['tmp_name'], $target)) {
                        $photoUpdateSql = ", photo = ?";
                        $params[] = 'assets/uploads/' . $newFilename;
                    }
                }
            }

            $params[] = $id;
            $stmt = $pdo->prepare("UPDATE personnel SET card_id = ?, fullname = ?, position = ?, department = ?, phone = ?, email = ?, qr_color = ?, qr_style = ?, access_level = ? {$photoUpdateSql} WHERE id = ?");
            $stmt->execute($params);

            $response = ['success' => true, 'message' => 'แก้ไขข้อมูลสำเร็จ'];
            break;

        case 'delete':
            $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
            if ($id <= 0) throw new Exception("รหัสบุคลากรไม่ถูกต้อง");

            $stmt = $pdo->prepare("DELETE FROM personnel WHERE id = ?");
            $stmt->execute([$id]);

            $response = ['success' => true, 'message' => 'ลบข้อมูลบุคลากรเรียบร้อยแล้ว'];
            break;

        default:
            $response = ['success' => false, 'message' => 'Action not supported'];
            break;
    }
} catch (Exception $e) {
    $response = ['success' => false, 'message' => $e->getMessage()];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
