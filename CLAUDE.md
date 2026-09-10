## Quy tắc test web
Khi tôi yêu cầu test 1 tính năng hoặc 1 trang web:
- Nếu trong thư mục tests/e2e/ đã có file test khớp với tính năng đó → chạy trực tiếp bằng lệnh `npx playwright test <tên file>`, không dò lại từ đầu.
- Nếu chưa có file test khớp (tính năng mới, hoặc trang/app lạ chưa từng kiểm tra) → dùng Playwright MCP để tự mở và dò trực tiếp qua accessibility tree (không dùng screenshot trừ khi thật sự cần).
- Sau khi dò xong và tôi xác nhận kết quả đúng → tự viết lại thành 1 file test mới trong tests/e2e/ để lần sau dùng lại bằng CLI, không phải dò nữa.
