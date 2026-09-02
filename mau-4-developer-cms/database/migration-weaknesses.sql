-- ==========================================================
-- Migration: Mục "Điểm cần cải thiện" (weaknesses)
-- Chức năng vẫn làm, nhưng MẶC ĐỊNH KHÔNG hiển thị ra trang công khai.
-- ==========================================================

-- 1. Bảng weaknesses (cấu trúc rút gọn: mỗi dòng là một ý ngắn)
CREATE TABLE IF NOT EXISTS `weaknesses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `content` VARCHAR(255) NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  INDEX `idx_weaknesses_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Section 'weaknesses' — is_visible = 0 (BẮT BUỘC mặc định ẩn).
--    sort_order = (sort_order của 'strengths') + 1 để nằm ngay dưới "Điểm mạnh"
--    dù DB đang đánh số kiểu cũ (…3,4,5) hay kiểu mới cách 10 (…40,50,60).
--    Migration `migration-sections-sort-order.sql` sẽ chuẩn hoá lại thành bội số của 10.
INSERT INTO `sections` (`key`, `badge_code`, `title`, `subtitle`, `is_visible`, `sort_order`)
SELECT 'weaknesses', '', 'Điểm cần cải thiện', '', 0,
       COALESCE((SELECT s.so FROM (SELECT `sort_order` AS so FROM `sections` WHERE `key` = 'strengths') s), 40) + 1
WHERE NOT EXISTS (SELECT 1 FROM `sections` WHERE `key` = 'weaknesses');
