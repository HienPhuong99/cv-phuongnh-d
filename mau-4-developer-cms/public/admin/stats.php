<?php
/**
 * TRANG QUẢN LÝ CÁC CHỈ SỐ THỐNG KÊ (KEY STATS)
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
            'value'        => trim($_POST['value'] ?? ''),
            'label'        => trim($_POST['label'] ?? ''),
            'subtext'      => trim($_POST['subtext'] ?? ''),
            'accent_color' => trim($_POST['accent_color'] ?? 'gold'),
            'is_active'    => isset($_POST['is_active']) ? 1 : 0
        ];

        if (empty($data['value']) || empty($data['label'])) {
            flash('error', 'Vui lòng nhập giá trị chỉ số và nhãn hiển thị.');
        } else {
            Repository::saveStat($data, $id);
            touch_content_changed();
            flash('success', $id ? 'Đã cập nhật chỉ số thành công!' : 'Đã thêm chỉ số mới thành công!');
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            Repository::deleteStat($id);
            touch_content_changed();
            flash('success', 'Đã xóa chỉ số thành công!');
        }
    }

    redirect('admin/stats.php');
}

$stats = Repository::getKeyStats(false);

$pageTitle = 'Chỉ số thống kê (Key Stats)';
$activeMenu = 'stats';
require_once __DIR__ . '/partials/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h2 class="h3 fw-bold text-white mb-1">Chỉ số thống kê (Key Stats)</h2>
    <p class="text-secondary small mb-0">Các khối chỉ số ấn tượng hiển thị trong Section "Về tôi / Mục tiêu nghề nghiệp"</p>
  </div>
  <button type="button" class="btn btn-gold" data-bs-toggle="modal" data-bs-target="#statModal">
    <i class="bi bi-plus-lg me-1"></i> Thêm chỉ số mới
  </button>
</div>

<div class="card-admin">
  <div class="card-admin-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0 fw-bold text-white"><i class="bi bi-bar-chart-line text-warning me-2"></i>Danh sách chỉ số</h5>
    <span class="badge bg-secondary">Kéo icon <i class="bi bi-grip-vertical"></i> để sắp xếp</span>
  </div>
  <div class="table-responsive">
    <table class="table table-dark-custom mb-0 align-middle">
      <thead>
        <tr>
          <th style="width: 50px;" class="ps-4">Thứ tự</th>
          <th>Chỉ số (Value)</th>
          <th>Nhãn (Label)</th>
          <th>Mô tả phụ</th>
          <th>Màu sắc (Accent)</th>
          <th class="text-center" style="width: 120px;">Hiển thị</th>
          <th class="text-end pe-4" style="width: 160px;">Thao tác</th>
        </tr>
      </thead>
      <tbody data-sortable-table="key_stats" data-sortable-handle=".drag-handle">
        <?php if (empty($stats)): ?>
          <tr>
            <td colspan="7" class="text-center py-4 text-secondary">Chưa có chỉ số nào. Nhấn "Thêm chỉ số mới" để bắt đầu.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($stats as $item): ?>
            <tr data-id="<?= $item['id'] ?>">
              <td class="ps-4 text-center">
                <i class="bi bi-grip-vertical drag-handle fs-5"></i>
              </td>
              <td>
                <span class="fs-5 fw-bold text-warning font-monospace"><?= e($item['value']) ?></span>
              </td>
              <td class="fw-bold text-white"><?= e($item['label']) ?></td>
              <td class="text-secondary small"><?= e($item['subtext'] ?: '—') ?></td>
              <td>
                <span class="badge" style="background-color: <?= match($item['accent_color']){'terracotta'=>'#D2603A','goldHover'=>'#F0BB55','bronze'=>'#A9762B',default=>'#E3A93B'} ?>; color: #0E0B08; font-weight: bold;">
                  <?= e($item['accent_color']) ?>
                </span>
              </td>
              <td class="text-center">
                <div class="form-check form-switch d-inline-block">
                  <input class="form-check-input ajax-toggle" type="checkbox" role="switch"
                         data-table="key_stats" data-id="<?= $item['id'] ?>" data-column="is_active"
                         <?= $item['is_active'] ? 'checked' : '' ?>>
                </div>
              </td>
              <td class="text-end pe-4">
                <button type="button" class="btn btn-sm btn-outline-warning me-1" 
                        data-bs-toggle="modal" data-bs-target="#statModal"
                        data-id="<?= $item['id'] ?>"
                        data-value="<?= e($item['value']) ?>"
                        data-label="<?= e($item['label']) ?>"
                        data-subtext="<?= e($item['subtext']) ?>"
                        data-color="<?= e($item['accent_color']) ?>"
                        data-active="<?= $item['is_active'] ?>"
                        onclick="editStat(this)">
                  <i class="bi bi-pencil"></i>
                </button>
                <form method="POST" action="stats.php" class="d-inline form-delete" data-item-name="<?= e($item['label']) ?>">
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

<!-- Modal Thêm / Sửa Chỉ số -->
<div class="modal fade" id="statModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content card-admin border-secondary">
      <div class="modal-header border-secondary">
        <h5 class="modal-title text-white fw-bold" id="statModalTitle">
          <i class="bi bi-plus-circle text-warning me-2"></i>Thêm chỉ số mới
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Đóng"></button>
      </div>
      <form method="POST" action="stats.php">
        <?= Csrf::field() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" id="stat_id" value="">

        <div class="modal-body space-y-3">
          <div class="mb-3">
            <label class="form-label" for="stat_value">Giá trị chỉ số (Con số / Chữ) <span class="text-danger">*</span></label>
            <input type="text" name="value" id="stat_value" class="form-control" placeholder="Ví dụ: 3+, 10+, 100%, Đa kênh" required>
          </div>

          <div class="mb-3">
            <label class="form-label" for="stat_label">Nhãn hiển thị (Label) <span class="text-danger">*</span></label>
            <input type="text" name="label" id="stat_label" class="form-control" placeholder="Ví dụ: Năm thực chiến, Cam kết KPI" required>
          </div>

          <div class="mb-3">
            <label class="form-label" for="stat_subtext">Mô tả phụ</label>
            <input type="text" name="subtext" id="stat_subtext" class="form-control" placeholder="Ví dụ: Kinh doanh & Tư vấn bán hàng">
          </div>

          <div class="mb-3">
            <label class="form-label" for="stat_color">Màu nhấn (Accent Color)</label>
            <select name="accent_color" id="stat_color" class="form-select">
              <option value="gold">Vàng Gold (#E3A93B)</option>
              <option value="terracotta">Cam Đất / Terracotta (#D2603A)</option>
              <option value="goldHover">Vàng Sáng / Gold Hover (#F0BB55)</option>
              <option value="bronze">Đồng / Bronze (#A9762B)</option>
            </select>
          </div>

          <div class="form-check form-switch pt-2">
            <input class="form-check-input" type="checkbox" name="is_active" id="stat_active" value="1" checked>
            <label class="form-check-label text-light" for="stat_active">Kích hoạt hiển thị</label>
          </div>
        </div>

        <div class="modal-footer border-secondary">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
          <button type="submit" class="btn btn-gold">Lưu chỉ số</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function editStat(btn) {
  document.getElementById('statModalTitle').innerHTML = '<i class="bi bi-pencil-square text-warning me-2"></i>Sửa chỉ số';
  document.getElementById('stat_id').value = btn.getAttribute('data-id');
  document.getElementById('stat_value').value = btn.getAttribute('data-value');
  document.getElementById('stat_label').value = btn.getAttribute('data-label');
  document.getElementById('stat_subtext').value = btn.getAttribute('data-subtext');
  document.getElementById('stat_color').value = btn.getAttribute('data-color');
  document.getElementById('stat_active').checked = btn.getAttribute('data-active') == '1';
}

document.getElementById('statModal').addEventListener('hidden.bs.modal', function () {
  document.getElementById('statModalTitle').innerHTML = '<i class="bi bi-plus-circle text-warning me-2"></i>Thêm chỉ số mới';
  document.getElementById('stat_id').value = '';
  document.getElementById('stat_value').value = '';
  document.getElementById('stat_label').value = '';
  document.getElementById('stat_subtext').value = '';
  document.getElementById('stat_color').value = 'gold';
  document.getElementById('stat_active').checked = true;
});
</script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
