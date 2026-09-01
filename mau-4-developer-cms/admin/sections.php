<?php
/**
 * TRANG QUẢN LÝ CÁC SECTION TRÊN TRANG (BẬT/TẮT, SẮP XẾP, SỬA TIÊU ĐỀ)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Csrf.php';
require_once __DIR__ . '/../app/Repository.php';
require_once __DIR__ . '/../app/helpers.php';


Auth::requireAuth();

// Xử lý cập nhật thông tin section
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    Csrf::validateRequest();

    $id = (int)($_POST['id'] ?? 0);
    $data = [
        'badge_code' => trim($_POST['badge_code'] ?? ''),
        'title'      => trim($_POST['title'] ?? ''),
        'subtitle'   => trim($_POST['subtitle'] ?? ''),
        'is_visible' => isset($_POST['is_visible']) ? 1 : 0
    ];

    if ($id > 0 && !empty($data['title'])) {
        Repository::updateSection($id, $data);
        flash('success', 'Đã cập nhật cấu hình section thành công!');
    } else {
        flash('error', 'Tiêu đề section không được để trống.');
    }

    redirect('admin/sections.php');
}

$sections = Repository::getSections(false);

$pageTitle = 'Quản lý Section';
$activeMenu = 'sections';
require_once __DIR__ . '/partials/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h2 class="h3 fw-bold text-white mb-1">Quản lý các Section</h2>
    <p class="text-secondary small mb-0">Bật/tắt hiển thị, thay đổi tiêu đề và kéo thả sắp xếp thứ tự hiển thị</p>
  </div>
  <a href="<?= url() ?>" target="_blank" class="btn btn-outline-warning btn-sm">
    <i class="bi bi-eye me-1"></i> Xem trang CV
  </a>
</div>

<div class="card-admin">
  <div class="card-admin-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0 fw-bold text-white"><i class="bi bi-layout-wtf text-warning me-2"></i>Danh sách các Section</h5>
    <span class="badge bg-secondary">Kéo icon <i class="bi bi-grip-vertical"></i> để sắp xếp</span>
  </div>
  <div class="table-responsive">
    <table class="table table-dark-custom mb-0 align-middle">
      <thead>
        <tr>
          <th style="width: 50px;" class="ps-4">Thứ tự</th>
          <th>Mã / Khóa</th>
          <th>Mã Badge</th>
          <th>Tiêu đề Section</th>
          <th>Mô tả phụ</th>
          <th class="text-center" style="width: 130px;">Hiển thị</th>
          <th class="text-end pe-4" style="width: 120px;">Thao tác</th>
        </tr>
      </thead>
      <tbody data-sortable-table="sections" data-sortable-handle=".drag-handle">
        <?php foreach ($sections as $sec): ?>
          <tr data-id="<?= $sec['id'] ?>">
            <td class="ps-4 text-center">
              <i class="bi bi-grip-vertical drag-handle fs-5"></i>
            </td>
            <td>
              <code class="text-warning fw-bold">#<?= e($sec['key']) ?></code>
            </td>
            <td>
              <span class="badge bg-dark border border-secondary text-secondary">
                <?= e($sec['badge_code'] ?: '—') ?>
              </span>
            </td>
            <td class="fw-bold text-white">
              <?= e($sec['title']) ?>
            </td>
            <td class="text-secondary small">
              <?= e($sec['subtitle'] ?: '—') ?>
            </td>
            <td class="text-center">
              <div class="form-check form-switch d-inline-block">
                <input class="form-check-input ajax-toggle" type="checkbox" role="switch" 
                       data-table="sections" data-id="<?= $sec['id'] ?>" data-column="is_visible"
                       <?= $sec['is_visible'] ? 'checked' : '' ?>>
              </div>
            </td>
            <td class="text-end pe-4">
              <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#editModal<?= $sec['id'] ?>">
                <i class="bi bi-pencil"></i> Sửa
              </button>
            </td>
          </tr>

          <!-- Modal Sửa Section -->
          <div class="modal fade" id="editModal<?= $sec['id'] ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content card-admin border-secondary">
                <div class="modal-header border-secondary">
                  <h5 class="modal-title text-white fw-bold">
                    <i class="bi bi-pencil-square text-warning me-2"></i>Sửa Section #<?= e($sec['key']) ?>
                  </h5>
                  <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <form method="POST" action="sections.php">
                  <?= Csrf::field() ?>
                  <input type="hidden" name="action" value="update">
                  <input type="hidden" name="id" value="<?= $sec['id'] ?>">

                  <div class="modal-body space-y-3">
                    <div class="mb-3">
                      <label class="form-label" for="badge_code_<?= $sec['id'] ?>">Mã Badge (Tiền tố)</label>
                      <input type="text" name="badge_code" id="badge_code_<?= $sec['id'] ?>" class="form-control" value="<?= e($sec['badge_code']) ?>" placeholder="01 // MỤC TIÊU NGHỀ NGHIỆP">
                    </div>

                    <div class="mb-3">
                      <label class="form-label" for="title_<?= $sec['id'] ?>">Tiêu đề Section <span class="text-danger">*</span></label>
                      <input type="text" name="title" id="title_<?= $sec['id'] ?>" class="form-control" value="<?= e($sec['title']) ?>" required>
                    </div>

                    <div class="mb-3">
                      <label class="form-label" for="subtitle_<?= $sec['id'] ?>">Mô tả phụ</label>
                      <input type="text" name="subtitle" id="subtitle_<?= $sec['id'] ?>" class="form-control" value="<?= e($sec['subtitle']) ?>" placeholder="Bộ kỹ năng tư vấn...">
                    </div>

                    <div class="form-check form-switch pt-2">
                      <input class="form-check-input" type="checkbox" name="is_visible" id="vis_<?= $sec['id'] ?>" value="1" <?= $sec['is_visible'] ? 'checked' : '' ?>>
                      <label class="form-check-label text-light" for="vis_<?= $sec['id'] ?>">Hiển thị Section này trên trang CV</label>
                    </div>
                  </div>

                  <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-gold">Lưu thay đổi</button>
                  </div>
                </form>
              </div>
            </div>
          </div>

        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
