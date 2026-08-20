<?php
// config/database.php
// กำหนดค่าการเชื่อมต่อฐานข้อมูล (รองรับ MAMP Socket, Port 8889 และ Port 3306)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_NAME', 'db_sgr_qrcode');
define('DB_USER', 'root');
define('DB_PASS', 'root');

function getDBConnection() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $configs = [
        ['dsn' => 'mysql:unix_socket=/Applications/MAMP/tmp/mysql/mysql.sock', 'user' => 'root', 'pass' => 'root'],
        ['dsn' => 'mysql:host=127.0.0.1;port=8889', 'user' => 'root', 'pass' => 'root'],
        ['dsn' => 'mysql:host=localhost;port=8889', 'user' => 'root', 'pass' => 'root'],
        ['dsn' => 'mysql:host=127.0.0.1;port=3306', 'user' => 'root', 'pass' => 'root'],
        ['dsn' => 'mysql:host=127.0.0.1;port=3306', 'user' => 'root', 'pass' => ''],
        ['dsn' => 'mysql:host=localhost;port=3306', 'user' => 'root', 'pass' => '']
    ];

    $connected = false;
    $lastError = '';

    foreach ($configs as $cfg) {
        try {
            $temp_pdo = new PDO($cfg['dsn'] . ';charset=utf8mb4', $cfg['user'], $cfg['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 2
            ]);
            $temp_pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            $pdo = new PDO($cfg['dsn'] . ';dbname=' . DB_NAME . ';charset=utf8mb4', $cfg['user'], $cfg['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
            
            initSchemaIfNotExist($pdo);

            $connected = true;
            break;
        } catch (Exception $e) {
            $lastError = $e->getMessage();
            continue;
        }
    }

    if (!$connected) {
        die("<div style='font-family: sans-serif; padding: 20px; background: #fee2e2; border: 1px solid #ef4444; border-radius: 8px; max-width: 600px; margin: 40px auto;'>
            <h3 style='color: #b91c1c; margin-top: 0;'>⚠️ ไม่สามารถเชื่อมต่อฐานข้อมูล MySQL ได้</h3>
            <p>กรุณาตรวจสอบว่าเปิด MAMP หรือ MySQL Service แล้วหรือไม่</p>
            <small style='color: #6b7280;'>Error: " . htmlspecialchars($lastError) . "</small>
        </div>");
    }

    return $pdo;
}

function initSchemaIfNotExist($pdo) {
    // 1. ตาราง users / admins (รองรับ Local Password, Google OAuth, LINE OAuth)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `admins` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(50) NOT NULL UNIQUE,
        `password` VARCHAR(255) NULL,
        `fullname` VARCHAR(100) NOT NULL,
        `email` VARCHAR(100) NULL,
        `role` VARCHAR(50) DEFAULT 'เจ้าหน้าที่',
        `avatar` VARCHAR(255) DEFAULT 'assets/images/default-avatar.png',
        `oauth_provider` VARCHAR(30) DEFAULT 'local',
        `oauth_id` VARCHAR(100) NULL,
        `status` VARCHAR(20) DEFAULT 'active',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Auto-migrate columns if missing in `admins`
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM `admins`")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('email', $cols)) $pdo->exec("ALTER TABLE `admins` ADD COLUMN `email` VARCHAR(100) NULL AFTER `fullname`");
        if (!in_array('oauth_provider', $cols)) $pdo->exec("ALTER TABLE `admins` ADD COLUMN `oauth_provider` VARCHAR(30) DEFAULT 'local' AFTER `avatar`");
        if (!in_array('oauth_id', $cols)) $pdo->exec("ALTER TABLE `admins` ADD COLUMN `oauth_id` VARCHAR(100) NULL AFTER `oauth_provider`");
        if (!in_array('status', $cols)) $pdo->exec("ALTER TABLE `admins` ADD COLUMN `status` VARCHAR(20) DEFAULT 'active' AFTER `oauth_id`");
        if (!in_array('updated_at', $cols)) $pdo->exec("ALTER TABLE `admins` ADD COLUMN `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
    } catch (Exception $e) {}

    // 2. ตาราง qr_items
    $pdo->exec("CREATE TABLE IF NOT EXISTS `qr_items` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `title` VARCHAR(255) NOT NULL,
        `target_url` TEXT NOT NULL,
        `category` VARCHAR(100) DEFAULT 'ทั่วไป',
        `qr_color` VARCHAR(30) DEFAULT '#0284c7',
        `qr_style` VARCHAR(30) DEFAULT 'blue',
        `is_permanent` TINYINT(1) DEFAULT 1,
        `token` VARCHAR(64) NOT NULL UNIQUE,
        `clicks` INT DEFAULT 0,
        `created_by` VARCHAR(100) DEFAULT 'ผู้ดูแลระบบ',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 3. ตาราง qr_logs
    $pdo->exec("CREATE TABLE IF NOT EXISTS `qr_logs` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `qr_id` INT NULL,
        `action` VARCHAR(50) NOT NULL,
        `ip_address` VARCHAR(50) NULL,
        `user_agent` TEXT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX (`qr_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 4. ตาราง settings
    $pdo->exec("CREATE TABLE IF NOT EXISTS `settings` (
        `setting_key` VARCHAR(50) PRIMARY KEY,
        `setting_value` TEXT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // ค่าเริ่มต้นระบบ
    $defaults = [
        'org_short_name' => 'สกร.',
        'org_name' => 'สำนักงานส่งเสริมการเรียนรู้',
        'org_sub' => 'ประจำจังหวัด',
        'org_logo' => 'assets/images/logo-sgr.png'
    ];

    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM `settings` WHERE `setting_key` = ?");
    $insertStmt = $pdo->prepare("INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES (?, ?)");
    foreach ($defaults as $k => $v) {
        $checkStmt->execute([$k]);
        if ($checkStmt->fetchColumn() == 0) {
            $insertStmt->execute([$k, $v]);
        }
    }

    // เพิ่ม Admin เริ่มต้นถ้ายังไม่มี
    $stmt = $pdo->query("SELECT COUNT(*) FROM `admins`");
    if ($stmt->fetchColumn() == 0) {
        $hashedPass = password_hash('admin1234', PASSWORD_DEFAULT);
        $insertAdmin = $pdo->prepare("INSERT INTO `admins` (`username`, `password`, `fullname`, `email`, `role`, `avatar`, `oauth_provider`, `status`) VALUES (?, ?, ?, ?, ?, ?, 'local', 'active')");
        $insertAdmin->execute(['admin', $hashedPass, 'ผู้ดูแลระบบ สกร.', 'admin@sgr.go.th', 'ผู้ดูแลระบบสูงสุด', 'assets/images/default-avatar.png']);
    }
}

function getSystemSetting($key, $default = '') {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $val = $stmt->fetchColumn();
    return ($val !== false && $val !== null && $val !== '') ? $val : $default;
}

function setSystemSetting($key, $value) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    return $stmt->execute([$key, $value]);
}

function formatThaiDateTime($dateString) {
    if (!$dateString || $dateString == '0000-00-00 00:00:00') return '-';
    $time = strtotime($dateString);
    $thai_months = [
        1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.',
        5 => 'พ.ค.', 6 => 'มิ.ย.', 7 => 'ก.ค.', 8 => 'ส.ค.',
        9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.'
    ];
    $day = date('j', $time);
    $month = $thai_months[(int)date('n', $time)];
    $year = (int)date('Y', $time) + 543;
    $hourMin = date('H:i', $time);
    return "{$day} {$month} {$year} {$hourMin} น.";
}
