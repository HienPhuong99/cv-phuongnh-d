<?php
/**
 * TRANG QUẢN LÝ HỒ SƠ & HERO & LIÊN HỆ
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../app/Database.php';
require_once __DIR__ . '/../../app/Auth.php';
require_once __DIR__ . '/../../app/Csrf.php';
require_once __DIR__ . '/../../app/Upload.php';
require_once __DIR__ . '/../../app/Repository.php';
require_once __DIR__ . '/../../app/helpers.php';


Auth::requireAuth();

$profile = Repository::getProfile();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::validateRequest();
    $action = $_POST['action'] ?? 'update_profile';

    // A. XỬ LÝ TẢI LÊN FILE CV PDF RIÊNG BIỆT
    if ($action === 'upload_cv') {
        if (empty($_FILES['cv_file']['name']) || $_FILES['cv_file']['error'] !== UPLOAD_ERR_OK) {
            flash('error', 'Vui lòng chọn một file PDF hợp lệ để tải lên.');
            redirect('admin/profile.php');
        }

        $file = $_FILES['cv_file'];
        $originalName = basename($file['name']);
        $fileSize = (int)$file['size'];

        // 1. Giới hạn tối đa 5MB
        if ($fileSize > 5 * 1024 * 1024) {
            flash('error', 'File PDF vượt quá dung lượng cho phép (tối đa 5MB).');
            redirect('admin/profile.php');
        }

        // 2. Kiểm tra phần mở rộng
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            flash('error', 'Chỉ chấp nhận file có đuôi mở rộng là .pdf.');
            redirect('admin/profile.php');
        }

        // 3. Kiểm tra MIME bằng finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if ($mimeType !== 'application/pdf') {
            flash('error', 'Nội dung file không phải định dạng PDF chuẩn (MIME: ' . e($mimeType) . ').');
            redirect('admin/profile.php');
        }

        // 4. Kiểm tra định danh "%PDF-" trong ~1KB đầu file (đọc từ tmp_name, TRƯỚC move_uploaded_file).
        //    KHÔNG bắt buộc "%PDF-" nằm đúng byte 0: nhiều công cụ xuất PDF (trong đó có bản
        //    export của TopCV) chèn 1 ký tự xuống dòng \n hoặc BOM ở đầu. Trình đọc PDF thật và
        //    libmagic (bước 3) đều chấp nhận tối đa ~1024 byte "rác" trước header, nên ta cũng vậy.
        $head = @file_get_contents($file['tmp_name'], false, null, 0, 1024);
        if ($head === false) {
            flash('error', 'Không đọc được nội dung file tải lên. Vui lòng thử lại.');
            redirect('admin/profile.php');
        }
        // Bỏ BOM UTF-8 / UTF-16 ở đầu nếu có rồi mới dò "%PDF-"
        $head = preg_replace('/^(?:\xEF\xBB\xBF|\xFF\xFE|\xFE\xFF)/', '', $head);
        if (strpos($head, '%PDF-') === false) {
            flash('error', 'File không hợp lệ: không tìm thấy định danh %PDF- trong phần đầu file.');
            redirect('admin/profile.php');
        }

        // 5. Thư mục lưu trữ: public/uploads/cv/
        $cvDir = UPLOAD_PATH . '/cv';
        if (!is_dir($cvDir)) {
            mkdir($cvDir, 0755, true);
        }

        $filename = 'cv-' . uniqid() . '.pdf';
        $targetPath = $cvDir . '/' . $filename;
        $relativePath = 'uploads/cv/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            flash('error', 'Không thể lưu file PDF vào thư mục lưu trữ uploads/cv.');
            redirect('admin/profile.php');
        }

        // Xóa file cũ nếu đã có
        if (!empty($profile['cv_pdf_file'])) {
            Upload::deleteOldFile($profile['cv_pdf_file']);
        }

        // Cập nhật Database
        Repository::updateCvPdf($relativePath, $originalName, $fileSize);

        flash('success', 'Đã tải lên và lưu file CV PDF thành công!');
        redirect('admin/profile.php');
    }

    // B. XỬ LÝ XÓA FILE CV PDF
    if ($action === 'delete_cv') {
        if (!empty($profile['cv_pdf_file'])) {
            Upload::deleteOldFile($profile['cv_pdf_file']);
        }
        Repository::removeCvPdf();
        flash('success', 'Đã xóa file CV PDF đính kèm thành công!');
        redirect('admin/profile.php');
    }

    // C. XỬ LÝ CẬP NHẬT HỒ SƠ CHUNG
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

    // 2. Xử lý upload ảnh đại diện mới nếu có
    if (!empty($_FILES['avatar']['name'])) {
        $uploadRes = Upload::processImage($_FILES['avatar'], 'avatar-' . Upload::slugify($data['full_name']));
        if ($uploadRes['success']) {
            if (!empty($profile['avatar'])) {
                Upload::deleteOldFile($profile['avatar']);
            }
            $data['avatar'] = $uploadRes['path'];
        } else {
            set_old($_POST);
            if (($uploadRes['code'] ?? '') === 'unsupported_type') {
                // Người dùng hay bỏ nhầm file CV (PDF) vào ô avatar — chỉ rõ nên bỏ vào đâu.
                // "{cv_link}" là placeholder duy nhất render_flash() cho phép ở loại error_html;
                // nó được thay bằng thẻ <a> cố định, phần chữ còn lại vẫn bị escape bình thường.
                flash('error_html',
                    'Định dạng file không được hỗ trợ (chỉ chấp nhận JPG, PNG, WEBP, GIF). '
                    . 'Nếu bạn muốn tải lên file CV dạng PDF, hãy dùng khối {cv_link} ở cuối trang.');
            } else {
                flash('error', $uploadRes['message']);
            }
            redirect('admin/profile.php');
        }
    }

    clear_old();
    Repository::updateProfile($data);
    touch_content_changed();
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
          <label class="form-label" for="avatar">Ảnh chân dung đại diện (JPG, PNG, WEBP, GIF — tối đa 5MB)</label>
          <div class="d-flex align-items-center gap-3">
            <img src="<?= upload_url($profile['avatar']) ?>" id="avatarPreview" alt="Avatar" class="avatar-preview">
            <div class="flex-grow-1">
              <input type="file" name="avatar" id="avatar" class="form-control mb-2" accept="image/jpeg,image/png,image/webp,image/gif" data-preview="avatarPreview">
              <div class="text-secondary small">Chỉ nhận ảnh JPG, PNG, WEBP, GIF (tối đa 5MB, tự động tối ưu hóa cạnh dài max 1600px). Đây <strong>không phải</strong> ô tải file CV PDF.</div>
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

<!-- 5. KHỐI QUẢN LÝ FILE CV ĐÍNH KÈM (PDF) RIÊNG BIỆT -->
<div class="card-admin mb-4" id="cv-upload-block">
  <div class="card-admin-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0 fw-bold text-white"><i class="bi bi-file-earmark-pdf text-danger me-2"></i>5. File CV Đính Kèm (PDF)</h5>
    <?php if (!empty($profile['cv_pdf_file'])): ?>
      <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25 px-2.5 py-1.5"><i class="bi bi-check-circle me-1"></i>Đã tải lên CV</span>
    <?php else: ?>
      <span class="badge bg-warning bg-opacity-25 text-warning border border-warning border-opacity-25 px-2.5 py-1.5"><i class="bi bi-exclamation-triangle me-1"></i>Chưa có file CV</span>
    <?php endif; ?>
  </div>
  <div class="p-4">
    <?php if (!empty($profile['cv_pdf_file'])): ?>
      <div class="p-3 rounded-3 mb-4 border border-secondary border-opacity-25 bg-dark bg-opacity-50">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
          <div class="d-flex align-items-center gap-3">
            <div class="p-2.5 rounded-3 bg-danger bg-opacity-10 border border-danger border-opacity-25 text-danger fs-3">
              <i class="bi bi-file-earmark-pdf-fill"></i>
            </div>
            <div>
              <div class="fw-bold text-white fs-6"><?= e($profile['cv_original_name'] ?: basename($profile['cv_pdf_file'])) ?></div>
              <div class="text-secondary small mt-1">
                <span class="me-3"><i class="bi bi-hdd me-1"></i><?= number_format(($profile['cv_size'] ?? 0) / 1024, 1) ?> KB</span>
                <span><i class="bi bi-clock-history me-1"></i>Tải lên lúc: <?= !empty($profile['cv_uploaded_at']) ? date('H:i d/m/Y', strtotime($profile['cv_uploaded_at'])) : 'Vừa xong' ?></span>
              </div>
            </div>
          </div>
          <div class="d-flex align-items-center gap-2">
            <a href="<?= upload_url($profile['cv_pdf_file']) ?>" target="_blank" class="btn btn-sm btn-outline-info">
              <i class="bi bi-box-arrow-up-right me-1"></i> Xem thử
            </a>
            <form method="POST" action="profile.php" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa file CV này không?');">
              <?= Csrf::field() ?>
              <input type="hidden" name="action" value="delete_cv">
              <button type="submit" class="btn btn-sm btn-outline-danger">
                <i class="bi bi-trash3 me-1"></i> Xóa file
              </button>
            </form>
          </div>
        </div>
      </div>
    <?php else: ?>
      <div class="alert alert-dark border-secondary border-opacity-25 text-secondary d-flex align-items-center mb-4" role="alert">
        <i class="bi bi-info-circle fs-5 text-warning me-2"></i>
        <div>Chưa có file CV PDF nào được tải lên. Nhà tuyển dụng sẽ không thấy nút "Tải CV (PDF)" trên trang công khai cho đến khi bạn tải lên file CV.</div>
      </div>
    <?php endif; ?>

    <form method="POST" action="profile.php" enctype="multipart/form-data">
      <?= Csrf::field() ?>
      <input type="hidden" name="action" value="upload_cv">
      <label class="form-label fw-semibold text-white" for="cv_file">
        File CV để nhà tuyển dụng tải về (chỉ nhận PDF — tối đa 5MB)
      </label>
      <div class="row g-3 align-items-center">
        <div class="col-md-8">
          <input type="file" name="cv_file" id="cv_file" class="form-control" accept="application/pdf,.pdf" required>
        </div>
        <div class="col-md-4">
          <button type="submit" class="btn btn-gold w-100">
            <i class="bi bi-cloud-arrow-up me-1"></i> Tải lên file CV
          </button>
        </div>
      </div>
      <div class="form-text text-secondary mt-2">
        <i class="bi bi-shield-check text-success me-1"></i>Chỉ chấp nhận file định dạng PDF chuẩn (tối đa 5MB).
        <?= !empty($profile['cv_pdf_file']) ? 'File mới sẽ tự động thay thế file cũ. ' : '' ?>
        File sẽ được lưu trữ bảo mật tại <code>public/uploads/cv/</code>.
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
