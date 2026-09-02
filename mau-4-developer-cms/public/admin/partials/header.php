<?php
/**
 * ADMIN HEADER PARTIAL
 */

if (!defined('ROOT_PATH')) {
    require_once __DIR__ . '/../../../config/config.php';
    require_once __DIR__ . '/../../../app/Database.php';
    require_once __DIR__ . '/../../../app/Auth.php';
    require_once __DIR__ . '/../../../app/Csrf.php';
    require_once __DIR__ . '/../../../app/Repository.php';
    require_once __DIR__ . '/../../../app/helpers.php';
}

Auth::requireAuth();

$unreadCount = Repository::countUnreadMessages();
$currentUser = Auth::user();
$pageTitle = $pageTitle ?? 'Quản trị hệ thống';
$activeMenu = $activeMenu ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?> — Quản trị CV Mẫu 4</title>
  <?= Csrf::meta() ?>

  <!-- Local Vendor CSS: Bootstrap 5.3.3 & Bootstrap Icons 1.11.3 -->
  <link rel="stylesheet" href="<?= asset('vendor/bootstrap/bootstrap.min.css') ?>">
  <link rel="stylesheet" href="<?= asset('vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
  <!-- Local Self-hosted Fonts (Plus Jakarta Sans & Space Grotesk) -->
  <link rel="stylesheet" href="<?= asset('fonts/fonts.css') ?>">


  <style>
    :root {
      --admin-bg: #0F1218;
      --admin-sidebar: #161B22;
      --admin-card: #1B222C;
      --admin-border: #2B3544;
      --admin-gold: #E3A93B;
      --admin-gold-hover: #F0BB55;
      --admin-text: #E6EDF3;
      --admin-muted: #8B949E;
    }

    body {
      font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      background-color: var(--admin-bg);
      color: var(--admin-text);
      min-height: 100vh;
    }


    .admin-navbar {
      background-color: var(--admin-sidebar);
      border-bottom: 1px solid var(--admin-border);
    }

    .admin-sidebar {
      background-color: var(--admin-sidebar);
      border-right: 1px solid var(--admin-border);
      min-height: calc(100vh - 60px);
    }

    .sidebar-link {
      color: var(--admin-muted);
      border-radius: 8px;
      padding: 10px 14px;
      display: flex;
      align-items: center;
      gap: 12px;
      font-weight: 500;
      text-decoration: none;
      transition: all 0.2s ease;
      margin-bottom: 3px;
    }

    .sidebar-link:hover {
      color: var(--admin-text);
      background-color: rgba(227, 169, 59, 0.1);
    }

    .sidebar-link.active {
      color: #0E0B08;
      background: linear-gradient(135deg, #E3A93B 0%, #F0BB55 100%);
      font-weight: 700;
    }

    .card-admin {
      background-color: var(--admin-card);
      border: 1px solid var(--admin-border);
      border-radius: 12px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
    }

    .card-admin-header {
      background-color: rgba(255, 255, 255, 0.02);
      border-bottom: 1px solid var(--admin-border);
      padding: 16px 20px;
    }

    .form-control, .form-select {
      background-color: #12161D !important;
      border: 1px solid var(--admin-border) !important;
      color: var(--admin-text) !important;
      border-radius: 8px;
    }

    .form-control:focus, .form-select:focus {
      border-color: var(--admin-gold) !important;
      box-shadow: 0 0 0 3px rgba(227, 169, 59, 0.25) !important;
    }

    .form-label {
      color: #B3A488;
      font-weight: 600;
      font-size: 0.875rem;
      margin-bottom: 6px;
    }

    .btn-gold {
      background: linear-gradient(135deg, #E3A93B 0%, #F0BB55 100%);
      color: #0E0B08;
      font-weight: 700;
      border: none;
      border-radius: 8px;
      padding: 8px 18px;
      transition: all 0.2s;
    }

    .btn-gold:hover {
      background: linear-gradient(135deg, #F0BB55 0%, #FFF0D0 100%);
      color: #0E0B08;
      box-shadow: 0 4px 15px rgba(227, 169, 59, 0.35);
    }

    .table-dark-custom {
      --bs-table-bg: transparent;
      --bs-table-color: var(--admin-text);
      --bs-table-border-color: var(--admin-border);
    }

    .table-dark-custom th {
      color: var(--admin-muted);
      font-size: 0.8rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      background-color: rgba(255, 255, 255, 0.02);
    }

    .drag-handle {
      cursor: grab;
      color: var(--admin-muted);
    }

    .drag-handle:active {
      cursor: grabbing;
    }

    .sortable-ghost {
      opacity: 0.4;
      background-color: rgba(227, 169, 59, 0.15) !important;
    }

    .avatar-preview {
      width: 110px;
      height: 110px;
      object-fit: cover;
      border-radius: 12px;
      border: 2px solid var(--admin-gold);
    }
  </style>
</head>
<body>

  <!-- Top Navbar -->
  <nav class="navbar navbar-expand-lg admin-navbar sticky-top px-3 py-2">
    <div class="container-fluid">
      <a class="navbar-brand d-flex align-items-center gap-2 text-white fw-bold" href="index.php">
        <span class="badge bg-warning text-dark px-2 py-1 fs-6 fw-bold">CV-CMS</span>
        <span class="d-none d-sm-inline fs-6 text-warning">Mẫu 4 Developer</span>
      </a>

      <div class="d-flex align-items-center gap-3 ms-auto">
        <a href="<?= url() ?>" target="_blank" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1 text-light border-secondary">
          <i class="bi bi-box-arrow-up-right"></i>
          <span class="d-none d-md-inline">Xem CV Public</span>
        </a>

        <a href="messages.php" class="btn btn-sm btn-outline-secondary position-relative border-secondary text-light">
          <i class="bi bi-envelope"></i>
          <?php if ($unreadCount > 0): ?>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
              <?= $unreadCount ?>
            </span>
          <?php endif; ?>
        </a>

        <div class="dropdown">
          <button class="btn btn-sm btn-outline-dark dropdown-toggle text-light border-secondary d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown">
            <i class="bi bi-person-circle text-warning"></i>
            <span><?= e($currentUser['full_name'] ?? 'Admin') ?></span>
          </button>
          <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark shadow">
            <li><a class="dropdown-item" href="account.php"><i class="bi bi-key me-2"></i>Đổi mật khẩu / Tài khoản</a></li>
            <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear me-2"></i>Cài đặt hệ thống</a></li>
            <li><hr class="dropdown-divider"></li>

            <li>
              <form method="POST" action="logout.php" class="m-0 p-0">
                <?= Csrf::field() ?>
                <button type="submit" class="dropdown-item text-danger d-flex align-items-center">
                  <i class="bi bi-box-arrow-right me-2"></i>Đăng xuất
                </button>
              </form>
            </li>
          </ul>

        </div>
      </div>
    </div>
  </nav>

  <!-- Main Layout Wrapper -->
  <div class="container-fluid">
    <div class="row">
      <!-- Sidebar -->
      <?php require __DIR__ . '/sidebar.php'; ?>

      <!-- Main Content Area -->
      <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
        <?= render_flash() ?>
