<?php
/**
 * Lớp Repository trung gian xử lý truy vấn dữ liệu cho trang Public và Admin CMS
 */

class Repository {

    // ==========================================
    // PROFILE
    // ==========================================
    public static function getProfile(): array {
        $profile = Database::fetch("SELECT * FROM `profile` WHERE `id` = 1");
        if (!$profile) {
            return [
                'id' => 1,
                'full_name' => 'HIỀN PHƯƠNG',
                'job_title' => 'Nhân Viên Kinh Doanh',
                'status_badge' => 'SẴN SÀNG NHẬN VIỆC • TP. THỦ ĐỨC, TP.HCM',
                'pre_title' => "Hello, I'm",
                'tagline' => 'Nhân viên kinh doanh với hơn 3 năm kinh nghiệm thực chiến tư vấn bán hàng đa kênh, mở rộng tệp khách hàng tiềm năng (B2B/B2C) và quản lý đơn hàng trên sàn thương mại điện tử.',
                'avatar' => 'assets/hien-phuong.png',
                'floating_badge_title' => 'HIỀN PHƯƠNG',
                'floating_badge_subtitle' => 'B2B / B2C Sales',
                'email' => 'hphuong123123@gmail.com',
                'phone' => '0876488047',
                'phone_display' => '087 6488 047',
                'zalo_url' => 'https://zalo.me/0876488047',
                'address' => 'Trường Thọ, TP. Thủ Đức, TP.HCM',
                'about_quote' => '“Trong vòng 3 tháng đầu làm việc, tôi đặt mục tiêu trở thành nhân viên chính thức bằng cách đạt doanh số tối thiểu công ty đề ra. Đồng thời, tôi sẽ mở rộng mạng lưới khách hàng tiềm năng bằng cách tiếp cận và thiết lập mối quan hệ với ít nhất 10 khách hàng mới mỗi tháng.”',
                'about_subtext' => 'Tôi cũng chủ động tìm hiểu và áp dụng các kỹ năng bán hàng hiệu quả để nâng cao năng suất và đạt được kết quả tốt nhất trong công việc.',
                'commitment_1_title' => 'Cam kết chỉ tiêu',
                'commitment_1_desc' => 'Bám sát KPI doanh số và tiến độ công việc',
                'commitment_2_title' => 'Mở rộng tệp khách hàng',
                'commitment_2_desc' => 'Thiết lập quan hệ với 10+ đối tác mới/tháng',
                'contact_heading' => 'Sẵn sàng đồng hành cùng doanh nghiệp đạt mục tiêu doanh số.',
                'contact_subtext' => 'Quý nhà tuyển dụng có thể liên hệ trực tiếp với tôi qua các kênh bên dưới để trao đổi chi tiết hơn về cơ hội hợp tác.',
                'cv_pdf_file' => null
            ];
        }
        return $profile;
    }

    public static function updateProfile(array $data): bool {
        $existing = Database::fetch("SELECT id FROM `profile` WHERE `id` = 1");
        if ($existing) {
            Database::update('profile', $data, 'id = 1');
        } else {
            $data['id'] = 1;
            Database::insert('profile', $data);
        }
        return true;
    }

    public static function updateCvPdf(string $path, string $originalName, int $size): bool {
        Database::query(
            "UPDATE `profile` SET `cv_pdf_file` = ?, `cv_original_name` = ?, `cv_uploaded_at` = NOW(), `cv_size` = ? WHERE `id` = 1",
            [$path, $originalName, $size]
        );
        return true;
    }

    public static function removeCvPdf(): bool {
        Database::query(
            "UPDATE `profile` SET `cv_pdf_file` = NULL, `cv_original_name` = NULL, `cv_uploaded_at` = NULL, `cv_size` = NULL WHERE `id` = 1"
        );
        return true;
    }

    // ==========================================
    // SECTIONS
    // ==========================================
    public static function getSections(bool $onlyVisible = false): array {
        $sql = "SELECT * FROM `sections`";
        if ($onlyVisible) {
            $sql .= " WHERE `is_visible` = 1";
        }
        $sql .= " ORDER BY `sort_order` ASC, `id` ASC";
        return Database::fetchAll($sql);
    }

    public static function getSectionMap(): array {
        $sections = self::getSections(false);
        $map = [];
        foreach ($sections as $sec) {
            $map[$sec['key']] = $sec;
        }
        return $map;
    }

    public static function updateSection(int $id, array $data): bool {
        Database::update('sections', $data, 'id = ?', [$id]);
        return true;
    }

    // ==========================================
    // SETTINGS
    // ==========================================
    public static function getSettings(): array {
        $rows = Database::fetchAll("SELECT `key`, `value` FROM `settings`");
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['key']] = $row['value'];
        }
        return $settings;
    }

    public static function getSetting(string $key, string $default = ''): string {
        $row = Database::fetch("SELECT `value` FROM `settings` WHERE `key` = ?", [$key]);
        return $row ? (string)$row['value'] : $default;
    }

    public static function updateSettings(array $data): void {
        foreach ($data as $k => $v) {
            $existing = Database::fetch("SELECT `key` FROM `settings` WHERE `key` = ?", [$k]);
            if ($existing) {
                Database::update('settings', ['value' => $v], '`key` = ?', [$k]);
            } else {
                Database::insert('settings', ['key' => $k, 'value' => $v]);
            }
        }
    }

    // ==========================================
    // KEY STATS
    // ==========================================
    public static function getKeyStats(bool $onlyActive = false): array {
        $sql = "SELECT * FROM `key_stats`";
        if ($onlyActive) {
            $sql .= " WHERE `is_active` = 1";
        }
        $sql .= " ORDER BY `sort_order` ASC, `id` ASC";
        return Database::fetchAll($sql);
    }

    public static function getStatById(int $id): ?array {
        return Database::fetch("SELECT * FROM `key_stats` WHERE `id` = ?", [$id]);
    }

    public static function saveStat(array $data, ?int $id = null): int {
        if ($id) {
            Database::update('key_stats', $data, 'id = ?', [$id]);
            return $id;
        } else {
            if (!isset($data['sort_order'])) {
                $maxOrder = Database::fetch("SELECT MAX(sort_order) as m FROM key_stats");
                $data['sort_order'] = ($maxOrder['m'] ?? 0) + 1;
            }
            return Database::insert('key_stats', $data);
        }
    }

    public static function deleteStat(int $id): bool {
        return Database::delete('key_stats', 'id = ?', [$id]) > 0;
    }

    // ==========================================
    // SKILLS
    // ==========================================
    public static function getSkills(bool $onlyActive = false): array {
        $sql = "SELECT * FROM `skills`";
        if ($onlyActive) {
            $sql .= " WHERE `is_active` = 1";
        }
        $sql .= " ORDER BY `sort_order` ASC, `id` ASC";
        $skills = Database::fetchAll($sql);

        foreach ($skills as &$skill) {
            if (!empty($skill['tags'])) {
                $decoded = json_decode($skill['tags'], true);
                $skill['tag_array'] = is_array($decoded) ? $decoded : array_filter(array_map('trim', explode(',', $skill['tags'])));
            } else {
                $skill['tag_array'] = [];
            }
        }

        return $skills;
    }

    public static function getSkillById(int $id): ?array {
        return Database::fetch("SELECT * FROM `skills` WHERE `id` = ?", [$id]);
    }

    public static function saveSkill(array $data, ?int $id = null): int {
        if ($id) {
            Database::update('skills', $data, 'id = ?', [$id]);
            return $id;
        } else {
            if (!isset($data['sort_order'])) {
                $maxOrder = Database::fetch("SELECT MAX(sort_order) as m FROM skills");
                $data['sort_order'] = ($maxOrder['m'] ?? 0) + 1;
            }
            return Database::insert('skills', $data);
        }
    }

    public static function deleteSkill(int $id): bool {
        return Database::delete('skills', 'id = ?', [$id]) > 0;
    }

    // ==========================================
    // STRENGTHS
    // ==========================================
    public static function getStrengths(bool $onlyActive = false): array {
        $sql = "SELECT * FROM `strengths`";
        if ($onlyActive) {
            $sql .= " WHERE `is_active` = 1";
        }
        $sql .= " ORDER BY `sort_order` ASC, `id` ASC";
        return Database::fetchAll($sql);
    }

    public static function getStrengthById(int $id): ?array {
        return Database::fetch("SELECT * FROM `strengths` WHERE `id` = ?", [$id]);
    }

    public static function saveStrength(array $data, ?int $id = null): int {
        if ($id) {
            Database::update('strengths', $data, 'id = ?', [$id]);
            return $id;
        } else {
            if (!isset($data['sort_order'])) {
                $maxOrder = Database::fetch("SELECT MAX(sort_order) as m FROM strengths");
                $data['sort_order'] = ($maxOrder['m'] ?? 0) + 1;
            }
            return Database::insert('strengths', $data);
        }
    }

    public static function deleteStrength(int $id): bool {
        return Database::delete('strengths', 'id = ?', [$id]) > 0;
    }

    // ==========================================
    // WEAKNESSES (Điểm cần cải thiện — section mặc định ẩn)
    // ==========================================
    public static function getWeaknesses(bool $onlyActive = false): array {
        $sql = "SELECT * FROM `weaknesses`";
        if ($onlyActive) {
            $sql .= " WHERE `is_active` = 1";
        }
        $sql .= " ORDER BY `sort_order` ASC, `id` ASC";
        return Database::fetchAll($sql);
    }

    public static function getWeaknessById(int $id): ?array {
        return Database::fetch("SELECT * FROM `weaknesses` WHERE `id` = ?", [$id]);
    }

    public static function saveWeakness(array $data, ?int $id = null): int {
        if ($id) {
            Database::update('weaknesses', $data, 'id = ?', [$id]);
            return $id;
        } else {
            if (!isset($data['sort_order'])) {
                $maxOrder = Database::fetch("SELECT MAX(sort_order) as m FROM weaknesses");
                $data['sort_order'] = ($maxOrder['m'] ?? 0) + 1;
            }
            return Database::insert('weaknesses', $data);
        }
    }

    public static function deleteWeakness(int $id): bool {
        return Database::delete('weaknesses', 'id = ?', [$id]) > 0;
    }

    // ==========================================
    // EXPERIENCES
    // ==========================================
    public static function getExperiences(bool $onlyActive = false): array {
        $sql = "SELECT * FROM `experiences`";
        if ($onlyActive) {
            $sql .= " WHERE `is_active` = 1";
        }
        $sql .= " ORDER BY `sort_order` ASC, `id` ASC";
        $exps = Database::fetchAll($sql);

        foreach ($exps as &$exp) {
            // Danh sách bullets nhiệm vụ
            $lines = array_filter(array_map('trim', explode("\n", (string)$exp['description'])));
            $exp['bullet_lines'] = $lines;

            // Danh sách tags
            if (!empty($exp['tags'])) {
                $decoded = json_decode($exp['tags'], true);
                $exp['tag_array'] = is_array($decoded) ? $decoded : array_filter(array_map('trim', explode(',', $exp['tags'])));
            } else {
                $exp['tag_array'] = [];
            }
        }

        return $exps;
    }

    public static function getExperienceById(int $id): ?array {
        return Database::fetch("SELECT * FROM `experiences` WHERE `id` = ?", [$id]);
    }

    public static function saveExperience(array $data, ?int $id = null): int {
        if ($id) {
            Database::update('experiences', $data, 'id = ?', [$id]);
            return $id;
        } else {
            if (!isset($data['sort_order'])) {
                $maxOrder = Database::fetch("SELECT MAX(sort_order) as m FROM experiences");
                $data['sort_order'] = ($maxOrder['m'] ?? 0) + 1;
            }
            return Database::insert('experiences', $data);
        }
    }

    public static function deleteExperience(int $id): bool {
        return Database::delete('experiences', 'id = ?', [$id]) > 0;
    }

    // ==========================================
    // EDUCATIONS
    // ==========================================
    public static function getEducations(bool $onlyActive = false): array {
        $sql = "SELECT * FROM `educations`";
        if ($onlyActive) {
            $sql .= " WHERE `is_active` = 1";
        }
        $sql .= " ORDER BY `sort_order` ASC, `id` ASC";
        return Database::fetchAll($sql);
    }

    public static function getEducationById(int $id): ?array {
        return Database::fetch("SELECT * FROM `educations` WHERE `id` = ?", [$id]);
    }

    public static function saveEducation(array $data, ?int $id = null): int {
        if ($id) {
            Database::update('educations', $data, 'id = ?', [$id]);
            return $id;
        } else {
            if (!isset($data['sort_order'])) {
                $maxOrder = Database::fetch("SELECT MAX(sort_order) as m FROM educations");
                $data['sort_order'] = ($maxOrder['m'] ?? 0) + 1;
            }
            return Database::insert('educations', $data);
        }
    }

    public static function deleteEducation(int $id): bool {
        return Database::delete('educations', 'id = ?', [$id]) > 0;
    }

    // ==========================================
    // TOOLS
    // ==========================================
    public static function getTools(bool $onlyActive = false): array {
        $sql = "SELECT * FROM `tools`";
        if ($onlyActive) {
            $sql .= " WHERE `is_active` = 1";
        }
        $sql .= " ORDER BY `sort_order` ASC, `id` ASC";
        return Database::fetchAll($sql);
    }

    public static function getToolById(int $id): ?array {
        return Database::fetch("SELECT * FROM `tools` WHERE `id` = ?", [$id]);
    }

    public static function saveTool(array $data, ?int $id = null): int {
        if ($id) {
            Database::update('tools', $data, 'id = ?', [$id]);
            return $id;
        } else {
            if (!isset($data['sort_order'])) {
                $maxOrder = Database::fetch("SELECT MAX(sort_order) as m FROM tools");
                $data['sort_order'] = ($maxOrder['m'] ?? 0) + 1;
            }
            return Database::insert('tools', $data);
        }
    }

    public static function deleteTool(int $id): bool {
        return Database::delete('tools', 'id = ?', [$id]) > 0;
    }

    // ==========================================
    // CONTACT MESSAGES
    // ==========================================
    public static function getMessages(int $limit = 100): array {
        return Database::fetchAll("SELECT * FROM `contact_messages` ORDER BY `created_at` DESC LIMIT {$limit}");
    }

    public static function getMessageById(int $id): ?array {
        return Database::fetch("SELECT * FROM `contact_messages` WHERE `id` = ?", [$id]);
    }

    public static function createMessage(array $data): int {
        return Database::insert('contact_messages', $data);
    }

    public static function markMessageAsRead(int $id, int $isRead = 1): bool {
        return Database::update('contact_messages', ['is_read' => $isRead], 'id = ?', [$id]) > 0;
    }

    public static function deleteMessage(int $id): bool {
        return Database::delete('contact_messages', 'id = ?', [$id]) > 0;
    }

    public static function countUnreadMessages(): int {
        $res = Database::fetch("SELECT COUNT(*) as cnt FROM `contact_messages` WHERE `is_read` = 0");
        return (int)($res['cnt'] ?? 0);
    }

    // ==========================================
    // CONTACT THROTTLE (CHỐNG SPAM)
    // ==========================================
    public static function checkContactThrottle(string $ip): bool {
        $ip = trim($ip);
        if (empty($ip)) {
            return true;
        }

        try {
            // Giới hạn 1: không quá 3 tin trong 1 giờ
            $hRow = Database::fetch(
                "SELECT COUNT(*) as cnt FROM `contact_throttle` WHERE `ip` = ? AND `created_at` >= NOW() - INTERVAL 1 HOUR",
                [$ip]
            );
            if ((int)($hRow['cnt'] ?? 0) >= 3) {
                return false;
            }

            // Giới hạn 2: không quá 10 tin trong 24 giờ
            $dRow = Database::fetch(
                "SELECT COUNT(*) as cnt FROM `contact_throttle` WHERE `ip` = ? AND `created_at` >= NOW() - INTERVAL 24 HOUR",
                [$ip]
            );
            if ((int)($dRow['cnt'] ?? 0) >= 10) {
                return false;
            }
        } catch (Throwable $e) {
            error_log("Lỗi checkContactThrottle: " . $e->getMessage());
        }

        return true;
    }

    public static function recordContactThrottle(string $ip): void {
        $ip = trim($ip);
        if (empty($ip)) {
            return;
        }
        try {
            Database::insert('contact_throttle', ['ip' => $ip]);
        } catch (Throwable $e) {
            error_log("Lỗi recordContactThrottle: " . $e->getMessage());
        }
    }

    public static function cleanupContactThrottle(): void {
        try {
            Database::query("DELETE FROM `contact_throttle` WHERE `created_at` < NOW() - INTERVAL 48 HOUR");
        } catch (Throwable $e) {
            error_log("Lỗi cleanupContactThrottle: " . $e->getMessage());
        }
    }


    // ==========================================
    // GENERAL UTILS: REORDER & TOGGLE
    // ==========================================
    public static function reorder(string $table, array $orderedIds): bool {
        $allowedTables = ['sections', 'key_stats', 'skills', 'strengths', 'weaknesses', 'experiences', 'educations', 'tools'];
        if (!in_array($table, $allowedTables, true)) {
            return false;
        }

        $pdo = Database::getInstance();
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE `{$table}` SET `sort_order` = ? WHERE `id` = ?");
            // Cách nhau 10 (10, 20, 30...) để giữ khoảng trống chèn mục mới mà không phải
            // đánh số lại toàn bảng — đồng nhất với cách seed.sql đánh số sẵn.
            foreach ($orderedIds as $index => $id) {
                $stmt->execute([((int)$index + 1) * 10, (int)$id]);
            }
            $pdo->commit();
            return true;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return false;
        }
    }

    public static function toggle(string $table, int $id, string $column = 'is_active'): ?int {
        $allowedTables = ['sections', 'key_stats', 'skills', 'strengths', 'weaknesses', 'experiences', 'educations', 'tools'];
        $allowedColumns = ['is_active', 'is_visible', 'is_read'];

        if (!in_array($table, $allowedTables, true) || !in_array($column, $allowedColumns, true)) {
            return null;
        }

        $item = Database::fetch("SELECT `{$column}` FROM `{$table}` WHERE `id` = ?", [$id]);
        if (!$item) {
            return null;
        }

        $newVal = $item[$column] ? 0 : 1;
        Database::update($table, [$column => $newVal], 'id = ?', [$id]);

        return $newVal;
    }
}
