-- ==========================================================
-- Migration: Chuyển bảng `cv_downloads` sang chuẩn riêng tư
-- Không lưu IP thô / user-agent thô. Chỉ lưu hash đổi mới mỗi ngày.
-- ==========================================================

-- 1. Xoá sạch dữ liệu thống kê cũ (IP thô không thể chuyển đổi ngược an toàn)
TRUNCATE TABLE `cv_downloads`;

-- 2. Bỏ các cột nhận dạng thô, thêm cột theo chuẩn ẩn danh
ALTER TABLE `cv_downloads`
  DROP COLUMN `ip`,
  DROP COLUMN `user_agent`,
  ADD COLUMN `visitor_hash` CHAR(64) NULL AFTER `id`,
  ADD COLUMN `device` ENUM('desktop','mobile','tablet') NULL AFTER `visitor_hash`,
  ADD COLUMN `is_bot` TINYINT(1) NOT NULL DEFAULT 0 AFTER `device`,
  ADD INDEX `idx_cv_visitor_hash` (`visitor_hash`);
