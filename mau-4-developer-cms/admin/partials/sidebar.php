<?php
/**
 * ADMIN SIDEBAR PARTIAL
 */
$activeMenu = $activeMenu ?? 'dashboard';
?>
<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block admin-sidebar collapse px-3 py-3">
  <div class="position-sticky pt-2">
    
    <div class="text-uppercase text-secondary fw-bold px-2 mb-2" style="font-size: 0.7rem; letter-spacing: 1px;">
      Tổng quan
    </div>
    <ul class="nav flex-column mb-3">
      <li class="nav-item">
        <a class="sidebar-link <?= $activeMenu === 'dashboard' ? 'active' : '' ?>" href="index.php">
          <i class="bi bi-speedometer2 fs-5"></i>
          <span>Dashboard</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="sidebar-link <?= $activeMenu === 'sections' ? 'active' : '' ?>" href="sections.php">
          <i class="bi bi-layout-wtf fs-5"></i>
          <span>Quản lý Section</span>
        </a>
      </li>
    </ul>

    <div class="text-uppercase text-secondary fw-bold px-2 mb-2" style="font-size: 0.7rem; letter-spacing: 1px;">
      Nội dung CV
    </div>
    <ul class="nav flex-column mb-3">
      <li class="nav-item">
        <a class="sidebar-link <?= $activeMenu === 'profile' ? 'active' : '' ?>" href="profile.php">
          <i class="bi bi-person-badge fs-5"></i>
          <span>Hồ sơ &amp; Hero</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="sidebar-link <?= $activeMenu === 'stats' ? 'active' : '' ?>" href="stats.php">
          <i class="bi bi-bar-chart-line fs-5"></i>
          <span>Chỉ số thống kê</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="sidebar-link <?= $activeMenu === 'skills' ? 'active' : '' ?>" href="skills.php">
          <i class="bi bi-lightning-charge fs-5"></i>
          <span>Kỹ năng cốt lõi</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="sidebar-link <?= $activeMenu === 'strengths' ? 'active' : '' ?>" href="strengths.php">
          <i class="bi bi-award fs-5"></i>
          <span>Điểm mạnh &amp; Kỷ luật</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="sidebar-link <?= $activeMenu === 'experience' ? 'active' : '' ?>" href="experience.php">
          <i class="bi bi-briefcase fs-5"></i>
          <span>Kinh nghiệm làm việc</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="sidebar-link <?= $activeMenu === 'education' ? 'active' : '' ?>" href="education.php">
          <i class="bi bi-mortarboard fs-5"></i>
          <span>Học vấn &amp; Công cụ</span>
        </a>
      </li>
    </ul>

    <div class="text-uppercase text-secondary fw-bold px-2 mb-2" style="font-size: 0.7rem; letter-spacing: 1px;">
      Hệ thống
    </div>
    <ul class="nav flex-column mb-3">
      <li class="nav-item">
        <a class="sidebar-link <?= $activeMenu === 'messages' ? 'active' : '' ?> d-flex justify-content-between align-items-center" href="messages.php">
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-chat-left-dots fs-5"></i>
            <span>Tin nhắn</span>
          </div>
          <?php if (!empty($unreadCount) && $unreadCount > 0): ?>
            <span class="badge bg-danger rounded-pill"><?= $unreadCount ?></span>
          <?php endif; ?>
        </a>
      </li>
      <li class="nav-item">
        <a class="sidebar-link <?= $activeMenu === 'settings' ? 'active' : '' ?>" href="settings.php">
          <i class="bi bi-sliders fs-5"></i>
          <span>Cài đặt &amp; SEO</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="sidebar-link <?= $activeMenu === 'account' ? 'active' : '' ?>" href="account.php">
          <i class="bi bi-shield-lock fs-5"></i>
          <span>Tài khoản Admin</span>
        </a>
      </li>
    </ul>

    <hr class="border-secondary my-3">
    
    <div class="px-2">
      <form method="POST" action="logout.php" class="m-0 p-0">
        <?= Csrf::field() ?>
        <button type="submit" class="btn btn-outline-danger btn-sm w-100 d-flex align-items-center justify-content-center gap-2">
          <i class="bi bi-box-arrow-right"></i>
          <span>Đăng xuất</span>
        </button>
      </form>
    </div>


  </div>
</nav>
