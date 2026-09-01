<?php
/**
 * TRANG QUẢN LÝ HỒ SƠ & HERO & LIÊN HỆ
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Csrf.php';
require_once __DIR__ . '/../app/Upload.php';
require_once __DIR__ . '/../app/Repository.php';
require_once __DIR__ . '/../app/helpers.php';


Auth::requireAuth();

$profile = Repository::getProfile();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::validateRequest();

    $data = [
        'full_name'               => trim($_POST['full_name'] ?? ''),
        'job_title'               => trim($_POST['job_title'] ?? ''),
        'status_badge'            => trim($_POST['status_badge'] ?? ''),
        'pre_title'               => trim($_POST['pre_title'] ?? "Hello, I'm"),
        'tagline'                 => trim($_POST['tagline'] ?? ''),
        'floating_badge_title'    => trim($_POST['floating_badge_title'] ?? ''),
        'floating_badge_subtitle' => trim($_POST['floating_badge_subtitle'] ?? ''),
        'email'                   => trim($_POST['email'] ?? ''),
        'phone'                   => trim($_POST['phone'] ?? ''),
        'phone_display'           => trim($_POST['phone_display'] ?? ''),
        'zalo_url'                => trim($_POST['zalo_url'] ?? ''),
        'address'                 => trim($_POST['address'] ?? ''),
        'about_quote'             => trim($_POST['about_quote'] ?? ''),
        'about_subtext'           => trim($_POST['about_subtext'] ?? ''),
        'commitment_1_title'      => trim($_POST['commitment_1_title'] ?? ''),
        'commitment_1_desc'       => trim($_POST['commitment_1_desc'] ?? ''),
        'commitment_2_title'      => trim($_POST['commitment_2_title'] ?? ''),
        'commitment_2_desc'       => trim($_POST['commitment_2_desc'] ?? ''),
        'contact_heading'         => trim($_POST['contact_heading'] ?? ''),
        'contact_subtext'         => trim($_POST['contact_subtext'] ?? ''),
    ];

    // 1. Kiểm tra bắt buộc: full_name và job_title không được rỗng
    if (empty($data['full_name']) || empty($data['job_title'])) {
        set_old($_POST);
        flash('error', 'Họ và tên và Chức danh nghề nghiệp là bắt buộc, không được để trống.');
        redirect('admin/profile.php');
    }

    // 2. Xử lý upload ảnh đại diện mới nếu có (dừng hẳn nếu upload lỗi)
    if (!empty($_FILES['avatar']['name'])) {
        $uploadRes = Upload::processImage($_FILES['avatar'], 'avatar-' . Upload::slugify($data['full_name']));
        if ($uploadRes['success']) {
            if (!empty($profile['avatar'])) {
                Upload::deleteOldFile($profile['avatar']);
            }
            $data['avatar'] = $uploadRes['path'];
        } else {
            set_old($_POST);
            flash('error', $uploadRes['message']);
            redirect('admin/profile.php');
        }
    }

    // 3. Xử lý upload file CV PDF nếu có (dừng hẳn nếu upload lỗi)
    if (!empty($_FILES['cv_pdf']['name'])) {
        $pdfRes = Upload::processDocument($_FILES['cv_pdf'], 'cv-' . Upload::slugify($data['full_name']));
        if ($pdfRes['success']) {
            if (!empty($profile['cv_pdf_file'])) {
                Upload::deleteOldFile($profile['cv_pdf_file']);
            }
            $data['cv_pdf_file'] = $pdfRes['path'];
        } else {
            set_old($_POST);
            flash('error', $pdfRes['message']);
            redirect('admin/profile.php');
        }
    }

    // 4. Xử lý xóa CV PDF nếu người dùng chọn
    if (isset($_POST['remove_cv_pdf']) && $_POST['remove_cv_pdf'] == '1') {
        if (!empty($profile['cv_pdf_file'])) {
            Upload::deleteOldFile($profile['cv_pdf_file']);
        }
        $data['cv_pdf_file'] = null;
    }

    clear_old();
    Repository::updateProfile($data);
    flash('success', 'Đã lưu thay đổi hồ sơ thành công!');
    redirect('admin/profile.php');
}


$pageTitle = 'Chỉnh sửa Hồ sơ & Hero';
$activeMenu = 'profile';
require_once __DIR__ . '/partials/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h2 class="h3 fw-bold text-white mb-1">Chỉnh sửa Hồ sơ &amp; Hero</h2>
    <p class="text-secondary small mb-0">Thay đổi thông tin cá nhân, ảnh chân dung và các nội dung hiển thị nổi bật</p>
  </div>
  <a href="<?= url() ?>" target="_blank" class="btn btn-outline-warning btn-sm">
    <i class="bi bi-eye me-1"></i> Xem giao diện Public
  </a>
</div>

<form method="POST" action="profile.php" enctype="multipart/form-data">
  <?= Csrf::field() ?>

  <!-- 1. THÔNG TIN HERO & ĐẦU TRANG -->
  <div class="card-admin mb-4">
    <div class="card-admin-header">
      <h5 class="mb-0 fw-bold text-white"><i class="bi bi-person-lines-fill text-warning me-2"></i>1. Thông tin Hero &amp; Giới thiệu chính</h5>
    </div>
    <div class="p-4">
      <div class="row g-3 mb-3">
        <div class="col-md-6">
          <label class="form-label" for="full_name">Họ và tên hiển thị <span class="text-danger">*</span></label>
          <input type="text" name="full_name" id="full_name" class="form-control" value="<?= e($profile['full_name']) ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label" for="job_title">Chức danh nghề nghiệp <span class="text-danger">*</span></label>
          <input type="text" name="job_title" id="job_title" class="form-control" value="<?= e($profile['job_title']) ?>" required>
        </div>
      </div>

      <div class="row g-3 mb-3">
        <div class="col-md-4">
          <label class="form-label" for="pre_title">Tiêu đề phụ trước tên (Pre-title)</label>
          <input type="text" name="pre_title" id="pre_title" class="form-control" value="<?= e($profile['pre_title']) ?>" placeholder="Hello, I'm">
        </div>
        <div class="col-md-8">
          <label class="form-label" for="status_badge">Badge trạng thái làm việc</label>
          <input type="text" name="status_badge" id="status_badge" class="form-control" value="<?= e($profile['status_badge']) ?>" placeholder="SẴN SÀNG NHẬN VIỆC • TP. THỦ ĐỨC, TP.HCM">
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label" for="tagline">Đoạn giới thiệu tóm tắt (Tagline / Hero Paragraph)</label>
        <textarea name="tagline" id="tagline" rows="3" class="form-control"><?= e($profile['tagline']) ?></textarea>
      </div>

      <div class="row g-4 pt-2">
        <!-- Avatar Upload -->
        <div class="col-md-6">
          <label class="form-label">Ảnh chân dung đại diện (Avatar)</label>
          <div class="d-flex align-items-center gap-3">
            <img src="<?= upload_url($profile['avatar']) ?>" id="avatarPreview" alt="Avatar" class="avatar-preview">
            <div class="flex-grow-1">
              <input type="file" name="avatar" id="avatar" class="form-control mb-2" accept="image/jpeg,image/png,image/webp,image/gif" data-preview="avatarPreview">
              <div class="text-secondary small">Chấp nhận JPG, PNG, WEBP, GIF (Tối đa 5MB, tự động tối ưu hóa cạnh dài max 1600px).</div>
            </div>
          </div>
        </div>

        <!-- Floating Tag Badge on Avatar -->
        <div class="col-md-6">
          <label class="form-label">Thẻ nổi đè lên ảnh chân dung (Floating Badge)</label>
          <div class="row g-2">
            <div class="col-6">
              <input type="text" name="floating_badge_title" class="form-control" value="<?= e($profile['floating_badge_title']) ?>" placeholder="HIỀN PHƯƠNG">
              <div class="text-secondary small mt-1">Dòng trên (Tên)</div>
            </div>
            <div class="col-6">
              <input type="text" name="floating_badge_subtitle" class="form-control" value="<?= e($profile['floating_badge_subtitle']) ?>" placeholder="B2B / B2C Sales">
              <div class="text-secondary small mt-1">Dòng dưới (Lĩnh vực)</div>
            </div>
          </div>
        </div>
      </div>

      <!-- CV PDF Upload Option -->
      <div class="mt-4 pt-3 border-top border-secondary border-opacity-25">
        <label class="form-label" for="cv_pdf">Tải lên file CV định dạng PDF (Tùy chọn)</label>
        <div class="row g-3 align-items-center">
          <div class="col-md-7">
            <input type="file" name="cv_pdf" id="cv_pdf" class="form-control" accept="application/pdf">
            <div class="text-secondary small mt-1">Nếu tải lên file PDF, nút "In / PDF" trên thanh điều hướng sẽ mở/tải trực tiếp file này.</div>
          </div>
          <div class="col-md-5">
            <?php if (!empty($profile['cv_pdf_file'])): ?>
              <div class="d-flex align-items-center gap-2">
                <a href="<?= upload_url($profile['cv_pdf_file']) ?>" target="_blank" class="btn btn-sm btn-outline-info">
                  <i class="bi bi-file-earmark-pdf me-1"></i> Xem file hiện tại
                </a>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="remove_cv_pdf" value="1" id="remove_cv_pdf">
                  <label class="form-check-label small text-danger" for="remove_cv_pdf">Xóa file PDF</label>
                </div>
              </div>
            <?php else: ?>
              <span class="text-secondary small"><i class="bi bi-info-circle me-1"></i>Chưa có file PDF nào được tải lên (mặc định dùng chế độ in trình duyệt).</span>
            <?php endif; ?>
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- 2. THÔNG TIN LIÊN HỆ & MẠNG XÃ HỘI -->
  <div class="card-admin mb-4">
    <div class="card-admin-header">
      <h5 class="mb-0 fw-bold text-white"><i class="bi bi-telephone-outbound text-warning me-2"></i>2. Thông tin liên hệ</h5>
    </div>
    <div class="p-4">
      <div class="row g-3 mb-3">
        <div class="col-md-4">
          <label class="form-label" for="phone">Số điện thoại (dùng gọi tel:)</label>
          <input type="text" name="phone" id="phone" class="form-control" value="<?= e($profile['phone']) ?>" placeholder="0876488047">
        </div>
        <div class="col-md-4">
          <label class="form-label" for="phone_display">Số điện thoại hiển thị đẹp</label>
          <input type="text" name="phone_display" id="phone_display" class="form-control" value="<?= e($profile['phone_display']) ?>" placeholder="087 6488 047">
        </div>
        <div class="col-md-4">
          <label class="form-label" for="email">Địa chỉ Email</label>
          <input type="email" name="email" id="email" class="form-control" value="<?= e($profile['email']) ?>" placeholder="hphuong123123@gmail.com">
        </div>
      </div>

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label" for="zalo_url">Link kết nối Zalo</label>
          <input type="url" name="zalo_url" id="zalo_url" class="form-control" value="<?= e($profile['zalo_url']) ?>" placeholder="https://zalo.me/0876488047">
        </div>
        <div class="col-md-6">
          <label class="form-label" for="address">Địa chỉ / Khu vực làm việc</label>
          <input type="text" name="address" id="address" class="form-control" value="<?= e($profile['address']) ?>" placeholder="Trường Thọ, TP. Thủ Đức, TP.HCM">
        </div>
      </div>
    </div>
  </div>

  <!-- 3. MỤC TIÊU NGHỀ NGHIỆP & CAM KẾT (ABOUT) -->
  <div class="card-admin mb-4">
    <div class="card-admin-header">
      <h5 class="mb-0 fw-bold text-white"><i class="bi bi-bullseye text-warning me-2"></i>3. Mục tiêu nghề nghiệp (About Section)</h5>
    </div>
    <div class="p-4">
      <div class="mb-3">
        <label class="form-label" for="about_quote">Trích dẫn mục tiêu trọng tâm (Đoạn viền vàng)</label>
        <textarea name="about_quote" id="about_quote" rows="3" class="form-control"><?= e($profile['about_quote']) ?></textarea>
      </div>

      <div class="mb-3">
        <label class="form-label" for="about_subtext">Nội dung bổ trợ mục tiêu</label>
        <textarea name="about_subtext" id="about_subtext" rows="2" class="form-control"><?= e($profile['about_subtext']) ?></textarea>
      </div>

      <div class="row g-3">
        <div class="col-md-6">
          <div class="p-3 rounded-3 bg-dark bg-opacity-50 border border-secondary border-opacity-25">
            <h6 class="fw-bold text-warning mb-2"><i class="bi bi-shield-check me-1"></i>Cam kết 1</h6>
            <div class="mb-2">
              <label class="form-label small" for="commitment_1_title">Tiêu đề cam kết</label>
              <input type="text" name="commitment_1_title" id="commitment_1_title" class="form-control" value="<?= e($profile['commitment_1_title']) ?>" placeholder="Cam kết chỉ tiêu">
            </div>
            <div>
              <label class="form-label small" for="commitment_1_desc">Mô tả cam kết</label>
              <input type="text" name="commitment_1_desc" id="commitment_1_desc" class="form-control" value="<?= e($profile['commitment_1_desc']) ?>" placeholder="Bám sát KPI doanh số và tiến độ công việc">
            </div>
          </div>
        </div>

        <div class="col-md-6">
          <div class="p-3 rounded-3 bg-dark bg-opacity-50 border border-secondary border-opacity-25">
            <h6 class="fw-bold text-danger mb-2"><i class="bi bi-graph-up-arrow me-1"></i>Cam kết 2</h6>
            <div class="mb-2">
              <label class="form-label small" for="commitment_2_title">Tiêu đề cam kết</label>
              <input type="text" name="commitment_2_title" id="commitment_2_title" class="form-control" value="<?= e($profile['commitment_2_title']) ?>" placeholder="Mở rộng tệp khách hàng">
            </div>
            <div>
              <label class="form-label small" for="commitment_2_desc">Mô tả cam kết</label>
              <input type="text" name="commitment_2_desc" id="commitment_2_desc" class="form-control" value="<?= e($profile['commitment_2_desc']) ?>" placeholder="Thiết lập quan hệ với 10+ đối tác mới/tháng">
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- 4. KHỐI KÊU GỌI LIÊN HỆ CUỐI TRANG (CONTACT CALLOUT) -->
  <div class="card-admin mb-4">
    <div class="card-admin-header">
      <h5 class="mb-0 fw-bold text-white"><i class="bi bi-chat-heart text-warning me-2"></i>4. Lời kêu gọi kết nối &amp; Hợp tác (Contact Section)</h5>
    </div>
    <div class="p-4">
      <div class="mb-3">
        <label class="form-label" for="contact_heading">Tiêu đề kêu gọi hợp tác</label>
        <input type="text" name="contact_heading" id="contact_heading" class="form-control" value="<?= e($profile['contact_heading']) ?>" placeholder="Sẵn sàng đồng hành cùng doanh nghiệp đạt mục tiêu doanh số.">
      </div>
      <div>
        <label class="form-label" for="contact_subtext">Lời nhắn gửi nhà tuyển dụng</label>
        <textarea name="contact_subtext" id="contact_subtext" rows="2" class="form-control"><?= e($profile['contact_subtext']) ?></textarea>
      </div>
    </div>
  </div>

  <!-- Submit Buttons Bar -->
  <div class="d-flex justify-content-end gap-3 sticky-bottom py-3 px-4 rounded-3 card-admin mb-4">
    <a href="profile.php" class="btn btn-outline-secondary">Hủy bỏ</a>
    <button type="submit" class="btn btn-gold px-4">
      <i class="bi bi-check-lg me-1"></i> Lưu thay đổi hồ sơ
    </button>
  </div>

</form>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
