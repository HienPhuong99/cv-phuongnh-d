<?php
/**
 * Lớp Upload xử lý tải ảnh lên an toàn, xác thực MIME type và resize với GD
 */

class Upload {
    private const MAX_SIZE = 5 * 1024 * 1024; // 5MB
    private const ALLOWED_MIMES = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif'
    ];
    private const MAX_DIMENSION = 1600; // Resize cạnh dài về max 1600px

    /**
     * Xử lý upload 1 file ảnh
     * 
     * @param array $file Mảng $_FILES['tên_field']
     * @param string $prefix Tiền tố tên file
     * @return array Kết quả ['success' => bool, 'path' => string, 'message' => string]
     */
    public static function processImage(array $file, string $prefix = 'image'): array {
        if (!isset($file['error']) || is_array($file['error'])) {
            return ['success' => false, 'message' => 'Dữ liệu file không hợp lệ.'];
        }

        if ($file['error'] === UPLOAD_ERR_NO_FILE) {
            return ['success' => false, 'message' => 'Chưa chọn file để tải lên.'];
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Lỗi khi tải file lên (Mã lỗi: ' . $file['error'] . ').'];
        }

        // 1. Kiểm tra dung lượng
        if ($file['size'] > self::MAX_SIZE) {
            return ['success' => false, 'message' => 'Dung lượng file vượt quá giới hạn cho phép (Tối đa 5MB).'];
        }

        // 2. Kiểm tra MIME Type thực tế qua finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!array_key_exists($mimeType, self::ALLOWED_MIMES)) {
            return ['success' => false, 'message' => 'Định dạng file không được hỗ trợ (Chỉ chấp nhận JPG, PNG, WEBP, GIF).'];
        }

        $extension = self::ALLOWED_MIMES[$mimeType];

        // 3. Tạo thư mục theo cấu trúc uploads/YYYY/MM/
        $yearMonth = date('Y/m');
        $targetDir = UPLOAD_PATH . '/' . $yearMonth;

        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
                return ['success' => false, 'message' => 'Không thể tạo thư mục lưu trữ ảnh.'];
            }
        }

        // 4. Đặt tên file an toàn: {slug}-{uniqid}.{ext}
        $slug = self::slugify($prefix);
        if (empty($slug)) {
            $slug = 'upload';
        }
        $filename = sprintf('%s-%s.%s', $slug, uniqid(), $extension);
        $targetFilePath = $targetDir . '/' . $filename;

        // 5. Thử resize ảnh bằng GD nếu vượt quá 1600px
        $resized = self::resizeImageGD($file['tmp_name'], $targetFilePath, $mimeType);

        if (!$resized) {
            // Nếu không dùng được GD, di chuyển file bình thường
            if (!move_uploaded_file($file['tmp_name'], $targetFilePath)) {
                return ['success' => false, 'message' => 'Không thể lưu file vào thư mục đích.'];
            }
        }

        // Đường dẫn tương đối lưu vào DB: uploads/YYYY/MM/filename.ext
        $relativePath = 'uploads/' . $yearMonth . '/' . $filename;

        return [
            'success' => true,
            'path'    => $relativePath,
            'url'     => $relativePath,
            'message' => 'Tải ảnh lên thành công.'
        ];
    }

    /**
     * Xử lý tải file tài liệu (PDF)
     */
    public static function processDocument(array $file, string $prefix = 'document'): array {
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Lỗi tải file tài liệu.'];
        }

        if ($file['size'] > 10 * 1024 * 1024) { // 10MB
            return ['success' => false, 'message' => 'File PDF vượt quá 10MB.'];
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if ($mimeType !== 'application/pdf') {
            return ['success' => false, 'message' => 'Chỉ chấp nhận file định dạng PDF.'];
        }

        $yearMonth = date('Y/m');
        $targetDir = UPLOAD_PATH . '/' . $yearMonth;
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $slug = self::slugify($prefix);
        $filename = sprintf('%s-%s.pdf', $slug, uniqid());
        $targetFilePath = $targetDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $targetFilePath)) {
            return ['success' => false, 'message' => 'Không thể lưu file PDF.'];
        }

        $relativePath = 'uploads/' . $yearMonth . '/' . $filename;
        return [
            'success' => true,
            'path'    => $relativePath,
            'url'     => $relativePath,
            'message' => 'Tải file PDF thành công.'
        ];
    }

    /**
     * Resize ảnh nếu kích thước cạnh dài vượt quá 1600px
     */
    private static function resizeImageGD(string $sourcePath, string $destPath, string $mimeType): bool {
        if (!extension_loaded('gd')) {
            return false;
        }

        $imageInfo = @getimagesize($sourcePath);
        if (!$imageInfo) {
            return false;
        }

        [$width, $height] = $imageInfo;

        // Tạo resource ảnh nguồn
        $srcImage = match ($mimeType) {
            'image/jpeg' => @imagecreatefromjpeg($sourcePath),
            'image/png'  => @imagecreatefrompng($sourcePath),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : false,
            'image/gif'  => @imagecreatefromgif($sourcePath),
            default      => false
        };

        if (!$srcImage) {
            return false;
        }

        // Tính toán kích thước mới nếu cạnh dài > MAX_DIMENSION
        $maxDim = self::MAX_DIMENSION;
        if ($width > $maxDim || $height > $maxDim) {
            if ($width >= $height) {
                $newWidth = $maxDim;
                $newHeight = (int) round(($height / $width) * $maxDim);
            } else {
                $newHeight = $maxDim;
                $newWidth = (int) round(($width / $height) * $maxDim);
            }
        } else {
            $newWidth = $width;
            $newHeight = $height;
        }

        $dstImage = imagecreatetruecolor($newWidth, $newHeight);

        // Giữ độ trong suốt cho PNG, WEBP, GIF
        if ($mimeType === 'image/png' || $mimeType === 'image/webp') {
            imagealphablending($dstImage, false);
            imagesavealpha($dstImage, true);
            $transparent = imagecolorallocatealpha($dstImage, 255, 255, 255, 127);
            imagefilledrectangle($dstImage, 0, 0, $newWidth, $newHeight, $transparent);
        } elseif ($mimeType === 'image/gif') {
            $transparentIndex = imagecolortransparent($srcImage);
            if ($transparentIndex >= 0) {
                $transparentColor = imagecolorsforindex($srcImage, $transparentIndex);
                $transparentIndex = imagecolorallocate($dstImage, $transparentColor['red'], $transparentColor['green'], $transparentColor['blue']);
                imagefill($dstImage, 0, 0, $transparentIndex);
                imagecolortransparent($dstImage, $transparentIndex);
            }
        }

        imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        // Lưu ảnh đích
        $saved = match ($mimeType) {
            'image/jpeg' => imagejpeg($dstImage, $destPath, 90),
            'image/png'  => imagepng($dstImage, $destPath, 8),
            'image/webp' => function_exists('imagewebp') ? imagewebp($dstImage, $destPath, 90) : false,
            'image/gif'  => imagegif($dstImage, $destPath),
            default      => false
        };

        imagedestroy($srcImage);
        imagedestroy($dstImage);

        return (bool)$saved;
    }

    /**
     * Xóa file cũ trong thư mục uploads/ (Bảo vệ Path Traversal)
     */
    public static function deleteOldFile(?string $relativePath): bool {
        if (empty($relativePath)) {
            return false;
        }

        // Không xóa ảnh mặc định trong assets/
        if (str_starts_with($relativePath, 'assets/')) {
            return false;
        }

        $fullPath = ROOT_PATH . '/' . ltrim($relativePath, '/\\');
        $realUploadPath = realpath(UPLOAD_PATH);
        $realFilePath = realpath($fullPath);

        // Chỉ xóa nếu file thực sự nằm trong UPLOAD_PATH
        if ($realFilePath && $realUploadPath && str_starts_with($realFilePath, $realUploadPath) && file_exists($realFilePath)) {
            return @unlink($realFilePath);
        }

        return false;
    }

    /**
     * Chuyển chuỗi thành slug an toàn cho tên file
     */
    public static function slugify(string $text): string {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);

        return empty($text) ? 'file' : $text;
    }
}
