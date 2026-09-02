<?php
/**
 * TRANG QUẢN LÝ KINH NGHIỆM LÀM VIỆC (EXPERIENCE)
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

        // Xử lý tags
        $rawTags = trim($_POST['tags'] ?? '');
        $tagsArray = array_values(array_filter(array_map('trim', explode(',', $rawTags))));
        $tagsJson = json_encode($tagsArray, JSON_UNESCAPED_UNICODE);

        $data = [
            'position'     => trim($_POST['position'] ?? ''),
            'company'      => trim($_POST['company'] ?? ''),
            'period_text'  => trim($_POST['period_text'] ?? ''),
            'description'  => trim($_POST['description'] ?? ''),
            'tags'         => $tagsJson,
            'accent_color' => trim($_POST['accent_color'] ?? 'gold'),
            'is_active'    => isset($_POST['is_active']) ? 1 : 0
        ];

        if (empty($data['position']) || empty($data['company']) || empty($data['period_text'])) {
            flash('error', 'Vui lòng nhập đầy đủ chức danh, tên công ty và thời gian làm việc.');
        } else {
            Repository::saveExperience($data, $id);
            touch_content_changed();
            flash('success', $id ? 'Đã cập nhật mốc kinh nghiệm thành công!' : 'Đã thêm mốc kinh nghiệm mới thành công!');
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            Repository::deleteExperience($id);
            touch_content_changed();
            flash('success', 'Đã xóa mốc kinh nghiệm thành công!');
        }
    }

    redirect('admin/experience.php');
}

$experiences = Repository::getExperiences(false);

$pageTitle = 'Kinh nghiệm làm việc (Experience)';
$activeMenu = 'experience';
require_once __DIR__ . '/partials/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h2 class="h3 fw-bold text-white mb-1">Kinh nghiệm làm việc (Experience)</h2>
    <p class="text-secondary small mb-0">Dòng thời gian các vị trí, công ty, trách nhiệm và kết quả đạt được</p>
  </div>
  <button type="button" class="btn btn-gold" data-bs-toggle="modal" data-bs-target="#expModal">
    <i class="bi bi-plus-lg me-1"></i> Thêm mốc kinh nghiệm
  </button>
</div>

<div class="card-admin">
  <div class="card-admin-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0 fw-bold text-white"><i class="bi bi-briefcase text-warning me-2"></i>Danh sách mốc kinh nghiệm</h5>
    <span class="badge bg-secondary">Kéo icon <i class="bi bi-grip-vertical"></i> để sắp xếp</span>
  </div>
  <div class="table-responsive">
    <table class="table table-dark-custom mb-0 align-middle">
      <thead>
        <tr>
          <th style="width: 50px;" class="ps-4">Thứ tự</th>
          <th>Chức danh</th>
          <th>Công ty / Đơn vị</th>
          <th>Thời gian</th>
          <th>Trách nhiệm chính</th>
          <th>Tags nổi bật</th>
          <th class="text-center" style="width: 120px;">Hiển thị</th>
          <th class="text-end pe-4" style="width: 160px;">Thao tác</th>
        </tr>
      </thead>
      <tbody data-sortable-table="experiences" data-sortable-handle=".drag-handle">
        <?php if (empty($experiences)): ?>
          <tr>
            <td colspan="8" class="text-center py-4 text-secondary">Chưa có mốc kinh nghiệm nào. Nhấn "Thêm mốc kinh nghiệm" để bắt đầu.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($experiences as $exp): ?>
            <tr data-id="<?= $exp['id'] ?>">
              <td class="ps-4 text-center">
                <i class="bi bi-grip-vertical drag-handle fs-5"></i>
              </td>
              <td>
                <span class="fw-bold text-white"><?= e($exp['position']) ?></span>
              </td>
              <td>
                <span class="text-warning fw-semibold"><?= e($exp['company']) ?></span>
              </td>
              <td>
                <span class="badge bg-dark border border-secondary text-secondary font-monospace"><?= e($exp['period_text']) ?></span>
              </td>
              <td class="text-secondary small" style="max-width: 240px;">
                <div class="text-truncate">
                  <?= !empty($exp['bullet_lines']) ? e($exp['bullet_lines'][0]) : e($exp['description']) ?>
                  <?php if (count($exp['bullet_lines']) > 1): ?>
                    <span class="text-warning">(+<?= count($exp['bullet_lines']) - 1 ?> mục)</span>
                  <?php endif; ?>
                </div>
              </td>
              <td>
                <?php if (!empty($exp['tag_array'])): ?>
                  <div class="d-flex flex-wrap gap-1">
                    <?php foreach ($exp['tag_array'] as $tag): ?>
                      <span class="badge bg-dark border border-secondary text-secondary small"><?= e($tag) ?></span>
                    <?php endforeach; ?>
                  </div>
                <?php else: ?>
                  <span class="text-secondary small">—</span>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <div class="form-check form-switch d-inline-block">
                  <input class="form-check-input ajax-toggle" type="checkbox" role="switch"
                         data-table="experiences" data-id="<?= $exp['id'] ?>" data-column="is_active"
                         <?= $exp['is_active'] ? 'checked' : '' ?>>
                </div>
              </td>
              <td class="text-end pe-4">
                <button type="button" class="btn btn-sm btn-outline-warning me-1"
                        data-bs-toggle="modal" data-bs-target="#expModal"
                        data-id="<?= $exp['id'] ?>"
                        data-position="<?= e($exp['position']) ?>"
                        data-company="<?= e($exp['company']) ?>"
                        data-period="<?= e($exp['period_text']) ?>"
                        data-description="<?= e($exp['description']) ?>"
                        data-tags="<?= e(implode(', ', $exp['tag_array'])) ?>"
                        data-color="<?= e($exp['accent_color']) ?>"
                        data-active="<?= $exp['is_active'] ?>"
                        onclick="editExp(this)">
                  <i class="bi bi-pencil"></i>
                </button>
                <form method="POST" action="experience.php" class="d-inline form-delete" data-item-name="<?= e($exp['position']) ?> tại <?= e($exp['company']) ?>">
                  <?= Csrf::field() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= $exp['id'] ?>">
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

<!-- Modal Thêm / Sửa Kinh nghiệm -->
<div class="modal fade" id="expModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content card-admin border-secondary">
      <div class="modal-header border-secondary">
        <h5 class="modal-title text-white fw-bold" id="expModalTitle">
          <i class="bi bi-plus-circle text-warning me-2"></i>Thêm mốc kinh nghiệm mới
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Đóng"></button>
      </div>
      <form method="POST" action="experience.php">
        <?= Csrf::field() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" id="exp_id" value="">

        <div class="modal-body space-y-3">
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label" for="exp_position">Chức danh / Vị trí <span class="text-danger">*</span></label>
              <input type="text" name="position" id="exp_position" class="form-control" placeholder="Ví dụ: Nhân Viên Kinh Doanh" required>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="exp_company">Tên công ty / Tổ chức <span class="text-danger">*</span></label>
              <input type="text" name="company" id="exp_company" class="form-control" placeholder="Ví dụ: Công Ty TNHH Kinh Doanh Siêu Việt" required>
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label" for="exp_period">Thời gian làm việc <span class="text-danger">*</span></label>
              <input type="text" name="period_text" id="exp_period" class="form-control" placeholder="Ví dụ: 04/2021 — 12/2024 (3 năm 8 tháng)" required>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="exp_color">Màu nhấn Timeline Marker</label>
              <select name="accent_color" id="exp_color" class="form-select">
                <option value="gold">Vàng Gold (#E3A93B)</option>
                <option value="terracotta">Cam Đất / Terracotta (#D2603A)</option>
                <option value="goldHover">Vàng Sáng / Gold Hover (#F0BB55)</option>
                <option value="bronze">Đồng / Bronze (#A9762B)</option>
              </select>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label" for="exp_description">Các đầu việc / Trách nhiệm chính (Mỗi dòng là 1 gạch đầu dòng) <span class="text-danger">*</span></label>
            <textarea name="description" id="exp_description" rows="5" class="form-control font-monospace small" placeholder="Chủ động tìm kiếm, mở rộng tệp khách hàng...&#10;Tư vấn bán hàng và giải đáp thắc mắc...&#10;Lập báo cáo doanh số..." required></textarea>
            <div class="form-text small text-secondary">Xuống dòng để tạo các bullet list trong timeline ngoài giao diện CV.</div>
          </div>

          <div class="mb-3">
            <label class="form-label" for="exp_tags">Tags lĩnh vực / Kỹ năng áp dụng (phân cách bằng dấu phẩy)</label>
            <input type="text" name="tags" id="exp_tags" class="form-control" placeholder="Ví dụ: Tư vấn B2B, Sàn Shopee/Lazada, Hợp đồng kinh tế, Canva Design">
          </div>

          <div class="form-check form-switch pt-2">
            <input class="form-check-input" type="checkbox" name="is_active" id="exp_active" value="1" checked>
            <label class="form-check-label text-light" for="exp_active">Kích hoạt hiển thị</label>
          </div>
        </div>

        <div class="modal-footer border-secondary">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
          <button type="submit" class="btn btn-gold">Lưu kinh nghiệm</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function editExp(btn) {
  document.getElementById('expModalTitle').innerHTML = '<i class="bi bi-pencil-square text-warning me-2"></i>Sửa mốc kinh nghiệm';
  document.getElementById('exp_id').value = btn.getAttribute('data-id');
  document.getElementById('exp_position').value = btn.getAttribute('data-position');
  document.getElementById('exp_company').value = btn.getAttribute('data-company');
  document.getElementById('exp_period').value = btn.getAttribute('data-period');
  document.getElementById('exp_description').value = btn.getAttribute('data-description');
  document.getElementById('exp_tags').value = btn.getAttribute('data-tags');
  document.getElementById('exp_color').value = btn.getAttribute('data-color');
  document.getElementById('exp_active').checked = btn.getAttribute('data-active') == '1';
}

document.getElementById('expModal').addEventListener('hidden.bs.modal', function () {
  document.getElementById('expModalTitle').innerHTML = '<i class="bi bi-plus-circle text-warning me-2"></i>Thêm mốc kinh nghiệm mới';
  document.getElementById('exp_id').value = '';
  document.getElementById('exp_position').value = '';
  document.getElementById('exp_company').value = '';
  document.getElementById('exp_period').value = '';
  document.getElementById('exp_description').value = '';
  document.getElementById('exp_tags').value = '';
  document.getElementById('exp_color').value = 'gold';
  document.getElementById('exp_active').checked = true;
});
</script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
