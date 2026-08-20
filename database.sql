-- Database: db_sgr_qrcode
CREATE DATABASE IF NOT EXISTS `db_sgr_qrcode` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `db_sgr_qrcode`;

-- Table: admins
CREATE TABLE IF NOT EXISTS `admins` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `fullname` VARCHAR(100) NOT NULL,
    `role` VARCHAR(50) DEFAULT 'ผู้ดูแลระบบ',
    `avatar` VARCHAR(255) DEFAULT 'assets/images/default-avatar.png',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: personnel
CREATE TABLE IF NOT EXISTS `personnel` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `card_id` VARCHAR(30) NOT NULL,
    `fullname` VARCHAR(150) NOT NULL,
    `position` VARCHAR(100) NOT NULL,
    `department` VARCHAR(150) NOT NULL,
    `phone` VARCHAR(30) NULL,
    `email` VARCHAR(100) NULL,
    `photo` VARCHAR(255) NULL,
    `qr_color` VARCHAR(30) DEFAULT '#0284c7',
    `qr_style` VARCHAR(30) DEFAULT 'blue',
    `link_profile` TINYINT(1) DEFAULT 1,
    `is_permanent` TINYINT(1) DEFAULT 1,
    `access_level` VARCHAR(50) DEFAULT 'public',
    `token` VARCHAR(64) NOT NULL UNIQUE,
    `created_by` VARCHAR(100) DEFAULT 'ผู้ดูแลระบบ',
    `status` VARCHAR(30) DEFAULT 'active',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: qr_logs
CREATE TABLE IF NOT EXISTS `qr_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `personnel_id` INT NULL,
    `action` VARCHAR(50) NOT NULL,
    `ip_address` VARCHAR(50) NULL,
    `user_agent` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX (`personnel_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: settings
CREATE TABLE IF NOT EXISTS `settings` (
    `setting_key` VARCHAR(50) PRIMARY KEY,
    `setting_value` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default Admin Account (Username: admin / Password: admin1234)
INSERT INTO `admins` (`username`, `password`, `fullname`, `role`, `avatar`)
VALUES ('admin', '$2y$10$wL4R1EaV1rX6l7tX0o1k.OqZtTfPsmW9bXqB/9jA9L8f2m.aTjCqC', 'ผู้ดูแลระบบ', 'ผู้ดูแลระบบ', 'assets/images/default-avatar.png')
ON DUPLICATE KEY UPDATE `username`=`username`;
