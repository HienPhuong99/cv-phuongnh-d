<?php
/**
 * TRANG QUẢN LÝ TÀI KHOẢN ADMIN & ĐỔI MẬT KHẨU
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Csrf.php';
require_once __DIR__ . '/../app/Repository.php';
require_once __DIR__ . '/../app/helpers.php';

Auth::requireAuth();

$user = Auth::user();
$userId = Auth::id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::validateRequest();
    $action = $_POST['action'] ?? '';
    $ip = Auth::getClientIp();

    if ($action === 'update_profile') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $fullName = trim($_POST['full_name'] ?? '');

        if (empty($username) || empty($email) || empty($fullName)) {
            flash('error', 'Vui lòng điền đầy đủ các thông tin bắt buộc.');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Địa chỉ email không hợp lệ.');
        } else {
            // Kiểm tra trùng username hoặc email với tài khoản khác
            $existing = Database::fetch("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?", [$username, $email, $userId]);
            if ($existing) {
                flash('error', 'Tên đăng nhập hoặc email này đã tồn tại trong hệ thống.');
            } else {
                Database::update('users', [
                    'username'  => $username,
                    'email'     => $email,
                    'full_name' => $fullName
                ], 'id = ?', [$userId]);

                $_SESSION['admin_username'] = $username;
                $_SESSION['admin_name'] = $fullName;

                flash('success', 'Đã cập nhật thông tin tài khoản thành công!');
            }
        }
        redirect('admin/account.php');
    } elseif ($action === 'change_password') {
        // Kiểm tra rate limiting theo IP trước khi xử lý
        $lockout = Auth::isLockedOut($ip);
        if ($lockout !== false) {
            $minutes = ceil($lockout / 60);
            flash('error', "Địa chỉ IP của bạn tạm thời bị khóa do nhập sai mật khẩu quá 5 lần. Vui lòng thử lại sau {$minutes} phút.");
            redirect('admin/account.php');
        }

        $currentPassword = (string)($_POST['current_password'] ?? '');
        $newPassword     = (string)($_POST['new_password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');

        $userData = Database::fetch("SELECT id, username, password_hash FROM users WHERE id = ?", [$userId]);

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            flash('error', 'Vui lòng điền đầy đủ mật khẩu hiện tại và mật khẩu mới.');
            redirect('admin/account.php');
        } elseif (!$userData || empty($userData['password_hash']) || !password_verify($currentPassword, $userData['password_hash'])) {
            // Ghi nhận lần thử sai vào login_attempts
            Auth::recordAttempt($ip, $userData['username'] ?? 'admin', false);
            flash('error', 'Mật khẩu hiện tại không chính xác.');
            redirect('admin/account.php');
        } elseif ($currentPassword === $newPassword) {
            flash('error', 'Mật khẩu mới không được trùng với mật khẩu hiện tại.');
            redirect('admin/account.php');
        } elseif (mb_strlen($newPassword, 'UTF-8') < 10) {
            flash('error', 'Mật khẩu mới phải có ít nhất 10 ký tự.');
            redirect('admin/account.php');
        } elseif ($newPassword !== $confirmPassword) {
            flash('error', 'Xác nhận mật khẩu mới không khớp.');
            redirect('admin/account.php');
        } else {
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            Database::update('users', ['password_hash' => $newHash], 'id = ?', [$userId]);

            // Ghi nhận thành công
            Auth::recordAttempt($ip, $userData['username'] ?? 'admin', true);

            // Đăng xuất và chuyển hướng về trang login với thông báo
            Auth::logout();
            flash('success', 'Đổi mật khẩu thành công! Vui lòng đăng nhập lại với mật khẩu mới.');
            redirect('admin/login.php');
        }
    }

    redirect('admin/account.php');
}

$pageTitle = 'Tài khoản Quản trị';
$activeMenu = 'account';
require_once __DIR__ . '/partials/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h2 class="h3 fw-bold text-white mb-1">Tài khoản Quản trị</h2>
    <p class="text-secondary small mb-0">Cập nhật thông tin cá nhân và thay đổi mật khẩu đăng nhập CMS</p>
  </div>
</div>

<div class="row g-4">
  <!-- 1. Form Cập nhật thông tin -->
  <div class="col-lg-6">
    <div class="card-admin h-100">
      <div class="card-admin-header">
        <h5 class="mb-0 fw-bold text-white"><i class="bi bi-person-gear text-warning me-2"></i>Thông tin tài khoản</h5>
      </div>
      <div class="p-4">
        <form method="POST" action="account.php">
          <?= Csrf::field() ?>
          <input type="hidden" name="action" value="update_profile">

          <div class="mb-3">
            <label class="form-label" for="acc_name">Họ và tên <span class="text-danger">*</span></label>
            <input type="text" name="full_name" id="acc_name" class="form-control" value="<?= e($user['full_name'] ?? '') ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label" for="acc_username">Tên đăng nhập (Username) <span class="text-danger">*</span></label>
            <input type="text" name="username" id="acc_username" class="form-control" value="<?= e($user['username'] ?? '') ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label" for="acc_email">Địa chỉ Email <span class="text-danger">*</span></label>
            <input type="email" name="email" id="acc_email" class="form-control" value="<?= e($user['email'] ?? '') ?>" required>
          </div>

          <div class="p-3 rounded bg-dark border border-secondary border-opacity-25 small mb-4">
            <div class="text-secondary">Lần đăng nhập cuối: <strong class="text-light"><?= $user['last_login_at'] ? date('d/m/Y H:i:s', strtotime($user['last_login_at'])) : 'Lần đầu' ?></strong></div>
            <div class="text-secondary">Ngày tạo tài khoản: <strong class="text-light"><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></strong></div>
          </div>

          <button type="submit" class="btn btn-gold">
            <i class="bi bi-check-lg me-1"></i> Lưu thông tin
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- 2. Form Đổi mật khẩu -->
  <div class="col-lg-6">
    <div class="card-admin h-100">
      <div class="card-admin-header">
        <h5 class="mb-0 fw-bold text-white"><i class="bi bi-key text-warning me-2"></i>Đổi mật khẩu</h5>
      </div>
      <div class="p-4">
        <form method="POST" action="account.php">
          <?= Csrf::field() ?>
          <input type="hidden" name="action" value="change_password">

          <div class="mb-3">
            <label class="form-label" for="current_password">Mật khẩu hiện tại <span class="text-danger">*</span></label>
            <input type="password" name="current_password" id="current_password" class="form-control" placeholder="••••••••" required>
          </div>

          <div class="mb-3">
            <label class="form-label" for="new_password">Mật khẩu mới <span class="text-danger">*</span></label>
            <input type="password" name="new_password" id="new_password" class="form-control" placeholder="Ít nhất 10 ký tự" required minlength="10">
          </div>

          <div class="mb-4">
            <label class="form-label" for="confirm_password">Nhập lại mật khẩu mới <span class="text-danger">*</span></label>
            <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="••••••••" required minlength="10">
          </div>

          <div class="alert alert-warning py-2 px-3 small border-0 mb-4" style="background-color: rgba(255, 193, 7, 0.15); color: #FFD56B;">
            <i class="bi bi-shield-exclamation me-1"></i>
            Mật khẩu mới phải có tối thiểu 10 ký tự và khác mật khẩu cũ. Sau khi đổi mật khẩu, bạn sẽ cần đăng nhập lại.
          </div>

          <button type="submit" class="btn btn-gold">
            <i class="bi bi-shield-check me-1"></i> Cập nhật mật khẩu
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
