<?php
/**
 * File cấu hình mẫu cho CV Mẫu 4 Developer CMS
 * Sao chép file này thành config.php và điều chỉnh thông tin kết nối DB & APP_KEY.
 */

// Thiết lập múi giờ mặc định
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Cấu hình Database (ưu tiên biến môi trường, fallback giá trị mặc định)
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'cv_mau4_developer');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_CHARSET', 'utf8mb4');

// Khóa bí mật ứng dụng (Bắt buộc đổi sang chuỗi ngẫu nhiên tối thiểu 32 ký tự)
// Sinh khóa mới bằng lệnh: php -r "echo bin2hex(random_bytes(32));"
define('APP_KEY', getenv('APP_KEY') ?: 'CHANGE_ME_SECRET_APP_KEY_AT_LEAST_32_CHARS_LONG');

// Tin cậy Reverse Proxy / Cloudflare để lấy IP thực (Mặc định: false)
define('TRUST_PROXY', false);

// Đường dẫn gốc BASE_URL (Ví dụ: http://localhost/mau-4-developer-cms hoặc http://yourdomain.com)
// Để trống '' để hệ thống tự động nhận diện theo request
define('BASE_URL', getenv('BASE_URL') ?: '');

// Cấu hình môi trường (development / production)
define('APP_ENV', getenv('APP_ENV') ?: 'development');

// Đường dẫn thư mục
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('UPLOAD_URL', 'uploads');

// Cấu hình Session bảo mật (Chỉ khởi chạy session khi không có cờ NO_SESSION)
if (!defined('NO_SESSION') && session_status() === PHP_SESSION_NONE) {
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    session_set_cookie_params([
        'lifetime' => 86400 * 7, // 7 ngày
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}
