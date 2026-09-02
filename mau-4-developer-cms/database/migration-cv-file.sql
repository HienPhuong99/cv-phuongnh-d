-- ==========================================================
-- Migration: Bổ sung các cột thông tin CV PDF và bảng thống kê lượt tải
-- Áp dụng cho cơ sở dữ liệu đã có dữ liệu mà không làm mất dữ liệu cũ
-- ==========================================================

-- 1. Bổ sung các cột lưu thông tin chi tiết của file CV vào bảng profile
ALTER TABLE `profile` 
  ADD COLUMN `cv_original_name` VARCHAR(255) NULL AFTER `cv_pdf_file`,
  ADD COLUMN `cv_uploaded_at` DATETIME NULL AFTER `cv_original_name`,
  ADD COLUMN `cv_size` INT NULL AFTER `cv_uploaded_at`;

-- 2. Thêm key 'content_changed_at' vào bảng settings để theo dõi nội dung cập nhật
INSERT INTO `settings` (`key`, `value`)
SELECT 'content_changed_at', NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM `settings` WHERE `key` = 'content_changed_at'
);

-- 3. Tạo bảng thống kê lượt tải file CV PDF từ trang công khai
CREATE TABLE IF NOT EXISTS `cv_downloads` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ip` VARCHAR(45) NULL,
  `user_agent` VARCHAR(255) NULL,
  `downloaded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_cv_downloaded_at` (`downloaded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
