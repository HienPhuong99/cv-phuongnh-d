<?php
/**
 * Endpoint Tải CV PDF An Toàn từ Trang Công Khai
 * Đọc đường dẫn từ Database, kiểm tra file tồn tại, ghi nhận lượt tải và gửi header download
 */

define('NO_SESSION', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/Repository.php';
require_once __DIR__ . '/../app/helpers.php';

// 1. Lấy thông tin profile từ DB (tuyệt đối không nhận tham số GET từ client)
$profile = Repository::getProfile();

if (empty($profile['cv_pdf_file'])) {
    header('Location: ' . url('/'));
    exit;
}

$relativePath = ltrim($profile['cv_pdf_file'], '/\\');
$filePath = PUBLIC_PATH . '/' . $relativePath;

if (!file_exists($filePath) || !is_file($filePath)) {
    header('Location: ' . url('/'));
    exit;
}

// 2. Ghi nhận lượt tải vào bảng cv_downloads theo chuẩn ẩn danh
//    (không lưu IP thô / user-agent thô; hash đổi mới mỗi ngày)
try {
    Database::query(
        "INSERT INTO `cv_downloads` (`visitor_hash`, `device`, `is_bot`, `downloaded_at`) VALUES (?, ?, ?, NOW())",
        [visitor_hash(), detect_device(), is_bot() ? 1 : 0]
    );
} catch (\Throwable $e) {
    // Bỏ qua lỗi ghi log để không gián đoạn việc tải file của nhà tuyển dụng
}

// 3. Chuẩn hóa tên file tải về đẹp mắt: CV-{slug-ho-ten}.pdf
$slugName = !empty($profile['full_name']) ? slugify_vi($profile['full_name']) : 'Ung-Vien';
if (empty($slugName)) {
    $slugName = 'Ung-Vien';
}
$downloadFilename = 'CV-' . $slugName . '.pdf';

// 4. Gửi headers tải file
$fileSize = filesize($filePath);

header('Content-Description: File Transfer');
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $downloadFilename . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: private, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Content-Length: ' . $fileSize);

// Xóa mọi output buffer TRƯỚC khi đọc file: nếu hosting bật output_buffering thì
// toàn bộ PDF sẽ bị nạp vào RAM trước khi gửi đi. set_time_limit(0) phòng trường hợp
// đường truyền yếu làm script bị ngắt giữa chừng. Hai lệnh này phải nằm SAU các
// header() ở trên và NGAY TRƯỚC readfile().
if (ob_get_level()) {
    ob_end_clean();
}
set_time_limit(0);

readfile($filePath);
exit;
