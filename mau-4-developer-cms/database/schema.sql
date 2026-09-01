-- ==============================================================================
-- DATABASE SCHEMA: CV MẪU 4 DEVELOPER CMS
-- MySQL 5.7+ / MariaDB
-- Charset: utf8mb4 / Collation: utf8mb4_unicode_ci
-- ==============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Bảng Quản trị viên
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(191) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `last_login_at` DATETIME NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Bảng Hồ sơ cá nhân (Single-row profile)
CREATE TABLE IF NOT EXISTS `profile` (
  `id` INT PRIMARY KEY DEFAULT 1,
  `full_name` VARCHAR(100) NOT NULL,
  `job_title` VARCHAR(150) NOT NULL,
  `status_badge` VARCHAR(200) DEFAULT 'SẴN SÀNG NHẬN VIỆC • TP. THỦ ĐỨC, TP.HCM',
  `pre_title` VARCHAR(50) DEFAULT 'Hello, I\'m',
  `tagline` TEXT NULL,
  `avatar` VARCHAR(255) NULL,
  `floating_badge_title` VARCHAR(100) DEFAULT 'HIỀN PHƯƠNG',
  `floating_badge_subtitle` VARCHAR(100) DEFAULT 'B2B / B2C Sales',
  `email` VARCHAR(191) NULL,
  `phone` VARCHAR(50) NULL,
  `phone_display` VARCHAR(50) NULL,
  `zalo_url` VARCHAR(255) NULL,
  `address` VARCHAR(255) NULL,
  `about_quote` TEXT NULL,
  `about_subtext` TEXT NULL,
  `commitment_1_title` VARCHAR(150) NULL,
  `commitment_1_desc` VARCHAR(255) NULL,
  `commitment_2_title` VARCHAR(150) NULL,
  `commitment_2_desc` VARCHAR(255) NULL,
  `contact_heading` VARCHAR(255) NULL,
  `contact_subtext` TEXT NULL,
  `cv_pdf_file` VARCHAR(255) NULL,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Bảng Quản lý các Section trên trang
CREATE TABLE IF NOT EXISTS `sections` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `key` VARCHAR(50) NOT NULL UNIQUE,
  `badge_code` VARCHAR(50) NULL,
  `title` VARCHAR(150) NOT NULL,
  `subtitle` VARCHAR(255) NULL,
  `is_visible` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT NOT NULL DEFAULT 0,
  INDEX `idx_sections_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Bảng Cài đặt hệ thống & SEO
CREATE TABLE IF NOT EXISTS `settings` (
  `key` VARCHAR(50) PRIMARY KEY,
  `value` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Bảng Chỉ số thống kê (Key Stats)
CREATE TABLE IF NOT EXISTS `key_stats` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `value` VARCHAR(50) NOT NULL,
  `label` VARCHAR(100) NOT NULL,
  `subtext` VARCHAR(255) NULL,
  `accent_color` VARCHAR(30) DEFAULT 'gold',
  `is_active` TINYINT(1) DEFAULT 1,
  `sort_order` INT DEFAULT 0,
  INDEX `idx_stats_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Bảng Kỹ năng cốt lõi (Skills)
CREATE TABLE IF NOT EXISTS `skills` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(150) NOT NULL,
  `description` TEXT NOT NULL,
  `icon` VARCHAR(50) DEFAULT 'message-square',
  `accent_color` VARCHAR(30) DEFAULT 'gold',
  `tags` TEXT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `sort_order` INT DEFAULT 0,
  INDEX `idx_skills_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Bảng Điểm mạnh / Phẩm chất nổi bật (Strengths)
CREATE TABLE IF NOT EXISTS `strengths` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(150) NOT NULL,
  `description` TEXT NOT NULL,
  `icon` VARCHAR(50) DEFAULT 'shield',
  `accent_color` VARCHAR(30) DEFAULT 'gold',
  `is_active` TINYINT(1) DEFAULT 1,
  `sort_order` INT DEFAULT 0,
  INDEX `idx_strengths_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Bảng Kinh nghiệm làm việc (Experiences)
CREATE TABLE IF NOT EXISTS `experiences` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `position` VARCHAR(150) NOT NULL,
  `company` VARCHAR(150) NOT NULL,
  `period_text` VARCHAR(100) NOT NULL,
  `description` TEXT NOT NULL,
  `tags` TEXT NULL,
  `accent_color` VARCHAR(30) DEFAULT 'gold',
  `is_active` TINYINT(1) DEFAULT 1,
  `sort_order` INT DEFAULT 0,
  INDEX `idx_exp_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Bảng Học vấn (Educations)
CREATE TABLE IF NOT EXISTS `educations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `school` VARCHAR(150) NOT NULL,
  `degree` VARCHAR(150) NULL,
  `major` VARCHAR(150) NULL,
  `period_text` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `footer_text` VARCHAR(255) NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `sort_order` INT DEFAULT 0,
  INDEX `idx_edu_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Bảng Công cụ làm việc (Tools)
CREATE TABLE IF NOT EXISTS `tools` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT NOT NULL,
  `icon` VARCHAR(50) DEFAULT 'tool',
  `accent_color` VARCHAR(30) DEFAULT 'gold',
  `is_active` TINYINT(1) DEFAULT 1,
  `sort_order` INT DEFAULT 0,
  INDEX `idx_tools_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Bảng Tin nhắn liên hệ gửi về
CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(191) NOT NULL,
  `phone` VARCHAR(50) NULL,
  `message` TEXT NOT NULL,
  `ip` VARCHAR(45) NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_messages_read` (`is_read`),
  INDEX `idx_messages_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Bảng Chống spam form liên hệ (Contact Throttle)
CREATE TABLE IF NOT EXISTS `contact_throttle` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ip` VARCHAR(45) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_throttle_ip` (`ip`),
  INDEX `idx_throttle_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Bảng Ghi nhận lần thử đăng nhập (Login Attempts Rate Limiting)
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ip` VARCHAR(45) NOT NULL,
  `username` VARCHAR(100) NULL,
  `attempted_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `success` TINYINT(1) DEFAULT 0,
  INDEX `idx_login_ip_time` (`ip`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

