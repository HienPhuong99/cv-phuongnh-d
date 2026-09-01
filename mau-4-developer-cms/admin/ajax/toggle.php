<?php
/**
 * AJAX ENDPOINT: Bật/tắt trạng thái hiển thị (is_active / is_visible)
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../app/Database.php';
require_once __DIR__ . '/../../app/Auth.php';
require_once __DIR__ . '/../../app/Csrf.php';
require_once __DIR__ . '/../../app/Repository.php';
require_once __DIR__ . '/../../app/helpers.php';

if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập!']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Phương thức không được hỗ trợ!']);
    exit;
}

Csrf::validateRequest();

$input  = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$table  = trim($input['table'] ?? '');
$id     = (int)($input['id'] ?? 0);
$column = trim($input['column'] ?? 'is_active');

if (empty($table) || $id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ!']);
    exit;
}

$newStatus = Repository::toggle($table, $id, $column);

if ($newStatus !== null) {
    echo json_encode([
        'success'    => true,
        'new_status' => $newStatus,
        'message'    => $newStatus ? 'Đã bật hiển thị' : 'Đã ẩn mục này'
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Không thể cập nhật trạng thái!']);
}
