-- ==========================================================
-- Migration: Đánh số lại sort_order bảng `sections`, cách nhau 10
-- Lý do: strengths và weaknesses đang cùng sort_order = 3 -> MySQL không đảm bảo
-- thứ tự khi giá trị bằng nhau. Đánh lại 10/20/30... để chèn section mới sau này
-- không phải đánh số lại. weaknesses đặt ngay sau strengths.
-- ==========================================================

UPDATE `sections` SET `sort_order` = 10 WHERE `key` = 'hero';
UPDATE `sections` SET `sort_order` = 20 WHERE `key` = 'about';
UPDATE `sections` SET `sort_order` = 30 WHERE `key` = 'skills';
UPDATE `sections` SET `sort_order` = 40 WHERE `key` = 'strengths';
UPDATE `sections` SET `sort_order` = 50 WHERE `key` = 'weaknesses';
UPDATE `sections` SET `sort_order` = 60 WHERE `key` = 'experience';
UPDATE `sections` SET `sort_order` = 70 WHERE `key` = 'education';
UPDATE `sections` SET `sort_order` = 80 WHERE `key` = 'contact';
