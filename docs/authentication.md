# 🔐 Authentication & Login Security

Tài liệu này mô tả cách làm trang đăng nhập, các lớp bảo vệ, và các bước áp dụng cho project này.

## Mục tiêu

- Làm trang đăng nhập theo style glassmorphism giống mockup.
- Tắt đăng ký công khai.
- Giảm rủi ro brute force, dò tài khoản, và login bot.
- Giữ thông báo lỗi chung chung, không lộ thông tin nhạy cảm.

## Luồng đăng nhập hiện tại

1. User mở trang login.
2. Nhập Email hoặc Tên đăng nhập.
3. Hệ thống kiểm tra rate limit.
4. Xác thực bằng password hash của Laravel.
5. Nếu đúng, regenerate session.
6. Nếu sai, trả lỗi chung: Thông tin đăng nhập không đúng.

## Giao diện login

- Nền mờ, sáng, dạng glass card.
- Ô nhập bo tròn lớn.
- Nút đăng nhập gradient xanh cyan.
- Có:
  - Email / Tên đăng nhập
  - Mật khẩu
  - Quên mật khẩu
  - Remember me
  - Buttons social mang tính UI nếu sau này tích hợp OAuth

## Các lớp bảo vệ cần có

### 1. HTTPS / SSL

- Bắt buộc chạy qua HTTPS.
- Không cho login hoạt động qua HTTP ở môi trường production.
- Cấu hình nên bật:
  - `SESSION_SECURE_COOKIE=true`
  - `SESSION_HTTP_ONLY=true`
  - `SESSION_SAME_SITE=lax`

### 2. Rate limit đăng nhập

- Dùng rate limiter của Laravel.
- Hiện tại login đã giới hạn theo key: login + IP.
- Gợi ý: 5 lần sai thì khóa tạm.
- Có thể tăng/giảm thời gian khóa theo policy.

### 3. CAPTCHA sau khi sai nhiều lần

- Không bật ngay từ đầu.
- Chỉ hiện khi:
  - sai mật khẩu nhiều lần
  - IP đăng nhập quá nhanh
  - hành vi giống bot
- Gợi ý tích hợp:
  - Cloudflare Turnstile
  - Google reCAPTCHA
  - hCaptcha

### 4. Không báo lỗi quá chi tiết

- Không phân biệt rõ:
  - Email không tồn tại
  - Mật khẩu sai
- Chỉ báo chung:
  - Thông tin đăng nhập không đúng.

### 5. Mật khẩu hash chuẩn

- Dùng cơ chế hash mặc định của Laravel: bcrypt hoặc argon.
- Không lưu mật khẩu dạng text.

### 6. CSRF protection

- Form login phải có CSRF protection của Laravel.
- Với Livewire, token được xử lý qua layout và middleware.

### 7. Session an toàn

- Regenerate session sau khi login.
- Không tái sử dụng session cũ.
- Bật cookie an toàn khi đã chạy HTTPS.

### 8. Email verify / 2FA

- Nếu hệ thống có dữ liệu quan trọng, nên bật:
  - email verification
  - 2FA bằng authenticator hoặc OTP email

### 9. Log đăng nhập đáng ngờ

Nên lưu:

- IP
- user agent
- thời gian
- số lần sai
- tài khoản bị thử

### 10. Chặn brute force ở server

- Bật firewall.
- Đổi port SSH.
- Cài fail2ban.
- Dùng Cloudflare nếu có thể.

## Bước triển khai cho project này

### Bước 1: Tắt Register

- Xóa route register trong [routes/auth.php](routes/auth.php).
- Xóa link Register ở navigation.
- Không cho đăng ký công khai.

### Bước 2: Dùng trang login mới

- Cập nhật [resources/views/livewire/pages/auth/login.blade.php](resources/views/livewire/pages/auth/login.blade.php).
- Dùng UI glassmorphism.
- Giữ các field tối thiểu cần thiết.

### Bước 3: Giữ rate limit trong `LoginForm`

- `App\Livewire\Forms\LoginForm` đang chặn brute force bằng rate limiter.
- Key nên gắn với login + IP.

### Bước 4: Thêm CAPTCHA khi cần

- Khi triển khai production, thêm Turnstile hoặc reCAPTCHA vào form.
- Chỉ hiện khi user có dấu hiệu bất thường.

### Bước 5: Bảo vệ session và cookie

- Bật secure cookie khi đã có SSL.
- Kiểm tra `APP_URL` dùng `https://`.

### Bước 6: Theo dõi đăng nhập đáng ngờ

- Ghi log vào database hoặc audit log.
- Có thể tách riêng bảng `login_attempts` sau.

## Quy ước cho project

- Không bật Register công khai nếu không có yêu cầu rõ ràng.
- Không hiển thị lỗi chi tiết ở login.
- Không dùng password text.
- Luôn ưu tiên bảo mật trước UI.

## Liên kết liên quan

- [docs/CLAUDE.md](docs/CLAUDE.md)
- [docs/memory.md](docs/memory.md)
- [docs/architecture.md](docs/architecture.md)
