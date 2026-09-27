<?php
/**
 * TRANG CHỦ CV (PUBLIC VIEW) — GIAO DIỆN MẪU 5 LIQUID GLASS
 * Render dữ liệu động từ Database MySQL (không đổi schema) theo phong cách desktop OS:
 * menu bar, cửa sổ kính kéo thả, dock, ngày/đêm, bảng màu Biển sương.
 */

// Không khởi chạy session cho khách truy cập public
define('NO_SESSION', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Repository.php';
require_once __DIR__ . '/../app/Csrf.php';
require_once __DIR__ . '/../app/helpers.php';

// Lấy toàn bộ dữ liệu từ Database (với fallback an toàn không lộ chi tiết nhạy cảm trên production)
try {
    $settings    = Repository::getSettings();
    $profile     = Repository::getProfile();
    $sections    = Repository::getSectionMap();
    $keyStats    = Repository::getKeyStats(true);
    $skills      = Repository::getSkills(true);
    $strengths   = Repository::getStrengths(true);
    $weaknesses  = Repository::getWeaknesses(true);
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

// ==========================================================================
// CHUẨN BỊ DỮ LIỆU HIỂN THỊ CHO GIAO DIỆN LIQUID GLASS
// ==========================================================================

// Section hiển thị khi chưa cấu hình hoặc is_visible = 1
function section_on(array $sections, string $key): bool {
    return !isset($sections[$key]) || (int)$sections[$key]['is_visible'] === 1;
}

// Icon SVG cho dock / màn hình chính (mobile)
function glass_icon(string $name): string {
    $paths = [
        'user'      => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle>',
        'star'      => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>',
        'briefcase' => '<rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>',
        'shield'    => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>',
        'trending'  => '<polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline>',
        'book'      => '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>',
        'phone'     => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>',
    ];
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$name] ?? '') . '</svg>';
}

// Thanh tiêu đề cửa sổ (3 nút đèn giao thông)
function win_bar(string $title): string {
    return '<div class="win-bar"><span class="lights">'
        . '<button class="l-close" type="button" aria-label="Đóng" title="Đóng (Esc)"><svg viewBox="0 0 10 10" aria-hidden="true"><path d="M2.5 2.5l5 5M7.5 2.5l-5 5"/></svg></button>'
        . '<button class="l-min" type="button" aria-label="Thu nhỏ xuống dock" title="Thu nhỏ (Ctrl/⌘ + M)"><svg viewBox="0 0 10 10" aria-hidden="true"><path d="M2 5h6"/></svg></button>'
        . '<button class="l-max" type="button" aria-label="Phóng to / thu về" title="Phóng to (nhấp đúp thanh tiêu đề)"><svg viewBox="0 0 10 10" aria-hidden="true"><path d="M2.2 6.3V2.2h4.1z M7.8 3.7v4.1H3.7z" class="fill"/></svg></button>'
        . '</span><h2 class="win-title">' . e($title) . '</h2></div>';
}

// Dòng badge + phụ đề đầu mỗi cửa sổ (lấy từ bảng sections)
function win_intro(?array $section): string {
    $html = '';
    if (!empty($section['badge_code'])) {
        $html .= '<p class="eyebrow">' . e($section['badge_code']) . '</p>';
    }
    if (!empty($section['subtitle'])) {
        $html .= '<p class="win-sub muted">' . e($section['subtitle']) . '</p>';
    }
    return $html === '' ? '' : '<div class="win-intro">' . $html . '</div>';
}

// Danh sách cửa sổ: key cửa sổ => section trong DB điều khiển ẩn/hiện & thứ tự
$winDefs = [
    'about'     => ['keys' => ['hero', 'about'], 'label' => 'Về tôi',             'icon' => 'user',      'ic' => 'ic1', 'title' => 'Về tôi'],
    'skills'    => ['keys' => ['skills'],        'label' => 'Kỹ năng',            'icon' => 'star',      'ic' => 'ic3', 'title' => $sections['skills']['title'] ?? 'Kỹ năng cốt lõi'],
    'strengths' => ['keys' => ['strengths'],     'label' => 'Điểm mạnh',          'icon' => 'shield',    'ic' => 'ic4', 'title' => $sections['strengths']['title'] ?? 'Điểm mạnh & Kỷ luật'],
    'weak'      => ['keys' => ['weaknesses'],    'label' => 'Cải thiện',          'icon' => 'trending',  'ic' => 'ic7', 'title' => $sections['weaknesses']['title'] ?? 'Điểm cần cải thiện'],
    'exp'       => ['keys' => ['experience'],    'label' => 'Kinh nghiệm',        'icon' => 'briefcase', 'ic' => 'ic2', 'title' => $sections['experience']['title'] ?? 'Kinh nghiệm làm việc'],
    'edu'       => ['keys' => ['education'],     'label' => 'Học vấn & Công cụ',  'icon' => 'book',      'ic' => 'ic5', 'title' => $sections['education']['title'] ?? 'Học vấn & Công cụ'],
    'contact'   => ['keys' => ['contact'],       'label' => 'Liên hệ',            'icon' => 'phone',     'ic' => 'ic6', 'title' => $sections['contact']['title'] ?? 'Liên hệ'],
];

$windows = [];
$fallbackOrder = 0;
foreach ($winDefs as $winKey => $def) {
    $fallbackOrder += 10;
    if ($winKey === 'weak') {
        // Điểm cần cải thiện: chỉ hiện khi được bật tường minh (giữ nguyên hành vi cũ)
        $visible = isset($sections['weaknesses']) && (int)$sections['weaknesses']['is_visible'] === 1 && !empty($weaknesses);
    } else {
        $visible = false;
        foreach ($def['keys'] as $k) {
            $visible = $visible || section_on($sections, $k);
        }
    }
    if (!$visible) {
        continue;
    }
    $order = null;
    foreach ($def['keys'] as $k) {
        if (isset($sections[$k])) {
            $order = $order === null ? (int)$sections[$k]['sort_order'] : min($order, (int)$sections[$k]['sort_order']);
        }
    }
    $def['order'] = $order ?? $fallbackOrder;
    $windows[$winKey] = $def;
}
uasort($windows, fn($a, $b) => $a['order'] <=> $b['order']);

$showHero  = section_on($sections, 'hero');
$showAbout = section_on($sections, 'about');

$phoneDisplay = $profile['phone_display'] ?: ($profile['phone'] ?? '');
$statusText   = trim(explode('•', (string)($profile['status_badge'] ?? ''))[0]);
if ($statusText !== '' && $statusText === mb_strtoupper($statusText, 'UTF-8')) {
    // "SẴN SÀNG NHẬN VIỆC" -> "Sẵn sàng nhận việc" cho gọn trên menu bar
    $statusText = mb_strtoupper(mb_substr($statusText, 0, 1, 'UTF-8'), 'UTF-8') . mb_strtolower(mb_substr($statusText, 1, null, 'UTF-8'), 'UTF-8');
}
$hasCvFile    = !empty($profile['cv_pdf_file']);

// Ảnh đại diện (ưu tiên bản .webp nếu tồn tại)
$avatarImgUrl   = upload_url($profile['avatar']);
$avatarWebpPath = preg_replace('/\.(png|jpe?g)$/i', '.webp', $profile['avatar'] ?? '');
$avatarWebpUrl  = preg_replace('/\.(png|jpe?g)$/i', '.webp', $avatarImgUrl);
$hasWebp        = !empty($avatarWebpPath) && $avatarWebpPath !== ($profile['avatar'] ?? '') && file_exists(PUBLIC_PATH . '/' . ltrim($avatarWebpPath, '/\\'));
$avatarAlt      = 'Chân dung ' . $profile['full_name'] . ' - ' . $profile['job_title'];

function avatar_picture(string $class, bool $hasWebp, string $webpUrl, string $imgUrl, string $alt): string {
    $html = '<picture>';
    if ($hasWebp) {
        $html .= '<source srcset="' . e($webpUrl) . '" type="image/webp">';
    }
    $html .= '<img class="' . e($class) . '" src="' . e($imgUrl) . '" alt="' . e($alt) . '" width="800" height="800" draggable="false" />';
    return $html . '</picture>';
}

// Màu chấm timeline kinh nghiệm (xoay vòng theo bảng Biển sương)
$jobDots = ['#2f7fb5', '#3aa7a0', '#7c6fd0', '#d98a4e', '#c0567a', '#4f9d5d'];

// Mở sẵn cửa sổ Liên hệ sau khi gửi form (không dùng JS/AJAX)
$initialWin = ($contactSuccess || !empty($contactError)) ? 'contact' : '';
?>
<!DOCTYPE html>
<html lang="vi" data-theme="light" data-palette="ocean">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= e($settings['site_title'] ?? ($profile['full_name'] . ' — ' . $profile['job_title'])) ?></title>
  <meta name="description" content="<?= e($settings['meta_description'] ?? $profile['tagline']) ?>" />
  <meta name="author" content="<?= e($settings['author'] ?? $profile['full_name']) ?>" />
  <meta name="theme-color" content="#eef2f5" />
  <script>
    (function () {
      var t; try { t = localStorage.getItem('hp-glass-theme'); } catch (e) {}
      if (!t) t = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
      document.documentElement.setAttribute('data-theme', t);
      var c; try { c = localStorage.getItem('hp-glass-clear'); } catch (e) {}
      if (c !== null && !isNaN(+c)) document.documentElement.style.setProperty('--clear', (+c / 100).toFixed(2));
    })();
  </script>
  <!-- Mẫu 5 Liquid Glass (font tự host: assets/fonts/fonts.css) -->
  <link rel="stylesheet" href="<?= asset('style5.css') ?>" />
</head>
<body<?= $initialWin !== '' ? ' data-open-initial="' . e($initialWin) . '"' : '' ?>>

  <div class="mesh" aria-hidden="true"><i class="b1"></i><i class="b2"></i><i class="b3"></i><i class="b4"></i></div>

  <!-- Menu bar -->
  <header class="menubar">
    <div class="menubar-left">
      <a class="brand" href="#" data-open="about" aria-label="Mở hồ sơ"><span class="brand-mark"><?= e($settings['monogram'] ?? 'HP') ?></span><?= e($profile['full_name']) ?></a>
      <?php foreach ($windows as $winKey => $w): ?>
        <a class="menu-link" href="#" data-open="<?= $winKey ?>"><?= e($w['label']) ?></a>
      <?php endforeach; ?>
    </div>
    <div class="menubar-right">
      <?php if ($statusText !== ''): ?>
        <span class="status"><span class="status-dot"></span><?= e($statusText) ?></span>
      <?php endif; ?>
      <?php if ($hasCvFile): ?>
        <a class="menu-btn" href="<?= url('cv-download.php') ?>" title="Tải CV dạng PDF">Tải CV</a>
      <?php endif; ?>
      <button class="menu-btn" id="btnPrintCV" type="button" title="In hoặc lưu hồ sơ dạng PDF">In / PDF</button>
      <span class="clock" id="clock"></span>
      <div class="clear-wrap">
        <button class="clear-btn" id="clearBtn" type="button" aria-label="Điều chỉnh độ trong suốt" aria-expanded="false" aria-controls="clearPop" title="Độ trong suốt"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8.5"></circle><path class="fill" d="M12 3.5a8.5 8.5 0 0 1 0 17z"></path></svg></button>
        <div class="clear-pop glass" id="clearPop" role="dialog" aria-label="Độ trong suốt">
          <div class="cp-head"><span>Độ trong suốt</span><output id="clearVal" for="clearRange">45%</output></div>
          <input class="cp-range" id="clearRange" type="range" min="0" max="100" step="1" value="45" aria-label="Độ trong suốt" />
          <div class="cp-presets" role="group" aria-label="Mức có sẵn">
            <button type="button" data-clear="12">Mờ đục</button>
            <button type="button" data-clear="45">Cân bằng</button>
            <button type="button" data-clear="85">Trong suốt</button>
          </div>
        </div>
      </div>
      <button class="theme-toggle" id="themeToggle" type="button" aria-label="Chuyển giao diện ngày/đêm" aria-pressed="false"></button>
    </div>
  </header>

  <main class="desk">

    <!-- Mobile home screen -->
    <section class="home" aria-label="Màn hình chính">
      <div class="home-card glass">
        <?= avatar_picture('home-avatar', $hasWebp, $avatarWebpUrl, $avatarImgUrl, $avatarAlt) ?>
        <div>
          <p class="name"><?= e($profile['full_name']) ?></p>
          <p class="role"><?= e($profile['job_title']) ?></p>
          <?php if (!empty($profile['floating_badge_subtitle'])): ?>
            <p class="mono muted home-meta"><?= e($profile['floating_badge_subtitle']) ?></p>
          <?php endif; ?>
        </div>
      </div>
      <nav class="home-grid" aria-label="Mục hồ sơ">
        <?php foreach ($windows as $winKey => $w): ?>
          <button class="home-app" type="button" data-open="<?= $winKey ?>"><span class="app-icon <?= $w['ic'] ?>"><?= glass_icon($w['icon']) ?></span><?= e($w['label']) ?></button>
        <?php endforeach; ?>
      </nav>
    </section>

    <?php if (isset($windows['about'])): ?>
    <!-- Window: Về tôi (sections: hero + about) -->
    <article class="win glass" id="win-about" aria-label="Về tôi">
      <?= win_bar($windows['about']['title']) ?>
      <div class="win-body">
        <?php if ($showHero): ?>
        <div class="profile">
          <?= avatar_picture('avatar', $hasWebp, $avatarWebpUrl, $avatarImgUrl, $avatarAlt) ?>
          <div>
            <p class="eyebrow"><?= e($profile['pre_title'] ?? "Hello, I'm") ?></p>
            <h1 class="name"><?= e($profile['full_name']) ?></h1>
            <p class="role"><?= e($profile['job_title']) ?></p>
          </div>
        </div>
        <?php if (!empty($profile['tagline'])): ?>
          <p class="intro"><?= nl2br(e($profile['tagline'])) ?></p>
        <?php endif; ?>
        <?php endif; ?>

        <?php if ($showAbout): ?>
          <?php if (!empty($sections['about']['badge_code']) || !empty($sections['about']['title'])): ?>
            <p class="eyebrow about-eyebrow"><?= e($sections['about']['badge_code'] ?: $sections['about']['title']) ?></p>
          <?php endif; ?>
          <?php if (!empty($profile['about_quote'])): ?>
            <p class="intro intro-quote"><?= nl2br(e($profile['about_quote'])) ?></p>
          <?php endif; ?>
          <?php if (!empty($profile['about_subtext'])): ?>
            <p class="intro"><?= nl2br(e($profile['about_subtext'])) ?></p>
          <?php endif; ?>

          <?php if (!empty($profile['commitment_1_title']) || !empty($profile['commitment_2_title'])): ?>
          <div class="commit">
            <?php foreach ([1, 2] as $n): if (empty($profile["commitment_{$n}_title"])) continue; ?>
              <div class="commit-item"><b><?= e($profile["commitment_{$n}_title"]) ?></b><span><?= e($profile["commitment_{$n}_desc"]) ?></span></div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

          <?php if (!empty($keyStats)): ?>
          <div class="stats">
            <?php foreach ($keyStats as $stat):
              $countAttr = '';
              if (preg_match('/^(\d{1,6})(\D{0,3})$/u', trim($stat['value']), $m)) {
                  $countAttr = ' data-count="' . (int)$m[1] . '" data-suffix="' . e($m[2]) . '"';
              }
            ?>
              <div class="stat<?= $countAttr === '' ? ' stat-text' : '' ?>"<?= !empty($stat['subtext']) ? ' title="' . e($stat['subtext']) . '"' : '' ?>><b<?= $countAttr ?>><?= e($stat['value']) ?></b><span><?= e($stat['label']) ?></span></div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        <?php endif; ?>

        <div class="actions">
          <?php if (!empty($profile['phone'])): ?>
            <a class="btn btn-solid" href="tel:<?= e($profile['phone']) ?>">Gọi ngay</a>
          <?php endif; ?>
          <?php if (isset($windows['exp'])): ?>
            <a class="btn btn-glass" href="#" data-open="exp">Xem kinh nghiệm</a>
          <?php endif; ?>
          <?php if (!empty($profile['phone'])): ?>
            <button class="btn btn-glass" type="button" data-copy="<?= e($profile['phone']) ?>" data-copy-label="số điện thoại" title="Sao chép số điện thoại"><?= e($phoneDisplay) ?></button>
          <?php endif; ?>
        </div>
        <?php if (!empty($profile['address'])): ?>
          <p class="loc loc-about"><?= e($profile['address']) ?></p>
        <?php endif; ?>
      </div>
    </article>
    <?php endif; ?>

    <?php if (isset($windows['skills'])): ?>
    <!-- Window: Kỹ năng -->
    <article class="win glass" id="win-skills" data-w="lg" aria-label="<?= e($windows['skills']['title']) ?>">
      <?= win_bar($windows['skills']['title']) ?>
      <div class="win-body">
        <?= win_intro($sections['skills'] ?? null) ?>
        <div class="cards">
          <?php foreach ($skills as $skill): ?>
            <div class="card">
              <h3><?= e($skill['title']) ?></h3>
              <p><?= nl2br(e($skill['description'])) ?></p>
              <?php if (!empty($skill['tag_array'])): ?>
                <span class="tags"><?= e(implode(' · ', $skill['tag_array'])) ?></span>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </article>
    <?php endif; ?>

    <?php if (isset($windows['exp'])): ?>
    <!-- Window: Kinh nghiệm -->
    <article class="win glass" id="win-exp" aria-label="<?= e($windows['exp']['title']) ?>">
      <?= win_bar($windows['exp']['title']) ?>
      <div class="win-body">
        <?= win_intro($sections['experience'] ?? null) ?>
        <ol class="timeline">
          <?php foreach (array_values($experiences) as $i => $exp): ?>
            <li class="job"><span class="job-dot" style="background:<?= $jobDots[$i % count($jobDots)] ?>"></span><div>
              <span class="job-time"><?= e($exp['period_text']) ?></span>
              <h3><?= e($exp['position']) ?></h3><span class="job-co"><?= e($exp['company']) ?></span>
              <?php if (!empty($exp['bullet_lines'])): ?>
                <ul>
                  <?php foreach ($exp['bullet_lines'] as $line): ?>
                    <li><?= e(ltrim($line, "-•* \t")) ?></li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>
              <?php if (!empty($exp['tag_array'])): ?>
                <span class="tags job-tags"><?= e(implode(' · ', $exp['tag_array'])) ?></span>
              <?php endif; ?>
            </div></li>
          <?php endforeach; ?>
        </ol>
      </div>
    </article>
    <?php endif; ?>

    <?php if (isset($windows['strengths'])): ?>
    <!-- Window: Điểm mạnh -->
    <article class="win glass" id="win-strengths" aria-label="<?= e($windows['strengths']['title']) ?>">
      <?= win_bar($windows['strengths']['title']) ?>
      <div class="win-body">
        <?= win_intro($sections['strengths'] ?? null) ?>
        <div class="cards one">
          <?php foreach ($strengths as $strength): ?>
            <div class="card"><h3><?= e($strength['title']) ?></h3><p><?= nl2br(e($strength['description'])) ?></p></div>
          <?php endforeach; ?>
        </div>
      </div>
    </article>
    <?php endif; ?>

    <?php if (isset($windows['weak'])): ?>
    <!-- Window: Điểm cần cải thiện -->
    <article class="win glass" id="win-weak" data-w="sm" aria-label="<?= e($windows['weak']['title']) ?>">
      <?= win_bar($windows['weak']['title']) ?>
      <div class="win-body">
        <?= win_intro($sections['weaknesses'] ?? null) ?>
        <ul class="weak-list">
          <?php foreach ($weaknesses as $weakness): ?>
            <li><?= nl2br(e($weakness['content'])) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </article>
    <?php endif; ?>

    <?php if (isset($windows['edu'])): ?>
    <!-- Window: Học vấn & Công cụ -->
    <article class="win glass" id="win-edu" data-w="lg" aria-label="<?= e($windows['edu']['title']) ?>">
      <?= win_bar($windows['edu']['title']) ?>
      <div class="win-body">
        <?= win_intro($sections['education'] ?? null) ?>
        <?php foreach ($educations as $edu): ?>
          <div class="edu">
            <span class="job-time"><?= e($edu['period_text']) ?></span>
            <h3><?= e($edu['school']) ?></h3>
            <?php $eduLine = implode(' • ', array_filter([$edu['degree'] ?? '', $edu['major'] ?? ''])); ?>
            <?php if ($eduLine !== ''): ?>
              <span class="job-co"><?= e($eduLine) ?></span>
            <?php endif; ?>
            <?php if (!empty($edu['description'])): ?>
              <p class="muted edu-desc"><?= nl2br(e($edu['description'])) ?></p>
            <?php endif; ?>
            <?php if (!empty($edu['footer_text'])): ?>
              <p class="edu-foot mono"><?= e($edu['footer_text']) ?></p>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
        <?php if (!empty($tools)): ?>
        <div class="cards">
          <?php foreach ($tools as $tool): ?>
            <div class="card"><h3><?= e($tool['name']) ?></h3><p><?= nl2br(e($tool['description'])) ?></p></div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </article>
    <?php endif; ?>

    <?php if (isset($windows['contact'])): ?>
    <!-- Window: Liên hệ + form gửi lời nhắn (antispam giữ nguyên) -->
    <article class="win glass" id="win-contact" aria-label="<?= e($windows['contact']['title']) ?>">
      <?= win_bar($windows['contact']['title']) ?>
      <div class="win-body">
        <?php if (!empty($profile['contact_heading']) || !empty($profile['contact_subtext'])): ?>
          <div class="win-intro">
            <?php if (!empty($profile['contact_heading'])): ?>
              <h3 class="contact-heading"><?= e($profile['contact_heading']) ?></h3>
            <?php endif; ?>
            <?php if (!empty($profile['contact_subtext'])): ?>
              <p class="win-sub muted"><?= nl2br(e($profile['contact_subtext'])) ?></p>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <div class="contact-list">
          <?php if (!empty($profile['phone'])): ?>
            <a class="contact-row" href="tel:<?= e($profile['phone']) ?>"><span>ĐIỆN THOẠI</span><b><?= e($phoneDisplay) ?></b></a>
          <?php endif; ?>
          <?php if (!empty($profile['email'])): ?>
            <a class="contact-row" href="mailto:<?= e($profile['email']) ?>"><span>EMAIL</span><b><?= e($profile['email']) ?></b></a>
          <?php endif; ?>
          <?php if (!empty($profile['zalo_url'])): ?>
            <a class="contact-row" href="<?= e($profile['zalo_url']) ?>" target="_blank" rel="noopener noreferrer"><span>ZALO</span><b><?= e($phoneDisplay ?: 'Nhắn Zalo') ?></b></a>
          <?php endif; ?>
          <?php if (!empty($profile['address'])): ?>
            <div class="contact-row"><span>ĐỊA CHỈ</span><b><?= e($profile['address']) ?></b></div>
          <?php endif; ?>
        </div>

        <div class="actions">
          <?php if (!empty($profile['email'])): ?>
            <button class="btn btn-glass" type="button" data-copy="<?= e($profile['email']) ?>" data-copy-label="email">Sao chép email</button>
          <?php endif; ?>
          <?php if ($hasCvFile): ?>
            <a class="btn btn-solid" href="<?= url('cv-download.php') ?>">Tải CV (PDF)</a>
          <?php endif; ?>
        </div>

        <div class="contact-form-wrap">
          <h3 class="form-title">Gửi lời nhắn nhanh</h3>
          <p class="win-sub muted">Tôi sẽ phản hồi qua email hoặc số điện thoại trong vòng 24 giờ.</p>

          <div class="notice<?= $contactSuccess ? ' ok' : (!empty($contactError) ? ' err' : '') ?>" id="contactNotice" role="status" aria-live="polite"<?= (!$contactSuccess && empty($contactError)) ? ' hidden' : '' ?>><?php
            if ($contactSuccess) {
                echo 'Cảm ơn bạn! Lời nhắn đã được gửi thành công. Tôi sẽ liên hệ lại với bạn sớm nhất.';
            } elseif (!empty($contactError)) {
                echo e($contactError);
            }
          ?></div>

          <form method="POST" action="index.php" class="contact-form" id="contactForm">
            <input type="hidden" name="action" value="send_message" />
            <input type="hidden" name="_contact_token" value="<?= e($contactToken) ?>" />
            <input type="hidden" name="_render_time" value="<?= $renderTimestamp ?>" />

            <!-- Honeypot field (hidden offscreen for bot traps) -->
            <div style="position:absolute;left:-9999px;" aria-hidden="true">
              <input type="text" name="website" tabindex="-1" autocomplete="off" />
            </div>

            <div class="field-row">
              <label class="field"><span>Họ và tên <em>*</em></span><input type="text" name="name" required maxlength="100" placeholder="Nguyễn Văn A" autocomplete="name" /></label>
              <label class="field"><span>Email <em>*</em></span><input type="email" name="email" required maxlength="191" placeholder="email@congty.com" autocomplete="email" /></label>
            </div>
            <label class="field"><span>Số điện thoại (tùy chọn)</span><input type="tel" name="phone" maxlength="50" placeholder="0901234567" autocomplete="tel" /></label>
            <label class="field"><span>Lời nhắn <em>*</em></span><textarea name="message" required maxlength="5000" rows="3" placeholder="Chào bạn, mình muốn trao đổi về cơ hội hợp tác..."></textarea></label>
            <button type="submit" class="btn btn-solid btn-block">Gửi lời nhắn</button>
          </form>
        </div>

        <?php if (!empty($settings['footer_title']) || !empty($settings['footer_text'])): ?>
          <div class="win-foot">
            <?php if (!empty($settings['footer_title'])): ?><p><b><?= e($settings['footer_title']) ?></b></p><?php endif; ?>
            <?php if (!empty($settings['footer_text'])): ?><p><?= sanitize_html($settings['footer_text']) ?></p><?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </article>
    <?php endif; ?>
  </main>

  <!-- Dock (desktop) -->
  <nav class="dock glass" aria-label="Dock">
    <?php foreach ($windows as $winKey => $w): ?>
      <button class="dock-item" type="button" data-open="<?= $winKey ?>"><span class="dock-tip glass"><?= e($w['label']) ?></span><span class="app-icon <?= $w['ic'] ?>"><?= glass_icon($w['icon']) ?></span></button>
    <?php endforeach; ?>
  </nav>

  <!-- Call bar (mobile) -->
  <?php if (!empty($profile['phone']) || !empty($profile['zalo_url'])): ?>
  <div class="callbar glass">
    <?php if (!empty($profile['phone'])): ?>
      <a class="btn btn-solid" href="tel:<?= e($profile['phone']) ?>">Gọi ngay</a>
    <?php endif; ?>
    <?php if (!empty($profile['zalo_url'])): ?>
      <a class="btn btn-glass" href="<?= e($profile['zalo_url']) ?>" target="_blank" rel="noopener noreferrer">Zalo</a>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <div class="scrim"></div>
  <div class="toast glass" id="toast" role="status" aria-live="polite"></div>

  <script src="<?= asset('script5.js') ?>"></script>
</body>
</html>
