<?php
/**
 * TRANG QUẢN LÝ TIN NHẮN LIÊN HỆ (MESSAGES)
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../app/Database.php';
require_once __DIR__ . '/../../app/Auth.php';
require_once __DIR__ . '/../../app/Csrf.php';
require_once __DIR__ . '/../../app/Repository.php';
require_once __DIR__ . '/../../app/helpers.php';


Auth::requireAuth();

// Xử lý Thao tác: Đánh dấu đã đọc / Xóa
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::validateRequest();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($id > 0) {
        if ($action === 'mark_read') {
            Repository::markMessageAsRead($id, 1);
            flash('success', 'Đã đánh dấu tin nhắn là đã đọc.');
        } elseif ($action === 'mark_unread') {
            Repository::markMessageAsRead($id, 0);
            flash('success', 'Đã đánh dấu tin nhắn là chưa đọc.');
        } elseif ($action === 'delete') {
            Repository::deleteMessage($id);
            flash('success', 'Đã xóa tin nhắn thành công.');
        }
    }

    redirect('admin/messages.php');
}

$messages = Repository::getMessages(100);

$pageTitle = 'Tin nhắn liên hệ';
$activeMenu = 'messages';
require_once __DIR__ . '/partials/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h2 class="h3 fw-bold text-white mb-1">Hộp thư liên hệ (Messages)</h2>
    <p class="text-secondary small mb-0">Danh sách các tin nhắn từ khách hàng hoặc nhà tuyển dụng</p>
  </div>
</div>

<div class="card-admin">
  <div class="card-admin-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0 fw-bold text-white"><i class="bi bi-chat-dots text-warning me-2"></i>Tất cả tin nhắn (<?= count($messages) ?>)</h5>
    <?php if ($unreadCount > 0): ?>
      <span class="badge bg-danger"><?= $unreadCount ?> tin chưa đọc</span>
    <?php endif; ?>
  </div>
  <div class="table-responsive">
    <table class="table table-dark-custom mb-0 align-middle">
      <thead>
        <tr>
          <th style="width: 50px;" class="ps-4">Trạng thái</th>
          <th>Người gửi</th>
          <th>Liên hệ</th>
          <th>Nội dung lời nhắn</th>
          <th>Địa chỉ IP</th>
          <th>Thời gian</th>
          <th class="text-end pe-4" style="width: 180px;">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($messages)): ?>
          <tr>
            <td colspan="7" class="text-center py-5 text-secondary">Hộp thư hiện đang trống.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($messages as $msg): ?>
            <tr class="<?= !$msg['is_read'] ? 'bg-warning bg-opacity-10' : '' ?>">
              <td class="ps-4">
                <?php if (!$msg['is_read']): ?>
                  <span class="badge bg-danger rounded-circle p-2" title="Chưa đọc"><span class="visually-hidden">Mới</span></span>
                <?php else: ?>
                  <i class="bi bi-check2-all text-secondary" title="Đã xem"></i>
                <?php endif; ?>
              </td>
              <td>
                <span class="fw-bold <?= !$msg['is_read'] ? 'text-warning' : 'text-white' ?>">
                  <?= e($msg['name']) ?>
                </span>
              </td>
              <td>
                <div><a href="mailto:<?= e($msg['email']) ?>" class="text-light text-decoration-none small"><?= e($msg['email']) ?></a></div>
                <?php if (!empty($msg['phone'])): ?>
                  <div><a href="tel:<?= e($msg['phone']) ?>" class="text-secondary text-decoration-none small"><i class="bi bi-telephone me-1"></i><?= e($msg['phone']) ?></a></div>
                <?php endif; ?>
              </td>
              <td style="max-width: 320px;">
                <div class="text-truncate text-secondary"><?= e($msg['message']) ?></div>
              </td>
              <td class="text-secondary small font-monospace"><?= e($msg['ip'] ?: '—') ?></td>
              <td class="text-secondary small"><?= date('d/m/Y H:i', strtotime($msg['created_at'])) ?></td>
              <td class="text-end pe-4">
                <!-- Nút Xem chi tiết -->
                <button type="button" class="btn btn-sm btn-outline-warning me-1" 
                        data-bs-toggle="modal" data-bs-target="#viewModal<?= $msg['id'] ?>"
                        onclick="markReadAuto(<?= $msg['id'] ?>, <?= $msg['is_read'] ?>)">
                  <i class="bi bi-eye"></i>
                </button>

                <!-- Nút Đổi trạng thái đã đọc/chưa đọc -->
                <form method="POST" action="messages.php" class="d-inline">
                  <?= Csrf::field() ?>
                  <input type="hidden" name="action" value="<?= $msg['is_read'] ? 'mark_unread' : 'mark_read' ?>">
                  <input type="hidden" name="id" value="<?= $msg['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-secondary me-1" title="<?= $msg['is_read'] ? 'Đánh dấu chưa đọc' : 'Đánh dấu đã đọc' ?>">
                    <i class="bi <?= $msg['is_read'] ? 'bi-envelope' : 'bi-envelope-open' ?>"></i>
                  </button>
                </form>

                <!-- Nút Xóa -->
                <form method="POST" action="messages.php" class="d-inline form-delete" data-item-name="tin nhắn từ <?= e($msg['name']) ?>">
                  <?= Csrf::field() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= $msg['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-trash"></i>
                  </button>
                </form>
              </td>
            </tr>

            <!-- Modal Chi Tiết Tin Nhắn -->
            <div class="modal fade" id="viewModal<?= $msg['id'] ?>" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content card-admin border-secondary">
                  <div class="modal-header border-secondary">
                    <h5 class="modal-title text-white fw-bold">
                      <i class="bi bi-chat-left-text text-warning me-2"></i>Tin nhắn từ <?= e($msg['name']) ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Đóng"></button>
                  </div>
                  <div class="modal-body">
                    <div class="mb-3 p-3 rounded bg-dark border border-secondary border-opacity-25 small">
                      <div class="mb-1"><strong class="text-warning">Người gửi:</strong> <?= e($msg['name']) ?></div>
                      <div class="mb-1"><strong class="text-warning">Email:</strong> <a href="mailto:<?= e($msg['email']) ?>" class="text-info"><?= e($msg['email']) ?></a></div>
                      <?php if (!empty($msg['phone'])): ?>
                        <div class="mb-1"><strong class="text-warning">SĐT:</strong> <a href="tel:<?= e($msg['phone']) ?>" class="text-info"><?= e($msg['phone']) ?></a></div>
                      <?php endif; ?>
                      <div class="mb-1"><strong class="text-warning">Thời gian:</strong> <?= date('d/m/Y H:i:s', strtotime($msg['created_at'])) ?></div>
                      <div><strong class="text-warning">IP Address:</strong> <?= e($msg['ip'] ?: 'Unknown') ?></div>
                    </div>

                    <div>
                      <h6 class="fw-bold text-white mb-2">Nội dung:</h6>
                      <div class="p-3 rounded bg-dark border border-secondary text-light leading-relaxed" style="white-space: pre-line;">
                        <?= e($msg['message']) ?>
                      </div>
                    </div>
                  </div>
                  <div class="modal-footer border-secondary">
                    <a href="mailto:<?= e($msg['email']) ?>?subject=Phản hồi liên hệ từ <?= urlencode($profile['full_name']) ?>" class="btn btn-gold">
                      <i class="bi bi-reply me-1"></i> Trả lời qua Email
                    </a>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button>
                  </div>
                </div>
              </div>
            </div>

          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
function markReadAuto(id, isRead) {
  if (isRead === 0) {
    fetch('ajax/toggle.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({
        table: 'contact_messages',
        id: id,
        column: 'is_read'
      })
    });
  }
}
</script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
