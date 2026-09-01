# 🌟 CV Mẫu 4 Developer — Web App PHP thuần & MySQL CMS

Dự án chuyển đổi template CV cá nhân tĩnh **Mẫu 4 Developer (Dark Gold)** thành một ứng dụng Web PHP thuần kết hợp MySQL/MariaDB với đầy đủ trang quản trị CMS giúp bạn tự chỉnh sửa 100% nội dung một cách trực quan, nhanh chóng và bảo mật.

---

## 📌 1. Yêu cầu môi trường

- **PHP**: Phiên bản `>= 8.0` (Hỗ trợ tốt PHP 8.1, 8.2, 8.3, 8.4).
- **PHP Extensions bắt buộc**: `pdo_mysql`, `fileinfo`, `gd`, `mbstring`, `session`.
- **Cơ sở dữ liệu**: MySQL `5.7+` hoặc MariaDB `10.3+`, Charset `utf8mb4`, Collation `utf8mb4_unicode_ci`.
- **Web Server**: Apache (có bật `mod_rewrite`, `mod_headers`) hoặc Nginx.
- **Không yêu cầu Composer/npm khi chạy production** — mã nguồn đã được biên dịch sẵn file CSS tĩnh và chạy được ngay trên mọi hosting (cPanel, DirectAdmin, VPS, Localhost XAMPP/Laragon).

---

## 🔐 2. Tài khoản quản trị Admin mặc định

- **Đường dẫn Admin**: `http://your-domain.com/admin/login.php` (hoặc `http://localhost/mau-4-developer-cms/admin/login.php`)
- **Tên đăng nhập**: `admin`
- **Mật khẩu mặc định**: `Admin@123`

> ⚠️ **CẢNH BÁO BẢO MẬT BẮT BUỘC:**  
> Ngay sau khi cài đặt và đăng nhập lần đầu tiên thành công, bạn hãy vào mục **Tài khoản Admin** (`admin/account.php`) để **đổi ngay mật khẩu mặc định** và cập nhật email cá nhân.

### 2.1. Hướng dẫn Khôi phục mật khẩu Admin (Quên mật khẩu)
Nếu bạn quên mật khẩu đăng nhập Admin, có thể thiết lập lại trực tiếp qua CSDL bằng 2 bước sau:

1. **Sinh chuỗi Hash Bcrypt mới bằng PHP CLI**:
   Mở Terminal/CMD và chạy lệnh sau (thay `'MatKhauMoiCuaBan'` bằng mật khẩu bạn muốn đặt):
   ```bash
   php -r "echo password_hash('MatKhauMoiCuaBan', PASSWORD_DEFAULT) . PHP_EOL;"
   ```
   *(Kết quả trả về sẽ có dạng `$2y$10$...` hoặc `$2y$12$...`)*

2. **Cập nhật vào Database qua phpMyAdmin / MySQL CLI**:
   Dán câu lệnh SQL sau vào tab **SQL** trong phpMyAdmin:
   ```sql
   UPDATE users SET password_hash = '<chuỗi-hash-vừa-sinh-ở-bước-1>' WHERE username = 'admin';
   ```

> 🔒 **Cảnh báo an toàn:** Sau khi đổi xong, hãy xóa lịch sử dòng lệnh Terminal (hoặc xóa buffer) và không lưu trữ mật khẩu dạng plain text ở bất kỳ file nào.


---

## 🚀 3. Hướng dẫn cài đặt nhanh trên Localhost (XAMPP / Laragon)

### Bước 1: Tạo cơ sở dữ liệu
1. Mở `phpMyAdmin` (ví dụ `http://localhost/phpmyadmin`).
2. Tạo mới database có tên: `cv_mau4_developer` (Collation: `utf8mb4_unicode_ci`).
3. Chọn database vừa tạo -> vào tab **Import** (Nhập):
   - Chọn file `database/schema.sql` -> Nhấn **Import** để tạo bảng.
   - Chọn file `database/seed.sql` -> Nhấn **Import** để nạp dữ liệu mẫu ban đầu.

### Bước 2: Cấu hình kết nối
1. Mở file `config/config.php` (hoặc sao chép từ `config/config.example.php`).
2. Điều chỉnh thông tin kết nối DB và thiết lập khóa bảo mật:
   ```php
   define('DB_HOST', '127.0.0.1');
   define('DB_PORT', '3306');
   define('DB_NAME', 'cv_mau4_developer');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   
   // Bắt buộc đổi sang chuỗi ngẫu nhiên tối thiểu 32 ký tự
   define('APP_KEY', 'sinh-bang-lenh-php-r-echo-bin2hex-random_bytes-32');
   
   // Bật nếu chạy sau Cloudflare / Nginx Reverse Proxy (mặc định: false)
   define('TRUST_PROXY', false);
   ```

> 💡 **Ghi chú về `TRUST_PROXY`:**
> - Nếu bạn chạy web trực tiếp trên Apache/Nginx (không qua proxy trung gian): Giữ nguyên `TRUST_PROXY = false` để lấy IP chính xác từ `REMOTE_ADDR` chống giả mạo header.
> - Nếu website chạy sau **Cloudflare CDN** hoặc **Reverse Proxy / Load Balancer**: Đổi `TRUST_PROXY = true` để hệ thống nhận diện đúng IP thật của người dùng qua `CF-Connecting-IP` và `X-Forwarded-For`.

   ```

### Bước 3: Khởi chạy
- Truy cập trang CV Public: `http://localhost/mau-4-developer-cms/`
- Truy cập trang Quản trị: `http://localhost/mau-4-developer-cms/admin/`

---

## 🌐 4. Hướng dẫn Triển khai (Deploy)

### 4.1. Triển khai trên Shared Hosting cPanel

1. **Upload mã nguồn**:
   - Nén toàn bộ thư mục `mau-4-developer-cms` thành file `.zip`.
   - Đăng nhập cPanel -> Mở **File Manager** -> Truy cập thư mục `public_html` (hoặc subdomain/subfolder).
   - Tải file `.zip` lên và giải nén (Extract).
2. **Tạo Database MySQL**:
   - Vào mục **MySQL Databases** trên cPanel -> Tạo Database mới (ví dụ: `u123_cv`).
   - Tạo MySQL User mới (ví dụ: `u123_cvuser`) và đặt mật khẩu an toàn.
   - Gán User vào Database và chọn quyền **ALL PRIVILEGES**.
3. **Import Dữ liệu**:
   - Mở **phpMyAdmin** trên cPanel -> Chọn database vừa tạo.
   - Import lần lượt `database/schema.sql` rồi đến `database/seed.sql`.
4. **Cập nhật `config/config.php`**:
   - Chỉnh sửa các hằng số `DB_NAME`, `DB_USER`, `DB_PASS`, `APP_KEY` theo thông tin thực tế.
5. **Cấp quyền ghi thư mục Uploads**:
   - Đảm bảo thư mục `uploads/` có quyền ghi `0755` hoặc `0777` để có thể upload ảnh đại diện.

---

### 4.2. Triển khai trên VPS (Ubuntu / Debian / CentOS)

#### 1. Phân quyền thư mục
```bash
sudo chown -R www-data:www-data /var/www/mau-4-developer-cms
sudo chmod -R 755 /var/www/mau-4-developer-cms
sudo chmod -R 775 /var/www/mau-4-developer-cms/uploads
```

#### 2. Cấu hình Nginx Vhost Mẫu
> ⚠️ **LƯU Ý QUAN TRỌNG:** File `.htaccess` **KHÔNG CÓ TÁC DỤNG** trên Nginx. Do đó bạn bắt buộc phải cấu hình block `location ^~ /uploads/` trực tiếp trong file cấu hình Nginx bên dưới.
> Đồng thời, trong Apache/LiteSpeed, `php_flag engine off` chỉ hiệu lực với `mod_php` cũ và không có tác dụng trên PHP-FPM/LiteSpeed. Lớp chặn chính xác và an toàn nhất là `<FilesMatch>` chặn toàn bộ extension thực thi.

Tạo file `/etc/nginx/sites-available/cv-developer.conf`:
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/mau-4-developer-cms;
    index index.php index.html;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # BẢO VỆ THƯ MỤC UPLOADS TRÊN NGINX
    location ^~ /uploads/ {
        location ~ \.(php|phtml|phar|cgi|pl|py|sh)$ {
            deny all;
        }
        add_header X-Content-Type-Options nosniff;
        autoindex off;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

Kích hoạt site và reload Nginx:
```bash
sudo ln -s /etc/nginx/sites-available/cv-developer.conf /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```


---

## 🎨 5. Hướng dẫn Biên dịch lại Tailwind CSS khi đổi Class

Trang Public sử dụng file CSS tĩnh `assets/tailwind.min.css` (~22KB minified) thay vì nạp thư viện CDN runtime nhằm tăng tốc độ tải trang tối đa và bảo mật.

Nếu sau này bạn tùy biến thêm class Tailwind trong `index.php`, hãy chạy lệnh sau để build lại file CSS:
```bash
npx -y tailwindcss@3.4.17 -i tailwind-input.css -o assets/tailwind.min.css --minify
```

---

## 📁 6. Cấu trúc thư mục dự án

```
mau-4-developer-cms/
├── config/
│   ├── config.example.php     # File cấu hình mẫu
│   └── config.php             # Cấu hình DB, BASE_URL, APP_KEY, Session
├── app/
│   ├── Database.php           # PDO Singleton chuẩn UTF-8mb4, Prepared Statements
│   ├── Auth.php               # Đăng nhập, rate-limit theo IP trong DB, dummy verify chống timing attack
│   ├── Csrf.php               # Quản lý CSRF Session Admin, chặn Open Redirect & Stateless Token cho Form public
│   ├── Upload.php             # Xử lý upload finfo, resize GD max 1600px, dọn dẹp file cũ
│   ├── Repository.php         # Model truy vấn CRUD dùng chung, checkContactThrottle
│   └── helpers.php            # e() escape XSS, url(), asset(), upload_url(), flash(), old()
├── admin/
│   ├── login.php, logout.php  # Đăng nhập & Đăng xuất
│   ├── index.php              # Dashboard tổng quan
│   ├── profile.php            # Sửa thông tin cá nhân, Hero, Mục tiêu, Liên hệ, Avatar, PDF
│   ├── sections.php           # Bật/tắt section, sửa tiêu đề, kéo thả thứ tự
│   ├── stats.php              # Quản lý 4 chỉ số thống kê (Key Stats)
│   ├── skills.php             # Quản lý kỹ năng cốt lõi & tags
│   ├── strengths.php          # Quản lý điểm mạnh & phẩm chất
│   ├── experience.php         # Quản lý các mốc kinh nghiệm timeline
│   ├── education.php          # Quản lý học vấn & danh sách công cụ làm việc
│   ├── settings.php           # Cấu hình SEO meta, monogram logo, footer
│   ├── messages.php           # Quản lý tin nhắn liên hệ gửi về
│   ├── account.php            # Đổi thông tin admin & mật khẩu
│   ├── ajax/
│   │   ├── reorder.php        # Nhận mảng ID SortableJS -> cập nhật Transaction PDO
│   │   ├── toggle.php         # Bật/tắt nhanh trạng thái hiển thị qua AJAX
│   │   └── upload.php         # Tải ảnh lên riêng lẻ
│   └── partials/
│       ├── header.php         # Bootstrap 5, Dark/Gold Admin CSS, Navbar
│       ├── sidebar.php        # Menu điều hướng module
│       └── footer.php         # Script SortableJS, SweetAlert2, AJAX helper
├── assets/                    # File tĩnh gốc: style4.css, script4.js, tailwind.min.css, ảnh...
├── uploads/                   # Thư mục chứa file tải lên (YYYY/MM/)
│   ├── .htaccess              # Chặn thực thi PHP script
│   └── index.html             # Chặn liệt kê danh mục thư mục (Directory Listing)
├── database/
│   ├── schema.sql             # Cấu trúc bảng MySQL chuẩn (13 bảng)
│   └── seed.sql               # Dữ liệu khởi tạo chính xác từ template
├── tailwind.config.js         # Cấu hình chủ đề Tailwind CSS
├── tailwind-input.css         # File đầu vào Tailwind CSS
├── index.php                  # Trang CV Public (render động, stateless token, antispam form)
└── README.md                  # Hướng dẫn sử dụng & triển khai
```

---

## 🛡️ 7. Tính năng bảo mật tích hợp

1. **Chống SQL Injection**: 100% các câu truy vấn sử dụng PDO Prepared Statements với tham số ràng buộc `?`.
2. **Chống tấn công CSRF & Open Redirect**:
   - Khu vực Admin sử dụng Token Session 32-byte ngẫu nhiên kèm bộ lọc `parse_url` kiểm tra trùng khớp host trước khi chuyển hướng.
   - Form liên hệ Public sử dụng cơ chế **Stateless HMAC Token** (dựa trên timestamp + `APP_KEY`), hoàn toàn không sinh cookie session cho khách vãng lai.
3. **Chống Spam Form Liên hệ (Multi-layer Antispam)**:
   - **IP Throttle**: Giới hạn tối đa 3 tin/giờ và 10 tin/24 giờ theo địa chỉ IP lưu trong bảng `contact_throttle`.
   - **Honeypot Trap**: Đặt trường ẩn `website` nằm ngoài màn hình; nếu bot tự điền sẽ giả vờ thành công nhưng không ghi vào CSDL.
   - **Time-trap**: Từ chối các lượt submit biểu mẫu được gửi dưới 3 giây kể từ khi tải trang hoặc quá 2 giờ.
   - **Validation độ dài nghiêm ngặt**: Name <= 100, Email <= 191, Phone <= 50, Message <= 5000 ký tự.
4. **Chống tấn công Brute-force Login**:
   - Rate Limiting dựa trên địa chỉ IP thực tế lưu trong bảng `login_attempts`. Nếu nhập sai quá 5 lần trong 15 phút sẽ khóa IP 5 phút.
   - Tích hợp `password_verify` với Dummy Hash cố định khi không tìm thấy tài khoản để ngăn ngừa tấn công phân tích độ trễ phản hồi (Timing Attack).
5. **Bảo vệ tải file an toàn (Upload Security)**:
   - Kiểm tra MIME type thực tế bằng `finfo_file` (không phụ thuộc vào đuôi mở rộng).
   - Đổi tên file ngẫu nhiên `{slug}-{uniqid}.{ext}` để tránh trùng lặp và tấn công ghi đè.
   - Thư mục `uploads/` có file `.htaccess` và `index.html` chặn thực thi mọi script PHP, phtml, phar, cgi... và cấm directory listing.
   - Tự động xóa file cũ khi thay đổi ảnh hoặc xóa bản ghi (bảo vệ chống Path Traversal).
6. **Chống tấn công XSS**: Toàn bộ dữ liệu hiển thị ra ngoài HTML đều được lọc qua hàm `e()` (`htmlspecialchars`). Các đoạn văn bản cho phép định dạng được lọc qua whitelist tag thủ công.
7. **Không rò rỉ thông tin lỗi Database**: Trên môi trường Production (`APP_ENV !== 'development'`), mọi ngoại lệ Database đều được ẩn thông tin nhạy cảm và ghi vào `error_log()`.
