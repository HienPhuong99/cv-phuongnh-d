<?php
/**
 * TRANG QUẢN LÝ HỌC VẤN & CÔNG CỤ SỐ (EDUCATION & TOOLS)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Csrf.php';
require_once __DIR__ . '/../app/Repository.php';
require_once __DIR__ . '/../app/helpers.php';


Auth::requireAuth();

// Xử lý Thêm / Sửa / Xóa cho Học vấn và Công cụ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::validateRequest();
    $target = $_POST['target'] ?? '';
    $action = $_POST['action'] ?? '';

    if ($target === 'education') {
        if ($action === 'save') {
            $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
            $data = [
                'school'      => trim($_POST['school'] ?? ''),
                'degree'      => trim($_POST['degree'] ?? ''),
                'major'       => trim($_POST['major'] ?? ''),
                'period_text' => trim($_POST['period_text'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'footer_text' => trim($_POST['footer_text'] ?? ''),
                'is_active'   => isset($_POST['is_active']) ? 1 : 0
            ];

            if (empty($data['school']) || empty($data['period_text'])) {
                flash('error', 'Vui lòng nhập tên trường/tổ chức và thời gian học.');
            } else {
                Repository::saveEducation($data, $id);
                flash('success', $id ? 'Đã cập nhật học vấn thành công!' : 'Đã thêm học vấn mới thành công!');
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                Repository::deleteEducation($id);
                flash('success', 'Đã xóa mục học vấn thành công!');
            }
        }
    } elseif ($target === 'tool') {
        if ($action === 'save') {
            $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
            $data = [
                'name'         => trim($_POST['name'] ?? ''),
                'description'  => trim($_POST['description'] ?? ''),
                'icon'         => trim($_POST['icon'] ?? 'edit'),
                'accent_color' => trim($_POST['accent_color'] ?? 'gold'),
                'is_active'    => isset($_POST['is_active']) ? 1 : 0
            ];

            if (empty($data['name']) || empty($data['description'])) {
                flash('error', 'Vui lòng nhập tên công cụ và mô tả ứng dụng.');
            } else {
                Repository::saveTool($data, $id);
                flash('success', $id ? 'Đã cập nhật công cụ thành công!' : 'Đã thêm công cụ mới thành công!');
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                Repository::deleteTool($id);
                flash('success', 'Đã xóa công cụ thành công!');
            }
        }
    }

    redirect('admin/education.php');
}

$educations = Repository::getEducations(false);
$tools = Repository::getTools(false);

$pageTitle = 'Học vấn & Công cụ làm việc';
$activeMenu = 'education';
require_once __DIR__ . '/partials/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h2 class="h3 fw-bold text-white mb-1">Học vấn &amp; Công cụ số (Education &amp; Tools)</h2>
    <p class="text-secondary small mb-0">Quản lý nền tảng học vấn và các phần mềm, nền tảng số thành thạo</p>
  </div>
</div>

<!-- 1. BẢNG HỌC VẤN (CARD TRÁI) -->
<div class="card-admin mb-5">
  <div class="card-admin-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0 fw-bold text-white"><i class="bi bi-mortarboard text-warning me-2"></i>1. Nền tảng học vấn</h5>
    <button type="button" class="btn btn-sm btn-gold" data-bs-toggle="modal" data-bs-target="#eduModal">
      <i class="bi bi-plus-lg me-1"></i> Thêm học vấn
    </button>
  </div>
  <div class="table-responsive">
    <table class="table table-dark-custom mb-0 align-middle">
      <thead>
        <tr>
          <th style="width: 50px;" class="ps-4">Thứ tự</th>
          <th>Trường / Bằng cấp</th>
          <th>Chuyên ngành</th>
          <th>Thời gian</th>
          <th>Mô tả chi tiết</th>
          <th>Footer Note</th>
          <th class="text-center" style="width: 120px;">Hiển thị</th>
          <th class="text-end pe-4" style="width: 160px;">Thao tác</th>
        </tr>
      </thead>
      <tbody data-sortable-table="educations" data-sortable-handle=".drag-handle">
        <?php if (empty($educations)): ?>
          <tr>
            <td colspan="8" class="text-center py-4 text-secondary">Chưa có thông tin học vấn nào.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($educations as $edu): ?>
            <tr data-id="<?= $edu['id'] ?>">
              <td class="ps-4 text-center">
                <i class="bi bi-grip-vertical drag-handle fs-5"></i>
              </td>
              <td>
                <span class="fw-bold text-white"><?= e($edu['school']) ?></span>
                <?php if (!empty($edu['degree'])): ?>
                  <div class="text-secondary small"><?= e($edu['degree']) ?></div>
                <?php endif; ?>
              </td>
              <td>
                <span class="text-warning fw-semibold"><?= e($edu['major'] ?: '—') ?></span>
              </td>
              <td>
                <span class="badge bg-dark border border-secondary text-secondary font-monospace"><?= e($edu['period_text']) ?></span>
              </td>
              <td class="text-secondary small" style="max-width: 260px;">
                <div class="text-truncate"><?= e($edu['description']) ?></div>
              </td>
              <td class="text-secondary small"><?= e($edu['footer_text'] ?: '—') ?></td>
              <td class="text-center">
                <div class="form-check form-switch d-inline-block">
                  <input class="form-check-input ajax-toggle" type="checkbox" role="switch"
                         data-table="educations" data-id="<?= $edu['id'] ?>" data-column="is_active"
                         <?= $edu['is_active'] ? 'checked' : '' ?>>
                </div>
              </td>
              <td class="text-end pe-4">
                <button type="button" class="btn btn-sm btn-outline-warning me-1"
                        data-bs-toggle="modal" data-bs-target="#eduModal"
                        data-id="<?= $edu['id'] ?>"
                        data-school="<?= e($edu['school']) ?>"
                        data-degree="<?= e($edu['degree']) ?>"
                        data-major="<?= e($edu['major']) ?>"
                        data-period="<?= e($edu['period_text']) ?>"
                        data-description="<?= e($edu['description']) ?>"
                        data-footer="<?= e($edu['footer_text']) ?>"
                        data-active="<?= $edu['is_active'] ?>"
                        onclick="editEdu(this)">
                  <i class="bi bi-pencil"></i>
                </button>
                <form method="POST" action="education.php" class="d-inline form-delete" data-item-name="<?= e($edu['school']) ?>">
                  <?= Csrf::field() ?>
                  <input type="hidden" name="target" value="education">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= $edu['id'] ?>">
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

<!-- 2. BẢNG CÔNG CỤ SỐ (GRID PHẢI) -->
<div class="card-admin">
  <div class="card-admin-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0 fw-bold text-white"><i class="bi bi-tools text-warning me-2"></i>2. Công cụ số làm việc</h5>
    <button type="button" class="btn btn-sm btn-gold" data-bs-toggle="modal" data-bs-target="#toolModal">
      <i class="bi bi-plus-lg me-1"></i> Thêm công cụ mới
    </button>
  </div>
  <div class="table-responsive">
    <table class="table table-dark-custom mb-0 align-middle">
      <thead>
        <tr>
          <th style="width: 50px;" class="ps-4">Thứ tự</th>
          <th>Icon</th>
          <th>Tên công cụ</th>
          <th>Mô tả ứng dụng thực tế</th>
          <th>Màu nhấn</th>
          <th class="text-center" style="width: 120px;">Hiển thị</th>
          <th class="text-end pe-4" style="width: 160px;">Thao tác</th>
        </tr>
      </thead>
      <tbody data-sortable-table="tools" data-sortable-handle=".drag-handle">
        <?php if (empty($tools)): ?>
          <tr>
            <td colspan="7" class="text-center py-4 text-secondary">Chưa có công cụ nào.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($tools as $tool): ?>
            <tr data-id="<?= $tool['id'] ?>">
              <td class="ps-4 text-center">
                <i class="bi bi-grip-vertical drag-handle fs-5"></i>
              </td>
              <td>
                <div class="p-2 rounded-2 bg-dark d-inline-block text-warning" style="border: 1px solid #33271A;">
                  <?= render_icon($tool['icon'] ?? 'edit', 'w-5 h-5') ?>
                </div>
              </td>
              <td class="fw-bold text-white"><?= e($tool['name']) ?></td>
              <td class="text-secondary small" style="max-width: 320px;">
                <div class="text-truncate"><?= e($tool['description']) ?></div>
              </td>
              <td>
                <span class="badge" style="background-color: <?= match($tool['accent_color']){'terracotta'=>'#D2603A','goldHover'=>'#F0BB55','bronze'=>'#A9762B',default=>'#E3A93B'} ?>; color: #0E0B08; font-weight: bold;">
                  <?= e($tool['accent_color']) ?>
                </span>
              </td>
              <td class="text-center">
                <div class="form-check form-switch d-inline-block">
                  <input class="form-check-input ajax-toggle" type="checkbox" role="switch"
                         data-table="tools" data-id="<?= $tool['id'] ?>" data-column="is_active"
                         <?= $tool['is_active'] ? 'checked' : '' ?>>
                </div>
              </td>
              <td class="text-end pe-4">
                <button type="button" class="btn btn-sm btn-outline-warning me-1"
                        data-bs-toggle="modal" data-bs-target="#toolModal"
                        data-id="<?= $tool['id'] ?>"
                        data-name="<?= e($tool['name']) ?>"
                        data-description="<?= e($tool['description']) ?>"
                        data-icon="<?= e($tool['icon']) ?>"
                        data-color="<?= e($tool['accent_color']) ?>"
                        data-active="<?= $tool['is_active'] ?>"
                        onclick="editTool(this)">
                  <i class="bi bi-pencil"></i>
                </button>
                <form method="POST" action="education.php" class="d-inline form-delete" data-item-name="<?= e($tool['name']) ?>">
                  <?= Csrf::field() ?>
                  <input type="hidden" name="target" value="tool">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= $tool['id'] ?>">
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

<!-- Modal Thêm / Sửa Học vấn -->
<div class="modal fade" id="eduModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content card-admin border-secondary">
      <div class="modal-header border-secondary">
        <h5 class="modal-title text-white fw-bold" id="eduModalTitle">
          <i class="bi bi-plus-circle text-warning me-2"></i>Thêm học vấn mới
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Đóng"></button>
      </div>
      <form method="POST" action="education.php">
        <?= Csrf::field() ?>
        <input type="hidden" name="target" value="education">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" id="edu_id" value="">

        <div class="modal-body space-y-3">
          <div class="mb-3">
            <label class="form-label" for="edu_school">Tên trường / Cơ sở đào tạo <span class="text-danger">*</span></label>
            <input type="text" name="school" id="edu_school" class="form-control" placeholder="Ví dụ: Cao Đẳng Thực Hành" required>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label" for="edu_major">Chuyên ngành</label>
              <input type="text" name="major" id="edu_major" class="form-control" placeholder="Ví dụ: IT - Phần Mềm">
            </div>
            <div class="col-md-6">
              <label class="form-label" for="edu_period">Thời gian học <span class="text-danger">*</span></label>
              <input type="text" name="period_text" id="edu_period" class="form-control" placeholder="Ví dụ: 07/2017 — 09/2020" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label" for="edu_description">Mô tả giá trị / Kiến thức trang bị</label>
            <textarea name="description" id="edu_description" rows="3" class="form-control" placeholder="Trang bị tư duy logic vững vàng..."></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label" for="edu_footer">Dòng tóm tắt Footer Note</label>
            <input type="text" name="footer_text" id="edu_footer" class="form-control" placeholder="Tư duy logic • Khả năng tiếp cận công nghệ số nhanh">
          </div>

          <div class="form-check form-switch pt-2">
            <input class="form-check-input" type="checkbox" name="is_active" id="edu_active" value="1" checked>
            <label class="form-check-label text-light" for="edu_active">Kích hoạt hiển thị</label>
          </div>
        </div>

        <div class="modal-footer border-secondary">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
          <button type="submit" class="btn btn-gold">Lưu học vấn</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Thêm / Sửa Công cụ -->
<div class="modal fade" id="toolModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content card-admin border-secondary">
      <div class="modal-header border-secondary">
        <h5 class="modal-title text-white fw-bold" id="toolModalTitle">
          <i class="bi bi-plus-circle text-warning me-2"></i>Thêm công cụ mới
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Đóng"></button>
      </div>
      <form method="POST" action="education.php">
        <?= Csrf::field() ?>
        <input type="hidden" name="target" value="tool">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" id="tool_id" value="">

        <div class="modal-body space-y-3">
          <div class="mb-3">
            <label class="form-label" for="tool_name">Tên công cụ / Nền tảng <span class="text-danger">*</span></label>
            <input type="text" name="name" id="tool_name" class="form-control" placeholder="Ví dụ: Canva Pro, Shopee & Lazada, MS Excel" required>
          </div>

          <div class="mb-3">
            <label class="form-label" for="tool_description">Mô tả ứng dụng thực tế <span class="text-danger">*</span></label>
            <textarea name="description" id="tool_description" rows="3" class="form-control" placeholder="Thiết kế hình ảnh, quản lý đơn hàng..." required></textarea>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label" for="tool_icon">Biểu tượng Icon</label>
              <select name="icon" id="tool_icon" class="form-select">
                <option value="edit">Bút vẽ / Canva (edit)</option>
                <option value="shopping-cart">Giỏ hàng / Sàn TMĐT (shopping-cart)</option>
                <option value="file-text">Văn bản / Excel & Word (file-text)</option>
                <option value="message-circle">Chat / Zalo OA & Fanpage (message-circle)</option>
                <option value="star">Ngôi sao (star)</option>
                <option value="search">Tìm kiếm (search)</option>
                <option value="shield">Bảo mật (shield)</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="tool_color">Màu nhấn</label>
              <select name="accent_color" id="tool_color" class="form-select">
                <option value="gold">Vàng Gold (#E3A93B)</option>
                <option value="terracotta">Cam Đất / Terracotta (#D2603A)</option>
                <option value="goldHover">Vàng Sáng / Gold Hover (#F0BB55)</option>
                <option value="bronze">Đồng / Bronze (#A9762B)</option>
              </select>
            </div>
          </div>

          <div class="form-check form-switch pt-2">
            <input class="form-check-input" type="checkbox" name="is_active" id="tool_active" value="1" checked>
            <label class="form-check-label text-light" for="tool_active">Kích hoạt hiển thị</label>
          </div>
        </div>

        <div class="modal-footer border-secondary">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
          <button type="submit" class="btn btn-gold">Lưu công cụ</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function editEdu(btn) {
  document.getElementById('eduModalTitle').innerHTML = '<i class="bi bi-pencil-square text-warning me-2"></i>Sửa học vấn';
  document.getElementById('edu_id').value = btn.getAttribute('data-id');
  document.getElementById('edu_school').value = btn.getAttribute('data-school');
  document.getElementById('edu_degree').value = btn.getAttribute('data-degree');
  document.getElementById('edu_major').value = btn.getAttribute('data-major');
  document.getElementById('edu_period').value = btn.getAttribute('data-period');
  document.getElementById('edu_description').value = btn.getAttribute('data-description');
  document.getElementById('edu_footer').value = btn.getAttribute('data-footer');
  document.getElementById('edu_active').checked = btn.getAttribute('data-active') == '1';
}

document.getElementById('eduModal').addEventListener('hidden.bs.modal', function () {
  document.getElementById('eduModalTitle').innerHTML = '<i class="bi bi-plus-circle text-warning me-2"></i>Thêm học vấn mới';
  document.getElementById('edu_id').value = '';
  document.getElementById('edu_school').value = '';
  document.getElementById('edu_degree').value = '';
  document.getElementById('edu_major').value = '';
  document.getElementById('edu_period').value = '';
  document.getElementById('edu_description').value = '';
  document.getElementById('edu_footer').value = '';
  document.getElementById('edu_active').checked = true;
});

function editTool(btn) {
  document.getElementById('toolModalTitle').innerHTML = '<i class="bi bi-pencil-square text-warning me-2"></i>Sửa công cụ';
  document.getElementById('tool_id').value = btn.getAttribute('data-id');
  document.getElementById('tool_name').value = btn.getAttribute('data-name');
  document.getElementById('tool_description').value = btn.getAttribute('data-description');
  document.getElementById('tool_icon').value = btn.getAttribute('data-icon');
  document.getElementById('tool_color').value = btn.getAttribute('data-color');
  document.getElementById('tool_active').checked = btn.getAttribute('data-active') == '1';
}

document.getElementById('toolModal').addEventListener('hidden.bs.modal', function () {
  document.getElementById('toolModalTitle').innerHTML = '<i class="bi bi-plus-circle text-warning me-2"></i>Thêm công cụ mới';
  document.getElementById('tool_id').value = '';
  document.getElementById('tool_name').value = '';
  document.getElementById('tool_description').value = '';
  document.getElementById('tool_icon').value = 'edit';
  document.getElementById('tool_color').value = 'gold';
  document.getElementById('tool_active').checked = true;
});
</script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
