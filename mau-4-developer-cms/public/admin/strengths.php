<?php
/**
 * TRANG QUẢN LÝ ĐIỂM MẠNH & KỶ LUẬT (STRENGTHS)
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
            'title'        => trim($_POST['title'] ?? ''),
            'description'  => trim($_POST['description'] ?? ''),
            'icon'         => trim($_POST['icon'] ?? 'shield'),
            'accent_color' => trim($_POST['accent_color'] ?? 'gold'),
            'is_active'    => isset($_POST['is_active']) ? 1 : 0
        ];

        if (empty($data['title']) || empty($data['description'])) {
            flash('error', 'Vui lòng nhập tiêu đề và mô tả điểm mạnh.');
        } else {
            Repository::saveStrength($data, $id);
            touch_content_changed();
            flash('success', $id ? 'Đã cập nhật điểm mạnh thành công!' : 'Đã thêm điểm mạnh mới thành công!');
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            Repository::deleteStrength($id);
            touch_content_changed();
            flash('success', 'Đã xóa điểm mạnh thành công!');
        }
    }

    redirect('admin/strengths.php');
}

$strengths = Repository::getStrengths(false);

$pageTitle = 'Điểm mạnh & Kỷ luật (Strengths)';
$activeMenu = 'strengths';
require_once __DIR__ . '/partials/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h2 class="h3 fw-bold text-white mb-1">Điểm mạnh &amp; Kỷ luật (Strengths)</h2>
    <p class="text-secondary small mb-0">Các phẩm chất nổi bật giúp tạo ấn tượng mạnh mẽ với nhà tuyển dụng</p>
  </div>
  <button type="button" class="btn btn-gold" data-bs-toggle="modal" data-bs-target="#strengthModal">
    <i class="bi bi-plus-lg me-1"></i> Thêm điểm mạnh mới
  </button>
</div>

<div class="card-admin">
  <div class="card-admin-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0 fw-bold text-white"><i class="bi bi-award text-warning me-2"></i>Danh sách phẩm chất</h5>
    <span class="badge bg-secondary">Kéo icon <i class="bi bi-grip-vertical"></i> để sắp xếp</span>
  </div>
  <div class="table-responsive">
    <table class="table table-dark-custom mb-0 align-middle">
      <thead>
        <tr>
          <th style="width: 50px;" class="ps-4">Thứ tự</th>
          <th>Icon</th>
          <th>Tên phẩm chất</th>
          <th>Mô tả chi tiết</th>
          <th>Màu nhấn</th>
          <th class="text-center" style="width: 120px;">Hiển thị</th>
          <th class="text-end pe-4" style="width: 160px;">Thao tác</th>
        </tr>
      </thead>
      <tbody data-sortable-table="strengths" data-sortable-handle=".drag-handle">
        <?php if (empty($strengths)): ?>
          <tr>
            <td colspan="7" class="text-center py-4 text-secondary">Chưa có điểm mạnh nào. Nhấn "Thêm điểm mạnh mới" để bắt đầu.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($strengths as $item): ?>
            <tr data-id="<?= $item['id'] ?>">
              <td class="ps-4 text-center">
                <i class="bi bi-grip-vertical drag-handle fs-5"></i>
              </td>
              <td>
                <div class="p-2 rounded-2 bg-dark d-inline-block text-warning" style="border: 1px solid #33271A;">
                  <?= render_icon($item['icon'] ?? 'shield', 'w-5 h-5') ?>
                </div>
              </td>
              <td class="fw-bold text-white"><?= e($item['title']) ?></td>
              <td class="text-secondary small" style="max-width: 320px;">
                <div class="text-truncate"><?= e($item['description']) ?></div>
              </td>
              <td>
                <span class="badge" style="background-color: <?= match($item['accent_color']){'terracotta'=>'#D2603A','goldHover'=>'#F0BB55','bronze'=>'#A9762B',default=>'#E3A93B'} ?>; color: #0E0B08; font-weight: bold;">
                  <?= e($item['accent_color']) ?>
                </span>
              </td>
              <td class="text-center">
                <div class="form-check form-switch d-inline-block">
                  <input class="form-check-input ajax-toggle" type="checkbox" role="switch"
                         data-table="strengths" data-id="<?= $item['id'] ?>" data-column="is_active"
                         <?= $item['is_active'] ? 'checked' : '' ?>>
                </div>
              </td>
              <td class="text-end pe-4">
                <button type="button" class="btn btn-sm btn-outline-warning me-1"
                        data-bs-toggle="modal" data-bs-target="#strengthModal"
                        data-id="<?= $item['id'] ?>"
                        data-title="<?= e($item['title']) ?>"
                        data-description="<?= e($item['description']) ?>"
                        data-icon="<?= e($item['icon']) ?>"
                        data-color="<?= e($item['accent_color']) ?>"
                        data-active="<?= $item['is_active'] ?>"
                        onclick="editStrength(this)">
                  <i class="bi bi-pencil"></i>
                </button>
                <form method="POST" action="strengths.php" class="d-inline form-delete" data-item-name="<?= e($item['title']) ?>">
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

<!-- Modal Thêm / Sửa Điểm mạnh -->
<div class="modal fade" id="strengthModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content card-admin border-secondary">
      <div class="modal-header border-secondary">
        <h5 class="modal-title text-white fw-bold" id="strengthModalTitle">
          <i class="bi bi-plus-circle text-warning me-2"></i>Thêm điểm mạnh mới
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Đóng"></button>
      </div>
      <form method="POST" action="strengths.php">
        <?= Csrf::field() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" id="strength_id" value="">

        <div class="modal-body space-y-3">
          <div class="mb-3">
            <label class="form-label" for="strength_title">Tiêu đề phẩm chất <span class="text-danger">*</span></label>
            <input type="text" name="title" id="strength_title" class="form-control" placeholder="Ví dụ: Chịu Áp Lực Cao, Kiên Nhẫn & Bền Bỉ" required>
          </div>

          <div class="mb-3">
            <label class="form-label" for="strength_description">Mô tả phẩm chất <span class="text-danger">*</span></label>
            <textarea name="description" id="strength_description" rows="3" class="form-control" placeholder="Khả năng làm việc tốt dưới áp lực..." required></textarea>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label" for="strength_icon">Biểu tượng Icon</label>
              <select name="icon" id="strength_icon" class="form-select">
                <option value="shield">Khiên bảo vệ (shield)</option>
                <option value="activity">Nhịp tim / Bền bỉ (activity)</option>
                <option value="book-open">Sách mở / Tự học (book-open)</option>
                <option value="target">Mục tiêu (target)</option>
                <option value="star">Ngôi sao (star)</option>
                <option value="lightning">Tia sét (lightning)</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="strength_color">Màu nhấn</label>
              <select name="accent_color" id="strength_color" class="form-select">
                <option value="gold">Vàng Gold (#E3A93B)</option>
                <option value="terracotta">Cam Đất / Terracotta (#D2603A)</option>
                <option value="goldHover">Vàng Sáng / Gold Hover (#F0BB55)</option>
                <option value="bronze">Đồng / Bronze (#A9762B)</option>
              </select>
            </div>
          </div>

          <div class="form-check form-switch pt-2">
            <input class="form-check-input" type="checkbox" name="is_active" id="strength_active" value="1" checked>
            <label class="form-check-label text-light" for="strength_active">Kích hoạt hiển thị</label>
          </div>
        </div>

        <div class="modal-footer border-secondary">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
          <button type="submit" class="btn btn-gold">Lưu phẩm chất</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function editStrength(btn) {
  document.getElementById('strengthModalTitle').innerHTML = '<i class="bi bi-pencil-square text-warning me-2"></i>Sửa điểm mạnh';
  document.getElementById('strength_id').value = btn.getAttribute('data-id');
  document.getElementById('strength_title').value = btn.getAttribute('data-title');
  document.getElementById('strength_description').value = btn.getAttribute('data-description');
  document.getElementById('strength_icon').value = btn.getAttribute('data-icon');
  document.getElementById('strength_color').value = btn.getAttribute('data-color');
  document.getElementById('strength_active').checked = btn.getAttribute('data-active') == '1';
}

document.getElementById('strengthModal').addEventListener('hidden.bs.modal', function () {
  document.getElementById('strengthModalTitle').innerHTML = '<i class="bi bi-plus-circle text-warning me-2"></i>Thêm điểm mạnh mới';
  document.getElementById('strength_id').value = '';
  document.getElementById('strength_title').value = '';
  document.getElementById('strength_description').value = '';
  document.getElementById('strength_icon').value = 'shield';
  document.getElementById('strength_color').value = 'gold';
  document.getElementById('strength_active').checked = true;
});
</script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
