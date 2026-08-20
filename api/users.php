<?php
// api/users.php - REST API จัดการข้อมูลผู้ใช้งาน
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

requireLogin();

$pdo = getDBConnection();
$currentUser = getLoggedInUser();

// ตรวจสอบสิทธิ์ API - อนุญาตเฉพาะ "ผู้ดูแลระบบสูงสุด" หรือ "ผู้ดูแลระบบ"
if (!in_array($currentUser['role'], ['ผู้ดูแลระบบสูงสุด', 'ผู้ดูแลระบบ'])) {
    echo json_encode(['success' => false, 'message' => 'Permission denied: คุณไม่มีสิทธิ์จัดการผู้ใช้งาน']);
    exit;
}
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

$response = ['success' => false, 'message' => 'Invalid action'];

try {
    switch ($action) {
        case 'list':
            $stmt = $pdo->query("SELECT id, username, fullname, email, role, avatar, oauth_provider, status, created_at FROM admins ORDER BY id DESC");
            $list = $stmt->fetchAll();
            foreach ($list as &$u) {
                $u['created_at_thai'] = formatThaiDateTime($u['created_at']);
            }
            $response = ['success' => true, 'data' => $list];
            break;

        case 'get':
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) throw new Exception("รหัสผู้ใช้ไม่ถูกต้อง");

            $stmt = $pdo->prepare("SELECT id, username, fullname, email, role, avatar, oauth_provider, status, created_at FROM admins WHERE id = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetch();
            if ($user) {
                $response = ['success' => true, 'data' => $user];
            } else {
                $response = ['success' => false, 'message' => 'ไม่พบผู้ใช้งาน'];
            }
            break;

        case 'create':
            $fullname = trim($_POST['fullname'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $role = trim($_POST['role'] ?? 'เจ้าหน้าที่');
            $status = trim($_POST['status'] ?? 'active');

            if (empty($fullname) || empty($username) || empty($password)) {
                throw new Exception("กรุณากรอกชื่อ-นามสกุล ชื่อผู้ใช้ และรหัสผ่าน");
            }

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("ชื่อผู้ใช้นี้มีอยู่ในระบบแล้ว");
            }

            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $ins = $pdo->prepare("INSERT INTO admins (username, password, fullname, email, role, avatar, oauth_provider, status, created_at) VALUES (?, ?, ?, ?, ?, 'assets/images/default-avatar.png', 'local', ?, NOW())");
            $ins->execute([$username, $hashed, $fullname, $email, $role, $status]);

            $response = ['success' => true, 'message' => 'เพิ่มผู้ใช้งานเรียบร้อยแล้ว!'];
            break;

        case 'update':
            $id = (int)($_POST['id'] ?? 0);
            $fullname = trim($_POST['fullname'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $role = trim($_POST['role'] ?? 'เจ้าหน้าที่');
            $status = trim($_POST['status'] ?? 'active');
            $password = trim($_POST['password'] ?? '');

            if ($id <= 0) throw new Exception("รหัสผู้ใช้ไม่ถูกต้อง");
            if (empty($fullname)) throw new Exception("กรุณากรอกชื่อ-นามสกุล");

            if (!empty($password)) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE admins SET fullname = ?, email = ?, role = ?, status = ?, password = ? WHERE id = ?");
                $stmt->execute([$fullname, $email, $role, $status, $hashed, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE admins SET fullname = ?, email = ?, role = ?, status = ? WHERE id = ?");
                $stmt->execute([$fullname, $email, $role, $status, $id]);
            }

            $response = ['success' => true, 'message' => 'บันทึกการแก้ไขข้อมูลผู้ใช้งานเรียบร้อยแล้ว!'];
            break;

        case 'delete':
            $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
            if ($id <= 0) throw new Exception("รหัสผู้ใช้ไม่ถูกต้อง");

            // ป้องกันไม่ให้ลบตัวเอง
            if (isset($_SESSION['admin_id']) && (int)$_SESSION['admin_id'] === $id) {
                throw new Exception("ไม่สามารถลบบัญชีของตัวคุณเองที่กำลังเข้าสู่ระบบอยู่ได้");
            }

            $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ?");
            $stmt->execute([$id]);

            $response = ['success' => true, 'message' => 'ลบผู้ใช้งานเรียบร้อยแล้ว'];
            break;

        default:
            $response = ['success' => false, 'message' => 'Action not supported'];
            break;
    }
} catch (Exception $e) {
    $response = ['success' => false, 'message' => $e->getMessage()];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
