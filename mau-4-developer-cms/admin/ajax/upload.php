<?php
/**
 * AJAX ENDPOINT: Tải ảnh lên riêng lẻ
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../app/Database.php';
require_once __DIR__ . '/../../app/Auth.php';
require_once __DIR__ . '/../../app/Csrf.php';
require_once __DIR__ . '/../../app/Upload.php';
require_once __DIR__ . '/../../app/helpers.php';

if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập!']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ!']);
    exit;
}

Csrf::validateRequest();

if (!isset($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy file tải lên!']);
    exit;
}

$prefix = trim($_POST['prefix'] ?? 'image');
$result = Upload::processImage($_FILES['file'], $prefix);

if ($result['success']) {
    echo json_encode([
        'success' => true,
        'path'    => $result['path'],
        'url'     => upload_url($result['path']),
        'message' => $result['message']
    ]);
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $result['message']]);
}
