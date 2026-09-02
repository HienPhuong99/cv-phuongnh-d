<?php
/**
 * TRANG QUẢN LÝ ĐIỂM CẦN CẢI THIỆN (WEAKNESSES)
 * Section này MẶC ĐỊNH ẨN khỏi trang công khai (sections.weaknesses.is_visible = 0).
 * Cấu trúc sao chép từ strengths.php để đồng nhất.
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../app/Database.php';
require_once __DIR__ . '/../../app/Auth.php';
require_once __DIR__ . '/../../app/Csrf.php';
require_once __DIR__ . '/../../app/Repository.php';
require_once __DIR__ . '/../../app/helpers.php';


Auth::requireAuth();

// Xử lý Thêm / Sửa / Xóa
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::validateRequest();
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
        $data = [
            'content'   => trim($_POST['content'] ?? ''),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];

        if (empty($data['content'])) {
            flash('error', 'Vui lòng nhập nội dung điểm cần cải thiện.');
        } elseif (mb_strlen($data['content'], 'UTF-8') > 255) {
            flash('error', 'Nội dung không được vượt quá 255 ký tự.');
        } else {
            Repository::saveWeakness($data, $id);
            touch_content_changed();
            flash('success', $id ? 'Đã cập nhật thành công!' : 'Đã thêm mục mới thành công!');
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            Repository::deleteWeakness($id);
            touch_content_changed();
            flash('success', 'Đã xóa mục thành công!');
        }
    }

    redirect('admin/weaknesses.php');
}

$weaknesses = Repository::getWeaknesses(false);
$section = Repository::getSectionMap()['weaknesses'] ?? null;
$isPublic = $section && (int)$section['is_visible'] === 1;

$pageTitle = 'Điểm cần cải thiện (Weaknesses)';
$activeMenu = 'weaknesses';
require_once __DIR__ . '/partials/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h2 class="h3 fw-bold text-white mb-1">Điểm cần cải thiện (Weaknesses)</h2>
    <p class="text-secondary small mb-0">Danh sách các điểm cần cải thiện của bản thân</p>
  </div>
  <button type="button" class="btn btn-gold" data-bs-toggle="modal" data-bs-target="#weaknessModal">
    <i class="bi bi-plus-lg me-1"></i> Thêm mục mới
  </button>
</div>

<div class="alert d-flex align-items-center gap-2 <?= $isPublic ? 'alert-danger' : 'alert-secondary' ?> border-opacity-25 mb-4" role="status">
  <i class="bi <?= $isPublic ? 'bi-eye-fill' : 'bi-eye-slash-fill' ?> flex-shrink-0"></i>
  <div class="small">
    <?php if ($isPublic): ?>
      Section này <strong>đang HIỂN THỊ</strong> công khai trên trang CV. Vào
      <a href="sections.php" class="alert-link">Quản lý Section</a> để tắt nếu cần.
    <?php else: ?>
      Section này <strong>đang ẩn</strong> khỏi trang công khai (mặc định). Chỉ hiện ra khi bạn
      bật <code>is_visible</code> trong <a href="sections.php" class="alert-link">Quản lý Section</a>.
    <?php endif; ?>
  </div>
</div>

<div class="card-admin">
  <div class="card-admin-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0 fw-bold text-white"><i class="bi bi-clipboard-check text-warning me-2"></i>Danh sách mục</h5>
    <span class="badge bg-secondary">Kéo icon <i class="bi bi-grip-vertical"></i> để sắp xếp</span>
  </div>
  <div class="table-responsive">
    <table class="table table-dark-custom mb-0 align-middle">
      <thead>
        <tr>
          <th style="width: 50px;" class="ps-4">Thứ tự</th>
          <th>Nội dung</th>
          <th class="text-center" style="width: 120px;">Hiển thị</th>
          <th class="text-end pe-4" style="width: 160px;">Thao tác</th>
        </tr>
      </thead>
      <tbody data-sortable-table="weaknesses" data-sortable-handle=".drag-handle">
        <?php if (empty($weaknesses)): ?>
          <tr>
            <td colspan="4" class="text-center py-4 text-secondary">Chưa có mục nào. Nhấn "Thêm mục mới" để bắt đầu.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($weaknesses as $item): ?>
            <tr data-id="<?= $item['id'] ?>">
              <td class="ps-4 text-center">
                <i class="bi bi-grip-vertical drag-handle fs-5"></i>
              </td>
              <td class="text-white"><?= e($item['content']) ?></td>
              <td class="text-center">
                <div class="form-check form-switch d-inline-block">
                  <input class="form-check-input ajax-toggle" type="checkbox" role="switch"
                         data-table="weaknesses" data-id="<?= $item['id'] ?>" data-column="is_active"
                         <?= $item['is_active'] ? 'checked' : '' ?>>
                </div>
              </td>
              <td class="text-end pe-4">
                <button type="button" class="btn btn-sm btn-outline-warning me-1"
                        data-bs-toggle="modal" data-bs-target="#weaknessModal"
                        data-id="<?= $item['id'] ?>"
                        data-content="<?= e($item['content']) ?>"
                        data-active="<?= $item['is_active'] ?>"
                        onclick="editWeakness(this)">
                  <i class="bi bi-pencil"></i>
                </button>
                <form method="POST" action="weaknesses.php" class="d-inline form-delete" data-item-name="<?= e($item['content']) ?>">
                  <?= Csrf::field() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= $item['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-trash"></i>
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Thêm / Sửa -->
<div class="modal fade" id="weaknessModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content card-admin border-secondary">
      <div class="modal-header border-secondary">
        <h5 class="modal-title text-white fw-bold" id="weaknessModalTitle">
          <i class="bi bi-plus-circle text-warning me-2"></i>Thêm mục mới
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Đóng"></button>
      </div>
      <form method="POST" action="weaknesses.php">
        <?= Csrf::field() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" id="weakness_id" value="">

        <div class="modal-body space-y-3">
          <div class="mb-2">
            <label class="form-label" for="weakness_content">Nội dung <span class="text-danger">*</span></label>
            <input type="text" name="content" id="weakness_content" class="form-control" maxlength="255"
                   placeholder="Ví dụ: Đôi khi quá tập trung vào chi tiết nhỏ" required>
          </div>

          <div class="alert alert-warning small mb-3" role="note">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            Mục này mặc định ẩn khỏi trang công khai. Cân nhắc kỹ trước khi bật — nội dung ở đây sẽ được
            nhà tuyển dụng đọc, và điểm yếu viết ra thường bị dùng để loại hồ sơ hơn là để đánh giá cao
            sự trung thực.
          </div>

          <div class="form-check form-switch pt-1">
            <input class="form-check-input" type="checkbox" name="is_active" id="weakness_active" value="1" checked>
            <label class="form-check-label text-light" for="weakness_active">Kích hoạt mục này (trong danh sách)</label>
          </div>
        </div>

        <div class="modal-footer border-secondary">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
          <button type="submit" class="btn btn-gold">Lưu</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function editWeakness(btn) {
  document.getElementById('weaknessModalTitle').innerHTML = '<i class="bi bi-pencil-square text-warning me-2"></i>Sửa mục';
  document.getElementById('weakness_id').value = btn.getAttribute('data-id');
  document.getElementById('weakness_content').value = btn.getAttribute('data-content');
  document.getElementById('weakness_active').checked = btn.getAttribute('data-active') == '1';
}

document.getElementById('weaknessModal').addEventListener('hidden.bs.modal', function () {
  document.getElementById('weaknessModalTitle').innerHTML = '<i class="bi bi-plus-circle text-warning me-2"></i>Thêm mục mới';
  document.getElementById('weakness_id').value = '';
  document.getElementById('weakness_content').value = '';
  document.getElementById('weakness_active').checked = true;
});
</script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
