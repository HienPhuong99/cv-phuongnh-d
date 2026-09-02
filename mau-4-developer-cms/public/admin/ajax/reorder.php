<?php
/**
 * AJAX ENDPOINT: Sắp xếp thứ tự các mục (SortableJS)
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../app/Database.php';
require_once __DIR__ . '/../../../app/Auth.php';
require_once __DIR__ . '/../../../app/Csrf.php';
require_once __DIR__ . '/../../../app/Repository.php';
require_once __DIR__ . '/../../../app/helpers.php';

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

// Kiểm tra CSRF Token từ header hoặc body
Csrf::validateRequest();

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$table = trim($input['table'] ?? '');
$ids   = $input['ids'] ?? [];

if (empty($table) || !is_array($ids)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ!']);
    exit;
}

$success = Repository::reorder($table, $ids);

if ($success) {
    // KHÔNG gọi touch_content_changed() ở đây: kéo thả sắp xếp sinh một request mỗi lần
    // thả chuột, và thứ tự hiển thị trên web không ảnh hưởng tới file PDF xuất từ TopCV.
    echo json_encode(['success' => true, 'message' => 'Đã lưu thứ tự sắp xếp thành công!']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật thứ tự trong cơ sở dữ liệu!']);
}
