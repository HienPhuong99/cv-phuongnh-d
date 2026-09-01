<?php
/**
 * TRANG CHỦ CV MẪU 4 DEVELOPER (PUBLIC VIEW)
 * Render dữ liệu động từ Database MySQL nhưng giữ nguyên 100% HTML/CSS/JS gốc
 */

// Không khởi chạy session cho khách truy cập public
define('NO_SESSION', true);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/Database.php';
require_once __DIR__ . '/app/Auth.php';
require_once __DIR__ . '/app/Repository.php';
require_once __DIR__ . '/app/Csrf.php';
require_once __DIR__ . '/app/helpers.php';

// Lấy toàn bộ dữ liệu từ Database (với fallback an toàn không lộ chi tiết nhạy cảm trên production)
try {
    $settings    = Repository::getSettings();
    $profile     = Repository::getProfile();
    $sections    = Repository::getSectionMap();
    $keyStats    = Repository::getKeyStats(true);
    $skills      = Repository::getSkills(true);
    $strengths   = Repository::getStrengths(true);
    $experiences = Repository::getExperiences(true);
    $educations  = Repository::getEducations(true);
    $tools       = Repository::getTools(true);
} catch (Throwable $e) {
    error_log("Database connection/query error in index.php: " . $e->getMessage());
    $isDev = (defined('APP_ENV') && APP_ENV === 'development');
    $errorDetail = $isDev ? '<p><small style="color:#888;">Chi tiết lỗi: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</small></p>' : '';
    die('<div style="padding:40px;font-family:sans-serif;background:#111;color:#eee;max-width:600px;margin:50px auto;border-radius:12px;border:1px solid #443;">
        <h2 style="color:#E3A93B;">⚠️ Hệ thống đang bảo trì</h2>
        <p>Không thể kết nối đến cơ sở dữ liệu. Vui lòng quay lại sau ít phút.</p>
        ' . $errorDetail . '
    </div>');
}

// Dọn dẹp bản ghi throttle cũ ngẫu nhiên (1/100 request)
if (mt_rand(1, 100) === 1) {
    Repository::cleanupContactThrottle();
}

// Xử lý gửi tin nhắn liên hệ (Antispam: Honeypot, Time-trap, IP-throttle, Length validation)
$contactSuccess = false;
$contactError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_message') {
    $ip = Auth::getClientIp();

    // 1. Honeypot check: nếu field website có giá trị -> giả vờ thành công với bot
    $honeypot = trim($_POST['website'] ?? '');
    if ($honeypot !== '') {
        $contactSuccess = true;
    } else {
        // 2. Time-trap & Stateless HMAC Token check (hợp lệ trong khoảng 3s -> 2h)
        $token = $_POST['_contact_token'] ?? '';
        $renderTime = (int)($_POST['_render_time'] ?? 0);

        if (!Csrf::verifyPublicToken($token, $renderTime)) {
            $contactError = 'Yêu cầu không hợp lệ hoặc biểu mẫu đã hết hạn. Vui lòng tải lại trang và thử lại.';
        } elseif (!Repository::checkContactThrottle($ip)) {
            // 3. IP Throttle check (tối đa 3 tin/giờ, 10 tin/24h)
            $contactError = 'Hệ thống đang tạm thời giới hạn lượt gửi tin nhắn từ bạn. Vui lòng thử lại sau.';
        } else {
            // 4. Validate dữ liệu đầu vào và giới hạn độ dài chuỗi
            $name    = trim($_POST['name'] ?? '');
            $email   = trim($_POST['email'] ?? '');
            $phone   = trim($_POST['phone'] ?? '');
            $message = trim($_POST['message'] ?? '');

            if (empty($name) || empty($email) || empty($message)) {
                $contactError = 'Vui lòng điền đầy đủ họ tên, email và lời nhắn.';
            } elseif (mb_strlen($name, 'UTF-8') > 100) {
                $contactError = 'Họ và tên không được vượt quá 100 ký tự.';
            } elseif (mb_strlen($email, 'UTF-8') > 191) {
                $contactError = 'Địa chỉ email không được vượt quá 191 ký tự.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $contactError = 'Địa chỉ email không đúng định dạng.';
            } elseif (mb_strlen($phone, 'UTF-8') > 50) {
                $contactError = 'Số điện thoại không được vượt quá 50 ký tự.';
            } elseif (mb_strlen($message, 'UTF-8') > 5000) {
                $contactError = 'Nội dung lời nhắn không được vượt quá 5000 ký tự.';
            } else {
                // 5. Thêm tin nhắn với try/catch an toàn
                try {
                    Repository::createMessage([
                        'name'    => $name,
                        'email'   => $email,
                        'phone'   => $phone,
                        'message' => $message,
                        'ip'      => $ip
                    ]);
                    Repository::recordContactThrottle($ip);
                    $contactSuccess = true;
                } catch (Throwable $e) {
                    error_log("Lỗi tạo tin nhắn liên hệ: " . $e->getMessage());
                    $contactError = 'Có lỗi xảy ra trong quá trình gửi tin nhắn. Vui lòng thử lại sau.';
                }
            }
        }
    }

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => $contactSuccess,
            'message' => $contactSuccess ? 'Cảm ơn bạn! Lời nhắn đã được gửi thành công.' : $contactError
        ]);
        exit;
    }
}

// Sinh timestamp & Stateless token cho form render lần này
$renderTimestamp = time();
$contactToken = Csrf::generatePublicToken($renderTimestamp);

// Helper xác định màu sắc hiển thị
function get_color_class(string $colorName): array {
    return match ($colorName) {
        'terracotta' => [
            'text'   => 'text-[#D2603A]',
            'bg'     => 'text-[#D2603A]',
            'border' => 'border-[#D2603A]',
            'dot'    => 'border-[#D2603A] group-hover:bg-[#D2603A] group-hover:shadow-[0_0_12px_#D2603A]'
        ],
        'goldHover'  => [
            'text'   => 'text-[#F0BB55]',
            'bg'     => 'text-[#F0BB55]',
            'border' => 'border-[#F0BB55]',
            'dot'    => 'border-[#F0BB55] group-hover:bg-[#F0BB55] group-hover:shadow-[0_0_12px_#F0BB55]'
        ],
        'bronze'     => [
            'text'   => 'text-[#A9762B]',
            'bg'     => 'text-[#A9762B]',
            'border' => 'border-[#A9762B]',
            'dot'    => 'border-[#A9762B] group-hover:bg-[#A9762B] group-hover:shadow-[0_0_12px_#A9762B]'
        ],
        default      => [ // gold
            'text'   => 'text-[#E3A93B]',
            'bg'     => 'text-[#E3A93B]',
            'border' => 'border-[#E3A93B]',
            'dot'    => 'border-[#E3A93B] group-hover:bg-[#E3A93B] group-hover:shadow-[0_0_12px_#E3A93B]'
        ]
    };
}
?>
<!DOCTYPE html>
<html lang="vi" class="scroll-smooth">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= e($settings['site_title'] ?? ($profile['full_name'] . ' — ' . $profile['job_title'])) ?></title>
  <meta name="description" content="<?= e($settings['meta_description'] ?? $profile['tagline']) ?>" />
  <meta name="author" content="<?= e($settings['author'] ?? $profile['full_name']) ?>" />
  <meta name="theme-color" content="<?= e($settings['theme_color'] ?? '#0E0B08') ?>" />

  <!-- Static Pre-compiled Tailwind CSS -->
  <link rel="stylesheet" href="<?= asset('tailwind.min.css') ?>" />


  <!-- Custom Stylesheet 4 -->
  <link rel="stylesheet" href="<?= asset('style4.css') ?>" />
</head>

<body class="bg-[#0E0B08] text-[#F4ECDF] font-sans antialiased min-h-screen relative selection:bg-[#E3A93B]/30 selection:text-[#F4ECDF]">

  <!-- Ambient Glow Backgrounds -->
  <div class="ambient-gold-glow glow-top" aria-hidden="true"></div>
  <div class="ambient-gold-glow glow-middle-right" aria-hidden="true"></div>
  <div class="ambient-gold-glow glow-bottom-left" aria-hidden="true"></div>
  <div class="bg-warm-grid" aria-hidden="true"></div>

  <!-- ==========================================================================
       1. STICKY NAVIGATION BAR
       ========================================================================== -->
  <header class="sticky top-0 z-50 transition-all duration-300 px-4 sm:px-6 lg:px-8 pt-4 pb-2" id="mainNav">
    <div class="max-w-6xl mx-auto flex items-center justify-between bg-[#191309]/85 backdrop-blur-xl border border-[#33271A] rounded-full px-5 py-3 shadow-2xl transition-all duration-300">
      
      <!-- Brand Logo / Monogram -->
      <a href="#hero" class="flex items-center gap-3 group cursor-pointer focus-visible:ring-2 focus-visible:ring-[#E3A93B] rounded-full" aria-label="Lên đầu trang">
        <span class="w-8 h-8 rounded-full bg-gradient-to-br from-[#E3A93B] to-[#A9762B] text-[#0E0B08] font-heading font-extrabold text-sm flex items-center justify-center shadow-md group-hover:scale-105 transition-transform duration-200">
          <?= e($settings['monogram'] ?? 'HP') ?>
        </span>
        <span class="font-heading font-bold text-base tracking-tight text-[#F4ECDF] group-hover:text-[#E3A93B] transition-colors duration-200">
          <?= e($profile['full_name']) ?>
        </span>
      </a>

      <!-- Desktop Nav Links -->
      <nav class="hidden md:flex items-center gap-1" aria-label="Điều hướng chính">
        <?php if (!isset($sections['about']) || $sections['about']['is_visible']): ?>
          <a href="#about" class="nav-link-item px-3.5 py-1.5 rounded-full text-sm font-medium text-[#B3A488] hover:text-[#F4ECDF] hover:bg-[#241B0F] transition-all cursor-pointer">Về tôi</a>
        <?php endif; ?>
        <?php if (!isset($sections['skills']) || $sections['skills']['is_visible']): ?>
          <a href="#skills" class="nav-link-item px-3.5 py-1.5 rounded-full text-sm font-medium text-[#B3A488] hover:text-[#F4ECDF] hover:bg-[#241B0F] transition-all cursor-pointer">Kỹ năng</a>
        <?php endif; ?>
        <?php if (!isset($sections['experience']) || $sections['experience']['is_visible']): ?>
          <a href="#experience" class="nav-link-item px-3.5 py-1.5 rounded-full text-sm font-medium text-[#B3A488] hover:text-[#F4ECDF] hover:bg-[#241B0F] transition-all cursor-pointer">Kinh nghiệm</a>
        <?php endif; ?>
        <?php if (!isset($sections['strengths']) || $sections['strengths']['is_visible']): ?>
          <a href="#strengths" class="nav-link-item px-3.5 py-1.5 rounded-full text-sm font-medium text-[#B3A488] hover:text-[#F4ECDF] hover:bg-[#241B0F] transition-all cursor-pointer">Điểm mạnh</a>
        <?php endif; ?>
        <?php if (!isset($sections['education']) || $sections['education']['is_visible']): ?>
          <a href="#education" class="nav-link-item px-3.5 py-1.5 rounded-full text-sm font-medium text-[#B3A488] hover:text-[#F4ECDF] hover:bg-[#241B0F] transition-all cursor-pointer">Học vấn &amp; Công cụ</a>
        <?php endif; ?>
        <?php if (!isset($sections['contact']) || $sections['contact']['is_visible']): ?>
          <a href="#contact" class="nav-link-item px-3.5 py-1.5 rounded-full text-sm font-medium text-[#B3A488] hover:text-[#F4ECDF] hover:bg-[#241B0F] transition-all cursor-pointer">Liên hệ</a>
        <?php endif; ?>
      </nav>

      <!-- Action Buttons -->
      <div class="flex items-center gap-2 sm:gap-3">
        <?php if (!empty($profile['cv_pdf_file'])): ?>
          <a href="<?= upload_url($profile['cv_pdf_file']) ?>" target="_blank" download class="hidden sm:inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-semibold text-[#B3A488] hover:text-[#F4ECDF] bg-[#241B0F] hover:bg-[#2E2214] border border-[#33271A] hover:border-[#E3A93B]/40 rounded-full transition-all cursor-pointer" title="Tải CV dạng PDF">
            <svg class="w-3.5 h-3.5 text-[#E3A93B]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
              <polyline points="7 10 12 15 17 10"></polyline>
              <line x1="12" y1="15" x2="12" y2="3"></line>
            </svg>
            <span>Tải PDF</span>
          </a>
        <?php else: ?>
          <button type="button" id="btnPrintCV" class="hidden sm:inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-semibold text-[#B3A488] hover:text-[#F4ECDF] bg-[#241B0F] hover:bg-[#2E2214] border border-[#33271A] hover:border-[#E3A93B]/40 rounded-full transition-all cursor-pointer" title="In hoặc lưu hồ sơ dạng PDF">
            <svg class="w-3.5 h-3.5 text-[#E3A93B]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <polyline points="6 9 6 2 18 2 18 9"></polyline>
              <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
              <rect x="6" y="14" width="12" height="8"></rect>
            </svg>
            <span>In / PDF</span>
          </button>
        <?php endif; ?>

        <?php if (!empty($profile['phone'])): ?>
          <a href="tel:<?= e($profile['phone']) ?>" class="inline-flex items-center gap-1.5 px-4 py-1.5 text-xs sm:text-sm font-bold text-[#0E0B08] bg-gradient-to-r from-[#E3A93B] to-[#F0BB55] hover:from-[#F0BB55] hover:to-[#FFF0D0] rounded-full shadow-lg shadow-[#E3A93B]/20 hover:shadow-[#E3A93B]/40 hover:-translate-y-0.5 transition-all cursor-pointer">
            <span>Gọi ngay</span>
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
            </svg>
          </a>
        <?php endif; ?>

        <!-- Mobile Menu Hamburger Button -->
        <button type="button" id="mobileMenuBtn" class="md:hidden p-1.5 text-[#B3A488] hover:text-[#F4ECDF] rounded-full cursor-pointer focus-visible:ring-2 focus-visible:ring-[#E3A93B]" aria-expanded="false" aria-label="Mở menu điều hướng">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
          </svg>
        </button>
      </div>
    </div>

    <!-- Mobile Dropdown Menu -->
    <div id="mobileMenu" class="hidden md:hidden max-w-6xl mx-auto mt-2 p-4 bg-[#191309]/95 backdrop-blur-2xl border border-[#33271A] rounded-2xl shadow-2xl flex flex-col gap-2">
      <?php if (!isset($sections['about']) || $sections['about']['is_visible']): ?>
        <a href="#about" class="px-4 py-2 text-sm font-medium text-[#B3A488] hover:text-[#F4ECDF] hover:bg-[#241B0F] rounded-lg transition-colors cursor-pointer">Về tôi</a>
      <?php endif; ?>
      <?php if (!isset($sections['skills']) || $sections['skills']['is_visible']): ?>
        <a href="#skills" class="px-4 py-2 text-sm font-medium text-[#B3A488] hover:text-[#F4ECDF] hover:bg-[#241B0F] rounded-lg transition-colors cursor-pointer">Kỹ năng</a>
      <?php endif; ?>
      <?php if (!isset($sections['experience']) || $sections['experience']['is_visible']): ?>
        <a href="#experience" class="px-4 py-2 text-sm font-medium text-[#B3A488] hover:text-[#F4ECDF] hover:bg-[#241B0F] rounded-lg transition-colors cursor-pointer">Kinh nghiệm</a>
      <?php endif; ?>
      <?php if (!isset($sections['strengths']) || $sections['strengths']['is_visible']): ?>
        <a href="#strengths" class="px-4 py-2 text-sm font-medium text-[#B3A488] hover:text-[#F4ECDF] hover:bg-[#241B0F] rounded-lg transition-colors cursor-pointer">Điểm mạnh</a>
      <?php endif; ?>
      <?php if (!isset($sections['education']) || $sections['education']['is_visible']): ?>
        <a href="#education" class="px-4 py-2 text-sm font-medium text-[#B3A488] hover:text-[#F4ECDF] hover:bg-[#241B0F] rounded-lg transition-colors cursor-pointer">Học vấn &amp; Công cụ</a>
      <?php endif; ?>
      <?php if (!isset($sections['contact']) || $sections['contact']['is_visible']): ?>
        <a href="#contact" class="px-4 py-2 text-sm font-medium text-[#B3A488] hover:text-[#F4ECDF] hover:bg-[#241B0F] rounded-lg transition-colors cursor-pointer">Liên hệ</a>
      <?php endif; ?>
    </div>
  </header>

  <!-- Main Content Container -->
  <main class="relative z-10 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-24 sm:space-y-32">

    <!-- ==========================================================================
         2. HERO SECTION
         ========================================================================== -->
    <?php if (!isset($sections['hero']) || $sections['hero']['is_visible']): ?>
    <section id="hero" class="pt-6 sm:pt-12 pb-6">
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 sm:gap-12 items-center">
        
        <!-- Left Column: Intro & Headline (7 cols) -->
        <div class="lg:col-span-7 flex flex-col items-start gap-6">
          
          <!-- Status Pill -->
          <?php if (!empty($profile['status_badge'])): ?>
          <div class="inline-flex items-center gap-2.5 px-3.5 py-1.5 rounded-full bg-[#191309] border border-[#33271A] shadow-sm">
            <span class="status-dot-gold"></span>
            <span class="text-xs font-semibold tracking-wide text-[#B3A488] uppercase"><?= e($profile['status_badge']) ?></span>
          </div>
          <?php endif; ?>

          <!-- Hero Headline & Name -->
          <div class="space-y-2">
            <p class="font-mono text-sm sm:text-base font-medium text-[#E3A93B] tracking-wider uppercase">
              <?= e($profile['pre_title'] ?? "Hello, I'm") ?>
            </p>
            <h1 class="font-heading text-4xl sm:text-6xl lg:text-7xl font-extrabold tracking-tight text-[#F4ECDF] leading-[1.08]">
              <?= e($profile['full_name']) ?>
            </h1>
            <h2 class="font-heading text-2xl sm:text-4xl font-bold tracking-tight text-gold-gradient leading-[1.15]">
              <?= e($profile['job_title']) ?>
            </h2>
          </div>

          <!-- Short Intro Paragraph -->
          <?php if (!empty($profile['tagline'])): ?>
          <p class="text-base sm:text-lg text-[#B3A488] max-w-2xl leading-relaxed">
            <?= nl2br(e($profile['tagline'])) ?>
          </p>
          <?php endif; ?>

          <!-- CTA Buttons Row -->
          <div class="flex flex-wrap items-center gap-4 pt-2">
            <?php if (!isset($sections['experience']) || $sections['experience']['is_visible']): ?>
            <a href="#experience" class="btn-gold cursor-pointer">
              <span>Xem kinh nghiệm</span>
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <polyline points="19 12 12 19 5 12"></polyline>
              </svg>
            </a>
            <?php endif; ?>

            <?php if (!isset($sections['contact']) || $sections['contact']['is_visible']): ?>
            <a href="#contact" class="btn-terracotta cursor-pointer">
              <span>Liên hệ ngay</span>
              <svg class="w-4 h-4 text-[#D2603A]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                <polyline points="22,6 12,13 2,6"></polyline>
              </svg>
            </a>
            <?php endif; ?>

            <?php if (!empty($profile['phone'])): ?>
            <button type="button" data-copy="<?= e($profile['phone']) ?>" class="btn-ghost cursor-pointer" title="Sao chép số điện thoại">
              <svg class="w-4 h-4 text-[#E3A93B]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
              </svg>
              <span><?= e($profile['phone_display'] ?: $profile['phone']) ?></span>
            </button>
            <?php endif; ?>
          </div>

          <!-- Quick Contact Detail Links -->
          <div class="flex flex-wrap items-center gap-4 sm:gap-6 pt-2 text-[#B3A488] text-xs font-mono">
            <?php if (!empty($profile['email'])): ?>
            <a href="mailto:<?= e($profile['email']) ?>" class="flex items-center gap-1.5 hover:text-[#E3A93B] transition-colors cursor-pointer">
              <svg class="w-3.5 h-3.5 text-[#E3A93B]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                <polyline points="22,6 12,13 2,6"></polyline>
              </svg>
              <span><?= e($profile['email']) ?></span>
            </a>
            <?php endif; ?>

            <?php if (!empty($profile['zalo_url'])): ?>
            <a href="<?= e($profile['zalo_url']) ?>" target="_blank" rel="noopener noreferrer" class="flex items-center gap-1.5 hover:text-[#E3A93B] transition-colors cursor-pointer">
              <svg class="w-3.5 h-3.5 text-[#E3A93B]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
              </svg>
              <span>Zalo: <?= e($profile['phone_display'] ?: $profile['phone']) ?></span>
            </a>
            <?php endif; ?>
          </div>

        </div>

        <!-- Right Column: Avatar Frame with Warm Gold Glow (5 cols) -->
        <div class="lg:col-span-5 flex justify-center lg:justify-end">
          <div class="relative group">
            
            <!-- Warm Gold Ambient Ring -->
            <div class="absolute -inset-1 rounded-2xl bg-gradient-to-tr from-[#E3A93B] via-[#D2603A] to-[#A9762B] opacity-75 blur-lg group-hover:opacity-100 transition duration-500"></div>
            
            <!-- Frame Container -->
            <div class="relative w-64 sm:w-72 h-80 sm:h-96 rounded-2xl bg-[#191309] border-2 border-[#E3A93B]/70 overflow-hidden shadow-2xl p-2 flex flex-col justify-between">
              
              <!-- Avatar Photo -->
              <div class="w-full h-full rounded-xl overflow-hidden bg-[#0E0B08]">
                <img src="<?= upload_url($profile['avatar']) ?>" alt="Chân dung <?= e($profile['full_name']) ?> - <?= e($profile['job_title']) ?>" class="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-500" />
              </div>

              <!-- Floating Tag -->
              <div class="absolute bottom-4 left-4 right-4 bg-[#0E0B08]/90 backdrop-blur-md border border-[#33271A] rounded-xl px-3 py-2 flex items-center justify-between shadow-lg">
                <div class="flex items-center gap-2">
                  <span class="w-2 h-2 rounded-full bg-[#E3A93B] animate-pulse"></span>
                  <span class="text-xs font-heading font-bold text-[#F4ECDF]"><?= e($profile['floating_badge_title'] ?: $profile['full_name']) ?></span>
                </div>
                <span class="text-[10px] font-mono text-[#E3A93B] uppercase tracking-wider"><?= e($profile['floating_badge_subtitle']) ?></span>
              </div>

            </div>

          </div>
        </div>

      </div>
    </section>
    <?php endif; ?>

    <!-- ==========================================================================
         3. ABOUT SECTION (MỤC TIÊU NGHỀ NGHIỆP)
         ========================================================================== -->
    <?php if (!isset($sections['about']) || $sections['about']['is_visible']): ?>
    <section id="about" class="scroll-mt-24 space-y-10">
      
      <!-- Section Header -->
      <div class="space-y-2">
        <div class="inline-flex items-center gap-2 text-xs font-mono font-bold tracking-widest text-[#E3A93B] uppercase">
          <span><?= e($sections['about']['badge_code'] ?? '01 // MỤC TIÊU NGHỀ NGHIỆP') ?></span>
        </div>
        <h2 class="font-heading text-3xl sm:text-4xl font-extrabold text-[#F4ECDF]">
          <?= e($sections['about']['title'] ?? 'Về tôi & Kế hoạch hành động') ?>
        </h2>
        <?php if (!empty($sections['about']['subtitle'])): ?>
          <p class="text-base text-[#B3A488] max-w-xl"><?= e($sections['about']['subtitle']) ?></p>
        <?php endif; ?>
      </div>

      <!-- Content Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- Left: Narrative Objective (7 cols) -->
        <div class="lg:col-span-7 space-y-5 text-[#B3A488] leading-relaxed text-base sm:text-lg">
          <div class="p-6 sm:p-7 rounded-2xl bg-[#191309] border-l-4 border-l-[#E3A93B] border border-[#33271A] space-y-4">
            <p class="text-[#F4ECDF] font-medium leading-relaxed">
              <?= nl2br(e($profile['about_quote'])) ?>
            </p>
            <?php if (!empty($profile['about_subtext'])): ?>
            <p class="text-sm text-[#B3A488]">
              <?= nl2br(e($profile['about_subtext'])) ?>
            </p>
            <?php endif; ?>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
            <?php if (!empty($profile['commitment_1_title'])): ?>
            <div class="flex items-start gap-3 p-3.5 rounded-xl bg-[#191309] border border-[#33271A]">
              <div class="p-2 rounded-lg bg-[#241B0F] text-[#E3A93B] shrink-0">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                </svg>
              </div>
              <div class="text-xs space-y-0.5">
                <strong class="text-[#F4ECDF] block"><?= e($profile['commitment_1_title']) ?></strong>
                <span class="text-[#B3A488]"><?= e($profile['commitment_1_desc']) ?></span>
              </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($profile['commitment_2_title'])): ?>
            <div class="flex items-start gap-3 p-3.5 rounded-xl bg-[#191309] border border-[#33271A]">
              <div class="p-2 rounded-lg bg-[#241B0F] text-[#D2603A] shrink-0">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <circle cx="12" cy="12" r="10"></circle>
                  <polyline points="12 6 12 12 14 14"></polyline>
                </svg>
              </div>
              <div class="text-xs space-y-0.5">
                <strong class="text-[#F4ECDF] block"><?= e($profile['commitment_2_title']) ?></strong>
                <span class="text-[#B3A488]"><?= e($profile['commitment_2_desc']) ?></span>
              </div>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Right: Key Stats (5 cols) -->
        <div class="lg:col-span-5 grid grid-cols-2 gap-4">
          <?php foreach ($keyStats as $stat): 
            $color = get_color_class($stat['accent_color'] ?? 'gold');
          ?>
            <div class="gold-card p-5 space-y-2">
              <span class="font-heading text-3xl sm:text-4xl font-bold <?= $color['text'] ?>"><?= e($stat['value']) ?></span>
              <p class="text-xs font-semibold uppercase tracking-wider text-[#B3A488]"><?= e($stat['label']) ?></p>
              <?php if (!empty($stat['subtext'])): ?>
                <p class="text-xs text-[#7D705C]"><?= e($stat['subtext']) ?></p>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>

      </div>
    </section>
    <?php endif; ?>

    <!-- ==========================================================================
         4. SKILLS SECTION (NĂNG LỰC CHUYÊN MÔN)
         ========================================================================== -->
    <?php if (!isset($sections['skills']) || $sections['skills']['is_visible']): ?>
    <section id="skills" class="scroll-mt-24 space-y-10">
      
      <!-- Section Header -->
      <div class="space-y-2">
        <div class="inline-flex items-center gap-2 text-xs font-mono font-bold tracking-widest text-[#E3A93B] uppercase">
          <span><?= e($sections['skills']['badge_code'] ?? '02 // NĂNG LỰC CHUYÊN MÔN') ?></span>
        </div>
        <h2 class="font-heading text-3xl sm:text-4xl font-extrabold text-[#F4ECDF]">
          <?= e($sections['skills']['title'] ?? 'Kỹ Năng Cốt Lõi') ?>
        </h2>
        <?php if (!empty($sections['skills']['subtitle'])): ?>
          <p class="text-base text-[#B3A488] max-w-xl">
            <?= e($sections['skills']['subtitle']) ?>
          </p>
        <?php endif; ?>
      </div>

      <!-- Skills Cards Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <?php foreach ($skills as $skill): 
          $color = get_color_class($skill['accent_color'] ?? 'gold');
        ?>
          <div class="gold-card p-6 flex flex-col justify-between space-y-4">
            <div class="space-y-3">
              <div class="w-10 h-10 rounded-xl bg-[#241B0F] border border-[#33271A] flex items-center justify-center <?= $color['text'] ?>">
                <?= render_icon($skill['icon'] ?? 'message-square', 'w-5 h-5') ?>
              </div>
              <h3 class="font-heading text-lg font-bold text-[#F4ECDF]"><?= e($skill['title']) ?></h3>
              <p class="text-xs text-[#B3A488] leading-relaxed">
                <?= nl2br(e($skill['description'])) ?>
              </p>
            </div>
            
            <?php if (!empty($skill['tag_array'])): ?>
            <div class="flex flex-wrap gap-1.5 pt-2 border-t border-[#33271A]">
              <?php foreach ($skill['tag_array'] as $tag): ?>
                <span class="px-2 py-1 bg-[#241B0F] text-[#F4ECDF] rounded text-xs font-mono"><?= e($tag) ?></span>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

    <!-- ==========================================================================
         5. STRENGTHS SECTION (ĐIỂM MẠNH & KỶ LUẬT)
         ========================================================================== -->
    <?php if (!isset($sections['strengths']) || $sections['strengths']['is_visible']): ?>
    <section id="strengths" class="scroll-mt-24 space-y-10">
      
      <!-- Section Header -->
      <div class="space-y-2">
        <div class="inline-flex items-center gap-2 text-xs font-mono font-bold tracking-widest text-[#E3A93B] uppercase">
          <span><?= e($sections['strengths']['badge_code'] ?? '03 // PHẨM CHẤT NỔI BẬT') ?></span>
        </div>
        <h2 class="font-heading text-3xl sm:text-4xl font-extrabold text-[#F4ECDF]">
          <?= e($sections['strengths']['title'] ?? 'Điểm Mạnh & Kỷ Luật Công Việc') ?>
        </h2>
        <?php if (!empty($sections['strengths']['subtitle'])): ?>
          <p class="text-base text-[#B3A488] max-w-xl"><?= e($sections['strengths']['subtitle']) ?></p>
        <?php endif; ?>
      </div>

      <!-- Strengths Grid -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <?php foreach ($strengths as $strength): 
          $color = get_color_class($strength['accent_color'] ?? 'gold');
        ?>
          <div class="gold-card p-6 space-y-3">
            <div class="w-9 h-9 rounded-lg bg-[#241B0F] <?= $color['text'] ?> flex items-center justify-center">
              <?= render_icon($strength['icon'] ?? 'shield', 'w-5 h-5') ?>
            </div>
            <h3 class="font-heading text-lg font-bold text-[#F4ECDF]"><?= e($strength['title']) ?></h3>
            <p class="text-sm text-[#B3A488] leading-relaxed">
              <?= nl2br(e($strength['description'])) ?>
            </p>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

    <!-- ==========================================================================
         6. EXPERIENCE TIMELINE SECTION (KINH NGHIỆM LÀM VIỆC)
         ========================================================================== -->
    <?php if (!isset($sections['experience']) || $sections['experience']['is_visible']): ?>
    <section id="experience" class="scroll-mt-24 space-y-10">
      
      <!-- Section Header -->
      <div class="space-y-2">
        <div class="inline-flex items-center gap-2 text-xs font-mono font-bold tracking-widest text-[#E3A93B] uppercase">
          <span><?= e($sections['experience']['badge_code'] ?? '04 // HÀNH TRÌNH THỰC CHIẾN') ?></span>
        </div>
        <h2 class="font-heading text-3xl sm:text-4xl font-extrabold text-[#F4ECDF]">
          <?= e($sections['experience']['title'] ?? 'Kinh Nghiệm Làm Việc') ?>
        </h2>
        <?php if (!empty($sections['experience']['subtitle'])): ?>
          <p class="text-base text-[#B3A488] max-w-xl"><?= e($sections['experience']['subtitle']) ?></p>
        <?php endif; ?>
      </div>

      <!-- Timeline Stack -->
      <div class="relative border-l border-[#33271A] ml-3 sm:ml-4 pl-6 sm:pl-8 space-y-10">
        <?php foreach ($experiences as $exp): 
          $color = get_color_class($exp['accent_color'] ?? 'gold');
        ?>
          <div class="relative group">
            <!-- Timeline Node Marker -->
            <span class="absolute -left-[31px] sm:-left-[39px] top-1.5 w-3.5 h-3.5 rounded-full bg-[#191309] border-2 <?= $color['dot'] ?> transition-all"></span>

            <div class="gold-card p-6 sm:p-7 space-y-4">
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-[#33271A] pb-3">
                <div>
                  <h3 class="font-heading text-xl font-bold text-[#F4ECDF]"><?= e($exp['position']) ?></h3>
                  <p class="text-sm font-medium <?= $color['text'] ?>"><?= e($exp['company']) ?></p>
                </div>
                <span class="text-xs font-mono text-[#B3A488] bg-[#241B0F] px-3 py-1 rounded-full border border-[#33271A] w-fit">
                  <?= e($exp['period_text']) ?>
                </span>
              </div>

              <?php if (!empty($exp['bullet_lines'])): ?>
              <ul class="space-y-2 text-sm text-[#B3A488] leading-relaxed list-disc list-inside">
                <?php foreach ($exp['bullet_lines'] as $line): ?>
                  <li><?= e($line) ?></li>
                <?php endforeach; ?>
              </ul>
              <?php else: ?>
                <p class="text-sm text-[#B3A488] leading-relaxed"><?= nl2br(e($exp['description'])) ?></p>
              <?php endif; ?>

              <?php if (!empty($exp['tag_array'])): ?>
              <div class="flex flex-wrap gap-2 pt-2">
                <?php foreach ($exp['tag_array'] as $idx => $tag): ?>
                  <?php if ($idx > 0): ?>
                    <span class="text-xs font-mono text-[#7D705C]">•</span>
                  <?php endif; ?>
                  <span class="text-xs font-mono text-[#7D705C]"><?= e($tag) ?></span>
                <?php endforeach; ?>
              </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

    <!-- ==========================================================================
         7. EDUCATION & TOOLS SECTION (HỌC VẤN & CÔNG CỤ SỐ)
         ========================================================================== -->
    <?php if (!isset($sections['education']) || $sections['education']['is_visible']): ?>
    <section id="education" class="scroll-mt-24 space-y-10">
      
      <!-- Section Header -->
      <div class="space-y-2">
        <div class="inline-flex items-center gap-2 text-xs font-mono font-bold tracking-widest text-[#E3A93B] uppercase">
          <span><?= e($sections['education']['badge_code'] ?? '05 // NỀN TẢNG & CÔNG CỤ') ?></span>
        </div>
        <h2 class="font-heading text-3xl sm:text-4xl font-extrabold text-[#F4ECDF]">
          <?= e($sections['education']['title'] ?? 'Học Vấn & Công Cụ Làm Việc') ?>
        </h2>
        <?php if (!empty($sections['education']['subtitle'])): ?>
          <p class="text-base text-[#B3A488] max-w-xl"><?= e($sections['education']['subtitle']) ?></p>
        <?php endif; ?>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-stretch">
        
        <!-- Education Card (5 cols) -->
        <div class="lg:col-span-5 gold-card p-6 sm:p-7 flex flex-col justify-between space-y-4">
          <?php if (!empty($educations)): 
            $firstEdu = $educations[0];
          ?>
            <div class="space-y-3">
              <div class="inline-flex items-center gap-2 text-xs font-mono text-[#E3A93B] bg-[#241B0F] px-2.5 py-1 rounded border border-[#33271A]">
                <?= e($firstEdu['period_text']) ?>
              </div>
              <h3 class="font-heading text-2xl font-bold text-[#F4ECDF]"><?= e($firstEdu['school']) ?></h3>
              <?php if (!empty($firstEdu['major'])): ?>
                <p class="text-sm font-semibold text-[#E3A93B]"><?= e($firstEdu['major']) ?></p>
              <?php endif; ?>
              <?php if (!empty($firstEdu['description'])): ?>
                <p class="text-sm text-[#B3A488] leading-relaxed">
                  <?= nl2br(e($firstEdu['description'])) ?>
                </p>
              <?php endif; ?>
            </div>

            <?php if (!empty($firstEdu['footer_text'])): ?>
            <div class="pt-4 border-t border-[#33271A] text-xs font-mono text-[#7D705C]">
              <?= e($firstEdu['footer_text']) ?>
            </div>
            <?php endif; ?>
          <?php else: ?>
            <p class="text-[#B3A488]">Chưa có thông tin học vấn.</p>
          <?php endif; ?>
        </div>

        <!-- Tools Grid (7 cols) -->
        <div class="lg:col-span-7 grid grid-cols-1 sm:grid-cols-2 gap-4">
          <?php foreach ($tools as $tool): 
            $color = get_color_class($tool['accent_color'] ?? 'gold');
          ?>
            <div class="gold-card p-5 space-y-2">
              <div class="flex items-center gap-2 <?= $color['text'] ?>">
                <?= render_icon($tool['icon'] ?? 'edit', 'w-5 h-5') ?>
                <h4 class="font-heading font-bold text-base text-[#F4ECDF]"><?= e($tool['name']) ?></h4>
              </div>
              <p class="text-xs text-[#B3A488] leading-relaxed">
                <?= nl2br(e($tool['description'])) ?>
              </p>
            </div>
          <?php endforeach; ?>
        </div>

      </div>
    </section>
    <?php endif; ?>

    <!-- ==========================================================================
         8. CONTACT & COLLABORATION (LIÊN HỆ TRỰC TIẾP)
         ========================================================================== -->
    <?php if (!isset($sections['contact']) || $sections['contact']['is_visible']): ?>
    <section id="contact" class="scroll-mt-24 space-y-10">
      
      <!-- Contact Bento Card -->
      <div class="gold-card p-8 sm:p-12 text-center relative overflow-hidden bg-gradient-to-b from-[#191309] to-[#241B0F]">
        
        <div class="max-w-2xl mx-auto space-y-6">
          <div class="inline-flex items-center gap-2 text-xs font-mono font-bold tracking-widest text-[#E3A93B] uppercase">
            <span><?= e($sections['contact']['badge_code'] ?? '06 // KẾT NỐI & HỢP TÁC') ?></span>
          </div>

          <h2 class="font-heading text-3xl sm:text-5xl font-extrabold text-[#F4ECDF] tracking-tight leading-tight">
            <?= e($profile['contact_heading'] ?: 'Sẵn sàng đồng hành cùng doanh nghiệp đạt mục tiêu doanh số.') ?>
          </h2>

          <p class="text-base sm:text-lg text-[#B3A488] leading-relaxed">
            <?= nl2br(e($profile['contact_subtext'] ?: 'Quý nhà tuyển dụng có thể liên hệ trực tiếp với tôi qua các kênh bên dưới để trao đổi chi tiết hơn về cơ hội hợp tác.')) ?>
          </p>

          <!-- Contact Action Buttons Grid -->
          <div class="flex flex-wrap justify-center items-center gap-4 pt-4">
            
            <?php if (!empty($profile['phone'])): ?>
            <a href="tel:<?= e($profile['phone']) ?>" class="btn-gold text-sm sm:text-base cursor-pointer">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
              </svg>
              <span>Gọi điện: <?= e($profile['phone_display'] ?: $profile['phone']) ?></span>
            </a>
            <?php endif; ?>

            <?php if (!empty($profile['email'])): ?>
            <a href="mailto:<?= e($profile['email']) ?>" class="btn-terracotta text-sm sm:text-base cursor-pointer">
              <svg class="w-4 h-4 text-[#D2603A]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                <polyline points="22,6 12,13 2,6"></polyline>
              </svg>
              <span>Gửi Email</span>
            </a>

            <button type="button" data-copy="<?= e($profile['email']) ?>" class="btn-ghost text-sm sm:text-base cursor-pointer" title="Sao chép địa chỉ email">
              <svg class="w-4 h-4 text-[#E3A93B]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
              </svg>
              <span>Sao chép Email</span>
            </button>
            <?php endif; ?>

          </div>

          <!-- Social & Location Details Row -->
          <div class="pt-8 border-t border-[#33271A] flex flex-wrap justify-center items-center gap-6 sm:gap-8 text-xs font-mono text-[#B3A488]">
            <?php if (!empty($profile['zalo_url'])): ?>
            <a href="<?= e($profile['zalo_url']) ?>" target="_blank" rel="noopener noreferrer" class="hover:text-[#E3A93B] transition-colors cursor-pointer">💬 Zalo <?= e($profile['full_name']) ?> ↗</a>
            <?php endif; ?>

            <?php if (!empty($profile['address'])): ?>
            <div class="flex items-center gap-1.5 text-[#B3A488]">
              <span>📍</span>
              <span><?= e($profile['address']) ?></span>
            </div>
            <?php endif; ?>

            <?php if (!empty($profile['phone'])): ?>
            <a href="tel:<?= e($profile['phone']) ?>" class="hover:text-[#E3A93B] transition-colors cursor-pointer">📞 <?= e($profile['phone_display'] ?: $profile['phone']) ?> ↗</a>
            <?php endif; ?>
          </div>

          <!-- Contact Message Form (Antispam & Protected) -->
          <div class="pt-8 border-t border-[#33271A] text-left max-w-xl mx-auto">
            <h3 class="font-heading text-lg font-bold text-[#F4ECDF] mb-1 text-center">Hoặc gửi tin nhắn nhanh</h3>
            <p class="text-xs text-[#B3A488] mb-6 text-center">Tôi sẽ phản hồi qua email hoặc số điện thoại trong vòng 24 giờ.</p>

            <?php if ($contactSuccess): ?>
              <div class="p-4 rounded-xl bg-[#E3A93B]/10 border border-[#E3A93B]/30 text-[#E3A93B] text-sm text-center mb-6">
                ✨ Cảm ơn bạn! Lời nhắn đã được gửi thành công. Tôi sẽ liên hệ lại với bạn sớm nhất.
              </div>
            <?php elseif (!empty($contactError)): ?>
              <div class="p-4 rounded-xl bg-[#D2603A]/15 border border-[#D2603A]/30 text-[#D2603A] text-sm text-center mb-6">
                ⚠️ <?= e($contactError) ?>
              </div>
            <?php endif; ?>

            <form method="POST" action="index.php#contact" class="space-y-4">
              <input type="hidden" name="action" value="send_message" />
              <input type="hidden" name="_contact_token" value="<?= e($contactToken) ?>" />
              <input type="hidden" name="_render_time" value="<?= $renderTimestamp ?>" />

              <!-- Honeypot field (hidden offscreen for bot traps) -->
              <div style="position:absolute;left:-9999px;" aria-hidden="true">
                <input type="text" name="website" tabindex="-1" autocomplete="off" />
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label for="c_name" class="block text-xs font-mono text-[#B3A488] mb-1">Họ và tên <span class="text-[#D2603A]">*</span></label>
                  <input type="text" id="c_name" name="name" required maxlength="100" placeholder="Nguyễn Văn A" class="w-full bg-[#120E08] border border-[#33271A] rounded-lg px-3.5 py-2.5 text-sm text-[#F4ECDF] placeholder-[#7D705C] focus:outline-none focus:border-[#E3A93B] transition-colors" />
                </div>
                <div>
                  <label for="c_email" class="block text-xs font-mono text-[#B3A488] mb-1">Email liên hệ <span class="text-[#D2603A]">*</span></label>
                  <input type="email" id="c_email" name="email" required maxlength="191" placeholder="email@congty.com" class="w-full bg-[#120E08] border border-[#33271A] rounded-lg px-3.5 py-2.5 text-sm text-[#F4ECDF] placeholder-[#7D705C] focus:outline-none focus:border-[#E3A93B] transition-colors" />
                </div>
              </div>

              <div>
                <label for="c_phone" class="block text-xs font-mono text-[#B3A488] mb-1">Số điện thoại (Tùy chọn)</label>
                <input type="tel" id="c_phone" name="phone" maxlength="50" placeholder="0901234567" class="w-full bg-[#120E08] border border-[#33271A] rounded-lg px-3.5 py-2.5 text-sm text-[#F4ECDF] placeholder-[#7D705C] focus:outline-none focus:border-[#E3A93B] transition-colors" />
              </div>

              <div>
                <label for="c_message" class="block text-xs font-mono text-[#B3A488] mb-1">Nội dung trao đổi / Lời nhắn <span class="text-[#D2603A]">*</span></label>
                <textarea id="c_message" name="message" required maxlength="5000" rows="3" placeholder="Chào bạn, mình muốn trao đổi về cơ hội hợp tác..." class="w-full bg-[#120E08] border border-[#33271A] rounded-lg px-3.5 py-2.5 text-sm text-[#F4ECDF] placeholder-[#7D705C] focus:outline-none focus:border-[#E3A93B] transition-colors"></textarea>
              </div>

              <button type="submit" class="w-full btn-gold justify-center py-3 text-sm font-bold cursor-pointer">
                <span>Gửi lời nhắn ngay</span>
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
              </button>
            </form>
          </div>


        </div>

      </div>
    </section>
    <?php endif; ?>

  </main>

  <!-- ==========================================================================
       9. SITE FOOTER
       ========================================================================== -->
  <footer class="relative z-10 border-t border-[#33271A] mt-24 py-10 px-4 sm:px-6 lg:px-8 bg-[#0E0B08]/90">
    <div class="max-w-6xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4 text-center sm:text-left">
      <div class="space-y-1">
        <p class="font-heading font-bold text-sm text-[#F4ECDF]">
          <?= e($settings['footer_title'] ?? ($profile['full_name'] . ' — ' . $profile['job_title'])) ?>
        </p>
        <p class="text-xs text-[#7D705C]">
          <?= sanitize_html($settings['footer_text'] ?? 'Thiết kế phong cách Modern Dark Gold Developer với HTML + Tailwind CSS. All rights reserved &copy; 2026.') ?>
        </p>
      </div>

      <a href="#hero" class="inline-flex items-center gap-2 text-xs font-mono font-medium text-[#B3A488] hover:text-[#E3A93B] transition-colors cursor-pointer py-1 px-3 rounded-full bg-[#191309] border border-[#33271A]" title="Cuộn lên đầu trang">
        <span>Lên đầu trang</span>
        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <polyline points="18 15 12 9 6 15"></polyline>
        </svg>
      </a>
    </div>
  </footer>

  <!-- Toast Notification Container -->
  <div id="toastNotify" class="toast-gold" role="status" aria-live="polite">
    <svg class="w-4 h-4 text-[#E3A93B] shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
      <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
    </svg>
    <span id="toastMsg">Đã sao chép vào bộ nhớ tạm!</span>
  </div>

  <!-- Custom JavaScript 4 -->
  <script src="<?= asset('script4.js') ?>"></script>
</body>
</html>
