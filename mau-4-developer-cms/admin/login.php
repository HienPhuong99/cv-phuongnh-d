<?php
/**
 * TRANG ĐĂNG NHẬP ADMIN (LOGIN)
 * Tích hợp CSRF + Rate Limiting 5 lần sai khóa 5 phút
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Csrf.php';
require_once __DIR__ . '/../app/helpers.php';

// Nếu đã đăng nhập thì chuyển vào Dashboard
if (Auth::check()) {
    redirect('admin/index.php');
}

$error = null;
$lockoutRemaining = Auth::isLockedOut();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($lockoutRemaining !== false) {
        $minutes = ceil($lockoutRemaining / 60);
        $error = "Hệ thống đang tạm khóa. Vui lòng thử lại sau {$minutes} phút.";
    } elseif (!Csrf::verify($_POST['_csrf_token'] ?? '')) {
        $error = 'Yêu cầu không hợp lệ hoặc phiên làm việc đã hết hạn (CSRF mismatch).';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        if (empty($username) || empty($password)) {
            $error = 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu.';
            set_old(['username' => $username]);
        } else {
            $res = Auth::login($username, $password);
            if ($res['success']) {
                clear_old();
                flash('success', 'Đăng nhập thành công! Chào mừng quay trở lại.');
                redirect('admin/index.php');
            } else {
                $error = $res['message'];
                set_old(['username' => $username]);
                $lockoutRemaining = Auth::isLockedOut();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Đăng nhập Quản trị — CV Mẫu 4</title>
  <!-- Local Vendor CSS: Bootstrap 5.3.3 & Bootstrap Icons 1.11.3 -->
  <link rel="stylesheet" href="<?= asset('vendor/bootstrap/bootstrap.min.css') ?>">
  <link rel="stylesheet" href="<?= asset('vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
  <!-- Local Self-hosted Fonts (Plus Jakarta Sans & Space Grotesk) -->
  <link rel="stylesheet" href="<?= asset('fonts/fonts.css') ?>">


  <style>
    body {
      font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      background-color: #0E0B08;
      color: #F4ECDF;

      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      overflow: hidden;
    }

    .ambient-glow {
      position: absolute;
      width: 500px;
      height: 500px;
      background: radial-gradient(circle, rgba(227, 169, 59, 0.18) 0%, rgba(210, 96, 58, 0.08) 50%, transparent 70%);
      filter: blur(100px);
      pointer-events: none;
      z-index: 0;
    }

    .login-card {
      background-color: #191309;
      border: 1px solid #33271A;
      border-radius: 20px;
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.8), 0 0 30px rgba(227, 169, 59, 0.1);
      position: relative;
      z-index: 1;
      width: 100%;
      max-width: 440px;
    }

    .form-control {
      background-color: #120E08 !important;
      border: 1px solid #33271A !important;
      color: #F4ECDF !important;
      border-radius: 10px;
      padding: 12px 14px;
    }

    .form-control:focus {
      border-color: #E3A93B !important;
      box-shadow: 0 0 0 3px rgba(227, 169, 59, 0.25) !important;
    }

    .btn-gold {
      background: linear-gradient(135deg, #E3A93B 0%, #F0BB55 100%);
      color: #0E0B08;
      font-weight: 700;
      border: none;
      border-radius: 10px;
      padding: 12px;
      transition: all 0.2s;
    }

    .btn-gold:hover:not(:disabled) {
      background: linear-gradient(135deg, #F0BB55 0%, #FFF0D0 100%);
      color: #0E0B08;
      box-shadow: 0 6px 25px rgba(227, 169, 59, 0.4);
      transform: translateY(-1px);
    }
  </style>
</head>
<body>

  <div class="ambient-glow"></div>

  <div class="container p-3">
    <div class="login-card p-4 p-sm-5 mx-auto">
      
      <div class="text-center mb-4">
        <div class="d-inline-flex align-items-center justify-content-center w-12 h-12 rounded-circle bg-warning bg-opacity-10 text-warning mb-3" style="width: 54px; height: 54px; border: 1px solid rgba(227, 169, 59, 0.3);">
          <i class="bi bi-shield-lock-fill fs-3"></i>
        </div>
        <h3 class="fw-bold mb-1" style="font-family: 'Space Grotesk', sans-serif; color: #F4ECDF;">ĐĂNG NHẬP CMS</h3>
        <p class="text-secondary small mb-0">Quản trị nội dung CV Mẫu 4 Developer</p>
      </div>

      <?php if (!empty($error)): ?>
        <div class="alert alert-danger d-flex align-items-center gap-2 py-2 px-3 small border-0" style="background-color: rgba(220, 53, 69, 0.15); color: #FF8080;" role="alert">
          <i class="bi bi-exclamation-triangle-fill"></i>
          <div><?= e($error) ?></div>
        </div>
      <?php endif; ?>

      <?php if ($lockoutRemaining !== false): ?>
        <div class="alert alert-warning py-2 px-3 small border-0 text-center" style="background-color: rgba(255, 193, 7, 0.15); color: #FFD56B;">
          <i class="bi bi-clock-history me-1"></i>
          Tài khoản bị khóa. Vui lòng quay lại sau <strong><?= ceil($lockoutRemaining / 60) ?> phút</strong>.
        </div>
      <?php endif; ?>

      <form method="POST" action="login.php" class="space-y-3">
        <?= Csrf::field() ?>

        <div class="mb-3">
          <label class="form-label small fw-semibold" style="color: #B3A488;" for="username">Tên đăng nhập hoặc Email</label>
          <div class="input-group">
            <span class="input-group-text border-0" style="background-color: #120E08; color: #B3A488; border: 1px solid #33271A !important; border-right: none !important;">
              <i class="bi bi-person"></i>
            </span>
            <input type="text" name="username" id="username" class="form-control" style="border-left: none !important;" value="<?= e(old('username', 'admin')) ?>" required autofocus placeholder="admin" <?= $lockoutRemaining !== false ? 'disabled' : '' ?>>
          </div>
        </div>

        <div class="mb-4">
          <label class="form-label small fw-semibold" style="color: #B3A488;" for="password">Mật khẩu</label>
          <div class="input-group">
            <span class="input-group-text border-0" style="background-color: #120E08; color: #B3A488; border: 1px solid #33271A !important; border-right: none !important;">
              <i class="bi bi-key"></i>
            </span>
            <input type="password" name="password" id="password" class="form-control" style="border-left: none !important;" required placeholder="••••••••" <?= $lockoutRemaining !== false ? 'disabled' : '' ?>>
          </div>
          <div class="form-text small" style="color: #7D705C;">Mặc định: <code>Admin@123</code></div>
        </div>

        <button type="submit" class="btn btn-gold w-100 fw-bold mb-3" <?= $lockoutRemaining !== false ? 'disabled' : '' ?>>
          <i class="bi bi-box-arrow-in-right me-1"></i>
          Đăng Nhập
        </button>

        <div class="text-center">
          <a href="<?= url() ?>" class="text-decoration-none small" style="color: #B3A488;">
            <i class="bi bi-arrow-left me-1"></i> Quay lại trang chủ CV
          </a>
        </div>
      </form>

    </div>
  </div>

</body>
</html>
