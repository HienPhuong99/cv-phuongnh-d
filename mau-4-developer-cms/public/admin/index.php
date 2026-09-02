<?php
/**
 * TRANG TỔNG QUAN (DASHBOARD)
 */

$pageTitle = 'Bảng điều khiển';
$activeMenu = 'dashboard';

require_once __DIR__ . '/partials/header.php';

$profile = Repository::getProfile();
$skillsCount = count(Repository::getSkills());
$expCount = count(Repository::getExperiences());
$sectionsCount = count(Repository::getSections());
$recentMessages = Repository::getMessages(5);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h2 class="h3 fw-bold text-white mb-1">Bảng điều khiển</h2>
    <p class="text-secondary small mb-0">Quản lý và cập nhật toàn bộ nội dung CV trực tuyến</p>
  </div>
  <a href="<?= url() ?>" target="_blank" class="btn btn-gold">
    <i class="bi bi-eye me-1"></i> Xem trang CV
  </a>
</div>

<?php $cvStatus = get_cv_status(); ?>
<?php if ($cvStatus['is_stale']): ?>
  <div class="alert alert-danger border-danger border-opacity-25 bg-danger bg-opacity-10 d-flex flex-wrap align-items-center justify-content-between p-3 rounded-3 mb-4 shadow-sm" role="alert">
    <div class="d-flex align-items-center me-3 mb-2 mb-md-0">
      <i class="bi bi-exclamation-octagon-fill fs-3 text-danger me-3 flex-shrink-0"></i>
      <div>
        <h6 class="fw-bold text-danger mb-1">Cảnh báo: Bản CV PDF đính kèm có thể đã lỗi thời!</h6>
        <div class="small text-white text-opacity-75">
          Nội dung trên website đã được chỉnh sửa lúc <strong><?= date('H:i d/m/Y', strtotime($cvStatus['content_changed_at'])) ?></strong>, trong khi bản PDF hiện tại được tải lên lúc <strong><?= date('H:i d/m/Y', strtotime($cvStatus['cv_uploaded_at'])) ?></strong>.
        </div>
      </div>
    </div>
    <a href="profile.php" class="btn btn-sm btn-danger px-3 text-nowrap">
      <i class="bi bi-cloud-arrow-up me-1"></i> Cập nhật bản PDF mới
    </a>
  </div>
<?php elseif (!$cvStatus['has_cv']): ?>
  <div class="alert alert-warning border-warning border-opacity-25 bg-warning bg-opacity-10 d-flex flex-wrap align-items-center justify-content-between p-3 rounded-3 mb-4 shadow-sm" role="alert">
    <div class="d-flex align-items-center me-3 mb-2 mb-md-0">
      <i class="bi bi-exclamation-triangle-fill fs-3 text-warning me-3 flex-shrink-0"></i>
      <div>
        <h6 class="fw-bold text-warning mb-1">Chưa tải lên file CV định dạng PDF</h6>
        <div class="small text-white text-opacity-75">
          Hệ thống chưa có file CV PDF. Hãy tải lên file PDF để nhà tuyển dụng có thể tải về trực tiếp từ thanh điều hướng website.
        </div>
      </div>
    </div>
    <a href="profile.php" class="btn btn-sm btn-warning px-3 text-nowrap">
      <i class="bi bi-cloud-arrow-up me-1"></i> Tải lên file CV
    </a>
  </div>
<?php endif; ?>

<!-- Stats Counter Cards Row -->
<div class="row g-3 mb-4">
  <div class="col-sm-6 col-xl-3">
    <div class="card-admin p-3">
      <div class="d-flex align-items-center justify-content-between">
        <div>
          <span class="text-secondary small text-uppercase fw-semibold">Kỹ năng cốt lõi</span>
          <h3 class="fw-bold text-warning mb-0 mt-1"><?= $skillsCount ?></h3>
        </div>
        <div class="p-3 rounded-3 bg-warning bg-opacity-10 text-warning">
          <i class="bi bi-lightning-charge fs-3"></i>
        </div>
      </div>
      <a href="skills.php" class="text-decoration-none small text-warning d-inline-block mt-3">
        Quản lý kỹ năng <i class="bi bi-arrow-right"></i>
      </a>
    </div>
  </div>

  <div class="col-sm-6 col-xl-3">
    <div class="card-admin p-3">
      <div class="d-flex align-items-center justify-content-between">
        <div>
          <span class="text-secondary small text-uppercase fw-semibold">Mốc kinh nghiệm</span>
          <h3 class="fw-bold text-info mb-0 mt-1"><?= $expCount ?></h3>
        </div>
        <div class="p-3 rounded-3 bg-info bg-opacity-10 text-info">
          <i class="bi bi-briefcase fs-3"></i>
        </div>
      </div>
      <a href="experience.php" class="text-decoration-none small text-info d-inline-block mt-3">
        Quản lý kinh nghiệm <i class="bi bi-arrow-right"></i>
      </a>
    </div>
  </div>

  <div class="col-sm-6 col-xl-3">
    <div class="card-admin p-3">
      <div class="d-flex align-items-center justify-content-between">
        <div>
          <span class="text-secondary small text-uppercase fw-semibold">Section trên trang</span>
          <h3 class="fw-bold text-success mb-0 mt-1"><?= $sectionsCount ?></h3>
        </div>
        <div class="p-3 rounded-3 bg-success bg-opacity-10 text-success">
          <i class="bi bi-layout-wtf fs-3"></i>
        </div>
      </div>
      <a href="sections.php" class="text-decoration-none small text-success d-inline-block mt-3">
        Cấu hình Section <i class="bi bi-arrow-right"></i>
      </a>
    </div>
  </div>

  <div class="col-sm-6 col-xl-3">
    <div class="card-admin p-3">
      <div class="d-flex align-items-center justify-content-between">
        <div>
          <span class="text-secondary small text-uppercase fw-semibold">Tin nhắn liên hệ</span>
          <h3 class="fw-bold text-danger mb-0 mt-1"><?= $unreadCount ?> mới</h3>
        </div>
        <div class="p-3 rounded-3 bg-danger bg-opacity-10 text-danger">
          <i class="bi bi-chat-left-dots fs-3"></i>
        </div>
      </div>
      <a href="messages.php" class="text-decoration-none small text-danger d-inline-block mt-3">
        Xem tất cả tin nhắn <i class="bi bi-arrow-right"></i>
      </a>
    </div>
  </div>
</div>

<!-- Profile Quick Info & Quick Actions -->
<div class="row g-4 mb-4">
  <div class="col-lg-7">
    <div class="card-admin h-100">
      <div class="card-admin-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold text-white"><i class="bi bi-person-badge text-warning me-2"></i>Thông tin hồ sơ chính</h5>
        <a href="profile.php" class="btn btn-sm btn-outline-warning">Sửa hồ sơ</a>
      </div>
      <div class="p-4">
        <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-3 mb-3">
          <img src="<?= upload_url($profile['avatar']) ?>" alt="Avatar" class="avatar-preview">
          <div>
            <h4 class="fw-bold text-white mb-1"><?= e($profile['full_name']) ?></h4>
            <div class="text-warning fw-semibold mb-1"><?= e($profile['job_title']) ?></div>
            <div class="text-secondary small"><i class="bi bi-geo-alt me-1"></i><?= e($profile['address']) ?></div>
          </div>
        </div>

        <div class="p-3 rounded-3 bg-dark bg-opacity-50 border border-secondary border-opacity-25 mb-3">
          <div class="text-secondary small mb-1 fw-semibold">Trạng thái hiện tại:</div>
          <span class="badge bg-warning text-dark px-2 py-1"><?= e($profile['status_badge']) ?></span>
        </div>

        <div class="row g-2 text-secondary small">
          <div class="col-sm-6"><strong class="text-light">Email:</strong> <?= e($profile['email']) ?></div>
          <div class="col-sm-6"><strong class="text-light">Điện thoại:</strong> <?= e($profile['phone']) ?></div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card-admin h-100">
      <div class="card-admin-header">
        <h5 class="mb-0 fw-bold text-white"><i class="bi bi-lightning text-warning me-2"></i>Thao tác nhanh</h5>
      </div>
      <div class="p-4 d-flex flex-column gap-2">
        <a href="experience.php?action=create" class="btn btn-outline-secondary text-start text-light py-2">
          <i class="bi bi-plus-circle text-info me-2"></i>Thêm mốc kinh nghiệm mới
        </a>
        <a href="skills.php?action=create" class="btn btn-outline-secondary text-start text-light py-2">
          <i class="bi bi-plus-circle text-warning me-2"></i>Thêm kỹ năng cốt lõi
        </a>
        <a href="strengths.php?action=create" class="btn btn-outline-secondary text-start text-light py-2">
          <i class="bi bi-plus-circle text-success me-2"></i>Thêm điểm mạnh / phẩm chất
        </a>
        <a href="education.php" class="btn btn-outline-secondary text-start text-light py-2">
          <i class="bi bi-pencil-square text-primary me-2"></i>Cập nhật học vấn &amp; công cụ
        </a>
        <a href="settings.php" class="btn btn-outline-secondary text-start text-light py-2">
          <i class="bi bi-gear text-secondary me-2"></i>Cấu hình SEO &amp; Footer
        </a>
      </div>
    </div>
  </div>
</div>

<!-- Recent Messages Table -->
<div class="card-admin">
  <div class="card-admin-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0 fw-bold text-white"><i class="bi bi-envelope text-warning me-2"></i>Tin nhắn liên hệ gần đây</h5>
    <a href="messages.php" class="btn btn-sm btn-outline-secondary text-light">Xem tất cả</a>
  </div>
  <div class="table-responsive">
    <table class="table table-dark-custom mb-0 align-middle">
      <thead>
        <tr>
          <th class="ps-4">Người gửi</th>
          <th>Email</th>
          <th>Nội dung</th>
          <th>Thời gian</th>
          <th class="text-end pe-4">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($recentMessages)): ?>
          <tr>
            <td colspan="5" class="text-center py-4 text-secondary">Chưa có tin nhắn nào được gửi đến.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($recentMessages as $msg): ?>
            <tr>
              <td class="ps-4">
                <span class="fw-bold <?= $msg['is_read'] ? 'text-secondary' : 'text-white' ?>">
                  <?= e($msg['name']) ?>
                </span>
                <?php if (!$msg['is_read']): ?>
                  <span class="badge bg-danger ms-1">Mới</span>
                <?php endif; ?>
              </td>
              <td class="text-secondary"><?= e($msg['email']) ?></td>
              <td class="text-secondary" style="max-width: 300px;">
                <div class="text-truncate"><?= e($msg['message']) ?></div>
              </td>
              <td class="text-secondary small"><?= date('d/m/Y H:i', strtotime($msg['created_at'])) ?></td>
              <td class="text-end pe-4">
                <a href="messages.php?id=<?= $msg['id'] ?>" class="btn btn-sm btn-outline-warning">
                  <i class="bi bi-eye"></i> Xem
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
