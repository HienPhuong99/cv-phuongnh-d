<?php
/**
 * Lớp Auth quản lý đăng nhập, xác thực và rate-limiting theo IP
 */

class Auth {
    private const MAX_ATTEMPTS = 5;
    private const WINDOW_MINUTES = 15; // Cửa sổ kiểm tra 15 phút
    private const LOCKOUT_TIME = 300;  // Khóa 5 phút (300s)

    // Dummy hash cố định nhằm chống Timing Attack phân biệt username tồn tại hay không
    private const DUMMY_HASH = '$2y$12$e8J3m7U2lJ8O1gTz5Q1i9u9O1tO8wO7wO7wO7wO7wO7wO7wO7wO7w';

    /**
     * Kiểm tra trạng thái đăng nhập
     */
    public static function check(): bool {
        return !empty($_SESSION['admin_logged_in']) && !empty($_SESSION['admin_user_id']);
    }

    /**
     * Lấy ID người dùng hiện tại
     */
    public static function id(): ?int {
        return self::check() ? (int)$_SESSION['admin_user_id'] : null;
    }

    /**
     * Lấy thông tin người dùng hiện tại từ Database
     */
    public static function user(): ?array {
        $id = self::id();
        if (!$id) {
            return null;
        }

        return Database::fetch("SELECT id, username, email, full_name, last_login_at, created_at FROM users WHERE id = ?", [$id]);
    }

    /**
     * Lấy địa chỉ IP của Client an toàn
     */
    public static function getClientIp(): string {
        return function_exists('client_ip') ? client_ip() : ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    /**
     * Kiểm tra xem IP hiện tại có đang bị khóa do nhập sai nhiều lần hay không
     */
    public static function isLockedOut(?string $ip = null): int|false {
        $clientIp = $ip ?? self::getClientIp();

        try {
            $row = Database::fetch(
                "SELECT COUNT(*) as fail_count, MAX(attempted_at) as last_attempt 
                 FROM `login_attempts` 
                 WHERE `ip` = ? AND `success` = 0 AND `attempted_at` >= NOW() - INTERVAL " . self::WINDOW_MINUTES . " MINUTE",
                [$clientIp]
            );

            $failCount = (int)($row['fail_count'] ?? 0);
            if ($failCount >= self::MAX_ATTEMPTS && !empty($row['last_attempt'])) {
                $lastTime = strtotime($row['last_attempt']);
                $unlockTime = $lastTime + self::LOCKOUT_TIME;
                $remaining = $unlockTime - time();
                if ($remaining > 0) {
                    return $remaining;
                }
            }
        } catch (Throwable $e) {
            error_log("Lỗi isLockedOut: " . $e->getMessage());
        }

        return false;
    }

    /**
     * Ghi nhận một lần thử đăng nhập vào Database
     */
    public static function recordAttempt(string $ip, ?string $username, bool $success): void {
        try {
            Database::insert('login_attempts', [
                'ip'           => $ip,
                'username'     => $username !== null ? mb_substr($username, 0, 100, 'UTF-8') : null,
                'attempted_at' => date('Y-m-d H:i:s'),
                'success'      => $success ? 1 : 0
            ]);
        } catch (Throwable $e) {
            error_log("Lỗi recordAttempt: " . $e->getMessage());
        }
    }

    /**
     * Dọn dẹp bản ghi cũ hơn 7 ngày trong login_attempts (1/100 request)
     */
    public static function cleanupOldAttempts(): void {
        if (mt_rand(1, 100) === 1) {
            try {
                Database::query("DELETE FROM `login_attempts` WHERE `attempted_at` < NOW() - INTERVAL 7 DAY");
            } catch (Throwable $e) {
                error_log("Lỗi cleanupOldAttempts: " . $e->getMessage());
            }
        }
    }

    /**
     * Thực hiện đăng nhập với Rate Limiting theo IP và Dummy password_verify
     */
    public static function login(string $username, string $password): array {
        self::cleanupOldAttempts();
        $ip = self::getClientIp();

        // 1. Kiểm tra lockout theo IP
        $remaining = self::isLockedOut($ip);
        if ($remaining !== false) {
            $minutes = ceil($remaining / 60);
            return [
                'success' => false,
                'message' => "Địa chỉ IP của bạn tạm thời bị khóa do nhập sai quá 5 lần. Vui lòng thử lại sau {$minutes} phút."
            ];
        }

        // 2. Tìm kiếm user
        $user = Database::fetch(
            "SELECT id, username, email, password_hash, full_name FROM `users` WHERE `username` = ? OR `email` = ? LIMIT 1",
            [$username, $username]
        );

        $isValid = false;
        if ($user) {
            $isValid = password_verify($password, $user['password_hash']);
        } else {
            // Chạy dummy verify để thời gian phản hồi không làm lộ username có tồn tại hay không
            password_verify($password, self::DUMMY_HASH);
        }

        // 3. Ghi log attempt
        self::recordAttempt($ip, $username, $isValid);

        // 4. Xử lý kết quả
        if ($isValid && $user) {
            // Tái tạo session ID ngăn Session Fixation
            session_regenerate_id(true);

            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_user_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_name'] = $user['full_name'];

            // Cập nhật last_login_at
            Database::update('users', ['last_login_at' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);

            // Xóa các lần thử sai trước đó của IP này khi đăng nhập thành công
            try {
                Database::query("DELETE FROM `login_attempts` WHERE `ip` = ? AND `success` = 0", [$ip]);
            } catch (Throwable $e) {
                error_log("Lỗi xóa failed attempts: " . $e->getMessage());
            }

            return [
                'success' => true,
                'message' => 'Đăng nhập thành công!'
            ];
        }


        // Đăng nhập thất bại -> Kiểm tra số lần còn lại
        $failRow = Database::fetch(
            "SELECT COUNT(*) as cnt FROM `login_attempts` 
             WHERE `ip` = ? AND `success` = 0 AND `attempted_at` >= NOW() - INTERVAL " . self::WINDOW_MINUTES . " MINUTE",
            [$ip]
        );
        $failCount = (int)($failRow['cnt'] ?? 0);

        if ($failCount >= self::MAX_ATTEMPTS) {
            return [
                'success' => false,
                'message' => 'Bạn đã nhập sai 5 lần liên tiếp. Hệ thống tạm thời khóa IP của bạn trong 5 phút.'
            ];
        }

        $remainingAttempts = self::MAX_ATTEMPTS - $failCount;
        return [
            'success' => false,
            'message' => "Tên đăng nhập hoặc mật khẩu không chính xác. Bạn còn {$remainingAttempts} lần thử."
        ];
    }

    /**
     * Đăng xuất và khởi tạo phiên sạch mới
     */
    public static function logout(): void {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();

        // Khởi tạo phiên sạch mới để flash message hoạt động
        session_start();
        session_regenerate_id(true);
    }


    /**
     * Yêu cầu bắt buộc đăng nhập, chuyển hướng nếu chưa đăng nhập
     */
    public static function requireAuth(): void {
        if (!self::check()) {
            flash('error', 'Vui lòng đăng nhập để tiếp tục.');
            redirect('admin/login.php');
        }
    }
}
