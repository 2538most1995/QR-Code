<?php
// admin/oauth_callback.php - รับ Callback Token Exchange จริงจาก Google และ LINE Login
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/oauth_config.php';

$pdo = getDBConnection();
$provider = strtolower($_GET['provider'] ?? 'google');
$code = $_GET['code'] ?? '';
$state = $_GET['state'] ?? '';
$error = $_GET['error'] ?? '';
$errorDescription = $_GET['error_description'] ?? '';

if (!empty($error)) {
    die("<div style='font-family: sans-serif; padding: 25px; background: #fee2e2; border: 1px solid #ef4444; border-radius: 16px; max-width: 500px; margin: 50px auto; text-align: center;'>
        <h3 style='color: #b91c1c; margin-top: 0;'>⚠️ เข้าสู่ระบบไม่สำเร็จ</h3>
        <p style='color: #4b5563; font-size: 14px;'>ข้อความจากระบบ: " . htmlspecialchars($errorDescription ?: $error) . "</p>
        <a href='login.php' style='display: inline-block; margin-top: 15px; padding: 8px 18px; background: #2563eb; color: white; border-radius: 8px; text-decoration: none; font-size: 13px;'>กลับหน้าเข้าสู่ระบบ</a>
    </div>");
}

if (empty($code)) {
    header("Location: login.php");
    exit;
}

try {
    if ($provider === 'google') {
        $google = getGoogleOAuthSettings();
        if (empty($google['client_id']) || empty($google['client_secret'])) {
            throw new Exception("ยังไม่ได้ตั้งค่า Google Client ID หรือ Client Secret ในระบบ");
        }

        // 1. Exchange Authorization Code for Access Token
        $tokenUrl = 'https://oauth2.googleapis.com/token';
        $tokenData = [
            'code'          => $code,
            'client_id'     => $google['client_id'],
            'client_secret' => $google['client_secret'],
            'redirect_uri'  => $google['redirect_uri'],
            'grant_type'    => 'authorization_code'
        ];

        $tokenRes = httpPost($tokenUrl, $tokenData);
        if (isset($tokenRes['error'])) {
            throw new Exception("Google Token Error: " . ($tokenRes['error_description'] ?? $tokenRes['error']));
        }

        $accessToken = $tokenRes['access_token'] ?? '';
        if (empty($accessToken)) {
            throw new Exception("ไม่ได้รับ Access Token จาก Google");
        }

        // 2. Fetch User Profile from Google
        $userInfoUrl = 'https://www.googleapis.com/oauth2/v3/userinfo';
        $userInfo = httpGet($userInfoUrl, ["Authorization: Bearer {$accessToken}"]);

        if (empty($userInfo['sub'])) {
            throw new Exception("ไม่สามารถดึงข้อมูลโปรไฟล์ผู้ใช้จาก Google ได้");
        }

        $oauthId = 'google_' . $userInfo['sub'];
        $fullname = $userInfo['name'] ?? 'ผู้ใช้งาน Google';
        $email = $userInfo['email'] ?? '';
        $avatar = $userInfo['picture'] ?? 'https://lh3.googleusercontent.com/a/default-user=s96-c';
        $username = 'g_' . (!empty($email) ? explode('@', $email)[0] : $userInfo['sub']);

    } elseif ($provider === 'line') {
        $line = getLineOAuthSettings();
        if (empty($line['channel_id']) || empty($line['channel_secret'])) {
            throw new Exception("ยังไม่ได้ตั้งค่า LINE Channel ID หรือ Channel Secret ในระบบ");
        }

        // 1. Exchange Authorization Code for Access Token
        $tokenUrl = 'https://api.line.me/oauth2/v2.1/token';
        $tokenData = [
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => $line['redirect_uri'],
            'client_id'     => $line['channel_id'],
            'client_secret' => $line['channel_secret']
        ];

        $tokenRes = httpPost($tokenUrl, $tokenData);
        if (isset($tokenRes['error'])) {
            throw new Exception("LINE Token Error: " . ($tokenRes['error_description'] ?? $tokenRes['error']));
        }

        $accessToken = $tokenRes['access_token'] ?? '';
        if (empty($accessToken)) {
            throw new Exception("ไม่ได้รับ Access Token จาก LINE");
        }

        // 2. Fetch User Profile from LINE
        $profileUrl = 'https://api.line.me/v2/profile';
        $profile = httpGet($profileUrl, ["Authorization: Bearer {$accessToken}"]);

        if (empty($profile['userId'])) {
            throw new Exception("ไม่สามารถดึงข้อมูลโปรไฟล์ผู้ใช้จาก LINE ได้");
        }

        $oauthId = 'line_' . $profile['userId'];
        $fullname = $profile['displayName'] ?? 'ผู้ใช้งาน LINE';
        $avatar = $profile['pictureUrl'] ?? '../assets/images/icons/icon-line.svg';
        $email = '';
        $username = 'line_' . substr($profile['userId'], 0, 8);

    } else {
        throw new Exception("ไม่รองรับ Social Provider นี้");
    }

    // 3. Upsert User in MySQL Database
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE oauth_provider = ? AND oauth_id = ?");
    $stmt->execute([$provider, $oauthId]);
    $user = $stmt->fetch();

    if ($user) {
        // Update user avatar and fullname if changed
        $update = $pdo->prepare("UPDATE admins SET fullname = ?, avatar = ?, email = IF(? != '', ?, email), updated_at = NOW() WHERE id = ?");
        $update->execute([$fullname, $avatar, $email, $email, $user['id']]);
    } else {
        // Unique username check
        $checkUser = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE username = ?");
        $checkUser->execute([$username]);
        if ($checkUser->fetchColumn() > 0) {
            $username .= '_' . rand(10, 99);
        }

        $oauthPasswordHash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
        $ins = $pdo->prepare("INSERT INTO admins (username, password, fullname, email, role, avatar, oauth_provider, oauth_id, status, created_at) VALUES (?, ?, ?, ?, 'เจ้าหน้าที่', ?, ?, ?, 'active', NOW())");
        $ins->execute([$username, $oauthPasswordHash, $fullname, $email, $avatar, $provider, $oauthId]);
        $newId = $pdo->lastInsertId();

        $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
        $stmt->execute([$newId]);
        $user = $stmt->fetch();
    }

    // Check if account is suspended
    if ($user['status'] === 'inactive') {
        throw new Exception("บัญชีผู้ใช้งานนี้ถูกระงับสิทธิ์ชั่วคราว กรุณาติดต่อผู้ดูแลระบบ");
    }

    // 4. Save Session & Log in
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_id'] = $user['id'];
    $_SESSION['admin_username'] = $user['username'];
    $_SESSION['admin_fullname'] = $user['fullname'];
    $_SESSION['admin_role'] = $user['role'];
    $_SESSION['admin_avatar'] = $user['avatar'];
    $_SESSION['admin_provider'] = $user['oauth_provider'];

    header("Location: ../index.php");
    exit;

} catch (Exception $e) {
    die("<div style='font-family: sans-serif; padding: 25px; background: #fee2e2; border: 1px solid #ef4444; border-radius: 16px; max-width: 550px; margin: 50px auto; text-align: center;'>
        <h3 style='color: #b91c1c; margin-top: 0;'>⚠️ เกิดข้อผิดพลาดในการเชื่อมต่อ OAuth</h3>
        <p style='color: #4b5563; font-size: 14px; line-height: 1.5;'>" . htmlspecialchars($e->getMessage()) . "</p>
        <div style='margin-top: 20px; display: flex; justify-content: center; gap: 10px;'>
            <a href='settings.php#oauth_section' style='padding: 8px 16px; background: #2563eb; color: white; border-radius: 8px; text-decoration: none; font-size: 13px;'>ไปตั้งค่า API Keys</a>
            <a href='login.php' style='padding: 8px 16px; background: #e5e7eb; color: #374151; border-radius: 8px; text-decoration: none; font-size: 13px;'>กลับหน้าเข้าสู่ระบบ</a>
        </div>
    </div>");
}
