<?php
/**
 * ĐĂNG XUẤT ADMIN (POST + CSRF)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Csrf.php';
require_once __DIR__ . '/../app/helpers.php';

// Nếu là request GET -> Chuyển hướng an toàn về trang login kèm flash
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    flash('warning', 'Vui lòng sử dụng nút Đăng xuất trong hệ thống quản trị để đảm bảo an toàn.');
    redirect('admin/login.php');
}

// Xác thực CSRF Token
Csrf::validateRequest();

Auth::logout();
flash('success', 'Bạn đã đăng xuất an toàn.');
redirect('admin/login.php');
