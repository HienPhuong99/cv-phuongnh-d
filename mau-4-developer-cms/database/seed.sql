-- ==============================================================================
-- DATABASE SEED DATA: CV MẪU 4 DEVELOPER CMS
-- Trích xuất chính xác 100% từ template templates/mau-4-developer
-- Tài khoản quản trị mặc định: admin / Admin@123
-- ==============================================================================

SET NAMES utf8mb4;

-- 1. Tài khoản Admin mặc định
INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `full_name`, `created_at`) 
VALUES (1, 'admin', 'admin@example.com', '$2y$12$9UpsbnCHmPEZ8rwBOuLFMe0s0MyJqHiOdkz.t5TrltqaGgcAhlPWO', 'Quản trị viên', NOW())
ON DUPLICATE KEY UPDATE `username` = VALUES(`username`);

-- 2. Hồ sơ cá nhân (Hiền Phương)
INSERT INTO `profile` (
  `id`, `full_name`, `job_title`, `status_badge`, `pre_title`, `tagline`, `avatar`, 
  `floating_badge_title`, `floating_badge_subtitle`, `email`, `phone`, `phone_display`, `zalo_url`, 
  `address`, `about_quote`, `about_subtext`, `commitment_1_title`, `commitment_1_desc`, 
  `commitment_2_title`, `commitment_2_desc`, `contact_heading`, `contact_subtext`, `cv_pdf_file`
) VALUES (
  1,
  'HIỀN PHƯƠNG',
  'Nhân Viên Kinh Doanh',
  'SẴN SÀNG NHẬN VIỆC • TP. THỦ ĐỨC, TP.HCM',
  'Hello, I\'m',
  'Nhân viên kinh doanh với hơn 3 năm kinh nghiệm thực chiến tư vấn bán hàng đa kênh, mở rộng tệp khách hàng tiềm năng (B2B/B2C) và quản lý đơn hàng trên sàn thương mại điện tử.',
  'assets/hien-phuong.png',
  'HIỀN PHƯƠNG',
  'B2B / B2C Sales',
  'hphuong123123@gmail.com',
  '0876488047',
  '087 6488 047',
  'https://zalo.me/0876488047',
  'Trường Thọ, TP. Thủ Đức, TP.HCM',
  '“Trong vòng 3 tháng đầu làm việc, tôi đặt mục tiêu trở thành nhân viên chính thức bằng cách đạt doanh số tối thiểu công ty đề ra. Đồng thời, tôi sẽ mở rộng mạng lưới khách hàng tiềm năng bằng cách tiếp cận và thiết lập mối quan hệ với ít nhất 10 khách hàng mới mỗi tháng.”',
  'Tôi cũng chủ động tìm hiểu và áp dụng các kỹ năng bán hàng hiệu quả để nâng cao năng suất và đạt được kết quả tốt nhất trong công việc.',
  'Cam kết chỉ tiêu',
  'Bám sát KPI doanh số và tiến độ công việc',
  'Mở rộng tệp khách hàng',
  'Thiết lập quan hệ với 10+ đối tác mới/tháng',
  'Sẵn sàng đồng hành cùng doanh nghiệp đạt mục tiêu doanh số.',
  'Quý nhà tuyển dụng có thể liên hệ trực tiếp với tôi qua các kênh bên dưới để trao đổi chi tiết hơn về cơ hội hợp tác.',
  NULL
) ON DUPLICATE KEY UPDATE `full_name` = VALUES(`full_name`);

-- 3. Cấu hình Section
INSERT INTO `sections` (`id`, `key`, `badge_code`, `title`, `subtitle`, `is_visible`, `sort_order`) VALUES
(1, 'hero', '', 'Đầu trang & Giới thiệu', '', 1, 0),
(2, 'about', '01 // MỤC TIÊU NGHỀ NGHIỆP', 'Về tôi & Kế hoạch hành động', '', 1, 1),
(3, 'skills', '02 // NĂNG LỰC CHUYÊN MÔN', 'Kỹ Năng Cốt Lõi', 'Bộ kỹ năng tư vấn, đàm phán và vận hành kinh doanh thực tế.', 1, 2),
(4, 'strengths', '03 // PHẨM CHẤT NỔI BẬT', 'Điểm Mạnh & Kỷ Luật Công Việc', '', 1, 3),
(5, 'experience', '04 // HÀNH TRÌNH THỰC CHIẾN', 'Kinh Nghiệm Làm Việc', '', 1, 4),
(6, 'education', '05 // NỀN TẢNG & CÔNG CỤ', 'Học Vấn & Công Cụ Làm Việc', '', 1, 5),
(7, 'contact', '06 // KẾT NỐI & HỢP TÁC', 'Liên Hệ Trực Tiếp', '', 1, 6)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);

-- 4. Cài đặt hệ thống (Settings)
INSERT INTO `settings` (`key`, `value`) VALUES
('site_title', 'Hiền Phương — Nhân Viên Kinh Doanh | Dark Gold Portfolio & CV'),
('meta_description', 'Portfolio & CV cá nhân của Hiền Phương - Nhân Viên Kinh Doanh với hơn 3 năm kinh nghiệm thực chiến tư vấn bán hàng đa kênh, mở rộng tệp khách hàng B2B/B2C và quản lý đơn hàng sàn TMĐT.'),
('author', 'Hiền Phương'),
('theme_color', '#0E0B08'),
('monogram', 'HP'),
('footer_title', 'HIỀN PHƯƠNG — Nhân Viên Kinh Doanh'),
('footer_text', 'Thiết kế phong cách Modern Dark Gold Developer với HTML + Tailwind CSS. All rights reserved &copy; 2026.'),
('google_analytics_id', '')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

-- 5. Chỉ số thống kê (Key Stats)
INSERT INTO `key_stats` (`id`, `value`, `label`, `subtext`, `accent_color`, `is_active`, `sort_order`) VALUES
(1, '3+', 'Năm thực chiến', 'Kinh doanh & Tư vấn bán hàng', 'gold', 1, 1),
(2, '10+', 'KH mới / tháng', 'Mục tiêu phát triển tệp khách hàng', 'terracotta', 1, 2),
(3, '100%', 'Cam kết KPI', 'Đạt định mức trong 3 tháng đầu', 'goldHover', 1, 3),
(4, 'Đa kênh', 'Khai thác', 'B2B, Social & Sàn TMĐT', 'bronze', 1, 4)
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

-- 6. Kỹ năng cốt lõi (Skills)
INSERT INTO `skills` (`id`, `title`, `description`, `icon`, `accent_color`, `tags`, `is_active`, `sort_order`) VALUES
(1, 'Kỹ Năng Giao Tiếp', 'Truyền đạt thông tin rõ ràng, mạch lạc; chủ động lắng nghe và thấu hiểu sâu sắc mong muốn của khách hàng.', 'message-square', 'gold', '["Lắng nghe chủ động", "Giao tiếp đa kênh", "Thấu cảm"]', 1, 1),
(2, 'Tư Vấn & Thuyết Phục', 'Nắm vững kiến thức sản phẩm/dịch vụ, đưa ra giải pháp tối ưu phù hợp với từng nhu cầu và ngân sách của khách hàng.', 'star', 'terracotta', '["Tư vấn giải pháp", "Đàm phán báo giá", "Chốt đơn"]', 1, 2),
(3, 'Tìm Kiếm Khách Hàng', 'Hiểu rõ thị trường mục tiêu, xác định tệp khách hàng tiềm năng và khai thác qua Social, Trang Vàng, Google Maps.', 'search', 'goldHover', '["Trang Vàng B2B", "Google Maps", "Social Media"]', 1, 3),
(4, 'Vận Hành & Đơn Hàng', 'Cập nhật thông tin sản phẩm, quản lý đơn hàng Shopee/Lazada, theo dõi công nợ và chăm sóc khách hàng sau bán.', 'shopping-cart', 'bronze', '["Shopee / Lazada", "Thu hồi công nợ", "After-sales"]', 1, 4)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);

-- 7. Điểm mạnh / Phẩm chất nổi bật (Strengths)
INSERT INTO `strengths` (`id`, `title`, `description`, `icon`, `accent_color`, `is_active`, `sort_order`) VALUES
(1, 'Chịu Áp Lực Cao', 'Khả năng làm việc tốt dưới áp lực chỉ tiêu doanh số và tiến độ công việc, duy trì năng lượng tích cực và sự tập trung cao độ.', 'shield', 'gold', 1, 1),
(2, 'Kiên Nhẫn & Bền Bỉ', 'Chủ động theo sát, chăm sóc và nuôi dưỡng tệp khách hàng tiềm năng, không nản lòng trước các thương vụ kéo dài.', 'activity', 'terracotta', 1, 2),
(3, 'Tinh Thần Tự Học Hỏi', 'Nhanh nhạy cập nhật kiến thức sản phẩm mới, tiếp thu nhanh các công cụ số và không ngừng trau dồi kỹ năng chuyên môn.', 'book-open', 'goldHover', 1, 3)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);

-- 8. Kinh nghiệm làm việc (Experiences)
INSERT INTO `experiences` (`id`, `position`, `company`, `period_text`, `description`, `tags`, `accent_color`, `is_active`, `sort_order`) VALUES
(
  1,
  'Nhân Viên Kinh Doanh',
  'Công Ty TNHH Kinh Doanh Siêu Việt',
  '04/2021 — 12/2024 (3 năm 8 tháng)',
  'Chủ động tìm kiếm, mở rộng tệp khách hàng tiềm năng qua các nền tảng mạng xã hội, e-mail,…\nTư vấn bán hàng, giải đáp các thắc mắc và khiếu nại của khách hàng về sản phẩm/dịch vụ qua Zalo, Facebook, Hotline.\nCập nhật thông tin sản phẩm và quản lý đơn hàng trên website cùng các sàn thương mại điện tử (Shopee, Lazada).\nLập báo cáo định kỳ theo tháng về doanh số bán hàng và tình hình kinh doanh.\nSoạn thảo hợp đồng và làm báo giá cho thuê máy photocopy theo mẫu có sẵn.\nThiết kế hình ảnh, tem dán mừng các dịp lễ/tết và chương trình ưu đãi theo mẫu trên Canva.',
  '["Tư vấn B2B", "Sàn Shopee/Lazada", "Hợp đồng kinh tế", "Canva Design"]',
  'gold',
  1,
  1
),
(
  2,
  'Nhân Viên Bán Hàng — Trực Page',
  'Hộ Kinh Doanh 79 Store',
  '03/2025 — 07/2025 (5 tháng)',
  'Trực page, tư vấn bán hàng, giải đáp thắc mắc và khiếu nại của khách hàng qua Facebook, Zalo.\nTheo dõi, nhắc nhở và thu hồi công nợ đối với khách hàng mua trả góp đúng hạn.\nChăm sóc khách hàng sau bán hàng nhằm duy trì mối quan hệ và tăng tỷ lệ khách hàng quay lại.',
  '["Trực Page FB/Zalo", "Quản lý công nợ", "Chăm sóc sau bán"]',
  'terracotta',
  1,
  2
),
(
  3,
  'Nhân Viên Kinh Doanh (Thử Việc)',
  'Công ty TNHH Mr.Rin Group',
  '08/2025 — 09/2025',
  'Chăm sóc, chào hàng qua tin nhắn tệp khách hàng tiềm năng theo data công ty bàn giao.\nChủ động học hỏi, nắm bắt đặc tính kỹ thuật sản phẩm và nhận biết chính xác mã hàng.',
  '["Xử lý Data nóng", "Nắm bắt thông số kỹ thuật"]',
  'goldHover',
  1,
  3
),
(
  4,
  'Nhân Viên Kinh Doanh (Thử Việc)',
  'Công ty TNHH TM Điện Thái Dương — Thadeco',
  '09/2025 — 11/2025',
  'Tìm kiếm và khai thác danh sách khách hàng doanh nghiệp qua Trang Vàng, Google Maps.\nTư vấn giải pháp sản phẩm và gửi báo giá chi tiết cho khách hàng qua Zalo.',
  '["Trang Vàng B2B", "Google Maps", "Báo giá Zalo"]',
  'bronze',
  1,
  4
)
ON DUPLICATE KEY UPDATE `position` = VALUES(`position`);

-- 9. Học vấn (Educations)
INSERT INTO `educations` (`id`, `school`, `degree`, `major`, `period_text`, `description`, `footer_text`, `is_active`, `sort_order`) VALUES
(
  1,
  'Cao Đẳng Thực Hành',
  '',
  'IT - Phần Mềm • Lập trình di động',
  '07/2017 — 09/2020',
  'Trang bị tư duy logic vững vàng, khả năng tiếp cận và ứng dụng nhanh chóng các công nghệ, phần mềm quản lý kinh doanh, quản trị sàn TMĐT và nền tảng số hiện đại.',
  'Tư duy logic • Khả năng tiếp cận công nghệ số nhanh',
  1,
  1
)
ON DUPLICATE KEY UPDATE `school` = VALUES(`school`);

-- 10. Công cụ làm việc (Tools)
INSERT INTO `tools` (`id`, `name`, `description`, `icon`, `accent_color`, `is_active`, `sort_order`) VALUES
(1, 'Canva Pro', 'Thiết kế hình ảnh, tem dán mừng các dịp lễ/tết và banner chương trình ưu đãi bán hàng.', 'edit', 'gold', 1, 1),
(2, 'Shopee & Lazada', 'Quản trị thông tin sản phẩm, xử lý đơn hàng và theo dõi vận chuyển sàn thương mại điện tử.', 'shopping-cart', 'terracotta', 1, 2),
(3, 'MS Excel & Word', 'Lập báo cáo doanh số, soạn thảo hợp đồng kinh tế và làm báo giá chi tiết cho khách hàng.', 'file-text', 'goldHover', 1, 3),
(4, 'Zalo OA & Fanpage', 'Trực page, tư vấn khách hàng, giải đáp thắc mắc và chăm sóc khách hàng sau bán hàng.', 'message-circle', 'bronze', 1, 4)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);
