<?php
/**
 * Lớp Csrf bảo vệ phòng chống tấn công Cross-Site Request Forgery
 */

class Csrf {
    private const SESSION_KEY = '_csrf_token';

    /**
     * Lấy token hiện tại hoặc tạo mới nếu chưa có (Dành cho Admin session-based)
     */
    public static function getToken(): string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::SESSION_KEY];
    }

    /**
     * Tạo input hidden cho form HTML Admin
     */
    public static function field(): string {
        $token = self::getToken();
        return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Tạo meta tag cho các request AJAX trong Admin
     */
    public static function meta(): string {
        $token = self::getToken();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Kiểm tra token hợp lệ trong Session Admin
     */
    public static function verify(?string $token): bool {
        if (empty($token) || empty($_SESSION[self::SESSION_KEY])) {
            return false;
        }

        return hash_equals($_SESSION[self::SESSION_KEY], $token);
    }

    /**
     * Kiểm tra request Admin hiện tại (chặn Open Redirect ở HTTP_REFERER)
     */
    public static function validateRequest(): bool {
        $token = $_POST['_csrf_token'] 
            ?? $_SERVER['HTTP_X_CSRF_TOKEN'] 
            ?? $_SERVER['HTTP_CSRF_TOKEN'] 
            ?? null;

        if (!self::verify($token)) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                http_response_code(403);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'CSRF Token không hợp lệ hoặc đã hết hạn!']);
                exit;
            }

            flash('error', 'Yêu cầu không hợp lệ hoặc phiên làm việc đã hết hạn (CSRF Token mismatch).');
            
            // Chặn Open Redirect an toàn
            $redirectUrl = url('admin/index.php');
            if (!empty($_SERVER['HTTP_REFERER'])) {
                $parsedReferer = parse_url($_SERVER['HTTP_REFERER']);
                $refererHost = $parsedReferer['host'] ?? '';
                $currentHost = $_SERVER['HTTP_HOST'] ?? '';
                
                // Tách port ra nếu có
                $currentHostName = explode(':', $currentHost)[0];
                $refererHostName = explode(':', $refererHost)[0];
                
                if ($refererHostName !== '' && strcasecmp($refererHostName, $currentHostName) === 0) {
                    $redirectUrl = $_SERVER['HTTP_REFERER'];
                }
            }

            header('Location: ' . $redirectUrl);
            exit;
        }

        return true;
    }

    // =========================================================================
    // STATELESS PUBLIC TOKEN (Dành cho Form liên hệ Public - Không dùng Session)
    // =========================================================================

    /**
     * Sinh token HMAC dựa trên timestamp và APP_KEY / Salt
     */
    public static function generatePublicToken(int $timestamp): string {
        $salt = defined('APP_KEY') ? APP_KEY : 'e3a93b_cv_public_salt_2026';
        return hash_hmac('sha256', $timestamp . '|public_contact_form|' . $salt, $salt);
    }

    /**
     * Xác thực token HMAC của form public kèm kiểm tra time-trap (>= 3s và <= 2h)
     */
    public static function verifyPublicToken(?string $token, int $timestamp, int $minSeconds = 3, int $maxSeconds = 7200): bool {
        if (empty($token) || $timestamp <= 0) {
            return false;
        }

        $now = time();
        $elapsed = $now - $timestamp;

        // Chặn nếu thời gian render ở tương lai bất thường hoặc submit quá nhanh (< 3 giây)
        if ($timestamp > $now + 10 || $elapsed < $minSeconds) {
            return false;
        }

        // Chặn nếu form đã hết hạn (quá 2 giờ)
        if ($elapsed > $maxSeconds) {
            return false;
        }

        $expectedToken = self::generatePublicToken($timestamp);
        return hash_equals($expectedToken, $token);
    }
}
