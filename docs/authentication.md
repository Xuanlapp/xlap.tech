# Authentication & Login Security

Tai lieu nay mo ta luong dang nhap va cac lop bao ve dang ap dung trong project XLAP.

## Muc tieu

- Cho phep dang nhap bang email hoac ten dang nhap.
- Giam rui ro brute force, bot submit form, va spam request vao endpoint login.
- Khong tiet lo tai khoan nao ton tai hay khong ton tai.
- Giu session an toan sau khi dang nhap thanh cong.

## Luong dang nhap

1. User mo trang `GET /login`.
2. Trang login tao 2 truong an:
   - `website`: honeypot, user that khong nhap.
   - `startedAt` / `started_at`: timestamp khi form duoc render.
3. Khi submit, he thong kiem tra:
   - IP co gui qua nhieu request trong 60 giay khong.
   - Tai khoan + IP co sai qua nhieu lan khong.
   - Honeypot co bi dien khong.
   - Form co bi submit qua nhanh khong.
   - Turnstile token co hop le khong, neu CAPTCHA dang bat.
4. Neu qua duoc cac lop bao ve, Laravel moi thuc hien `Auth::attempt`.
5. Neu dung mat khau, session duoc regenerate va user vao dashboard.
6. Neu sai, tra ve loi chung: `These credentials do not match our records.`

## File lien quan

- `app/Support/LoginSecurity.php`
  Chua logic bao ve dung chung cho MVC controller va Livewire form.
- `app/Livewire/Forms/LoginForm.php`
  Xu ly dang nhap qua Volt/Livewire.
- `app/Http/Controllers/Auth/LoginController.php`
  Xu ly fallback `POST /login`.
- `resources/views/livewire/pages/auth/login.blade.php`
  Giao dien login chinh.
- `resources/views/auth/login.blade.php`
  Giao dien login fallback neu dung form POST thuong.
- `routes/web.php`
  Khai bao `GET /login` va `POST /login`.

## Cac lop bao ve hien co

### 1. Rate limit theo IP

`LoginSecurity` gioi han so request login tu cung mot IP:

- Toi da: 20 request / 60 giay.
- Ap dung truoc khi kiem tra mat khau.
- Muc dich: chan bot flood endpoint bang nhieu email khac nhau.

### 2. Rate limit theo tai khoan + IP

Moi cap `login + IP` bi gioi han:

- Toi da: 5 lan sai / 60 giay.
- Khi vuot qua, Laravel tra loi theo `auth.throttle`.
- Muc dich: chan brute force mot tai khoan cu the.

### 3. Soft lockout tang dan

Sau moi lan sai, `LoginSecurity` tang thoi gian khoa mem theo so lan thu:

- Tu lan thu 5: 60 giay.
- Tu lan thu 8: 300 giay.
- Tu lan thu 12: 900 giay.

Day la soft lockout, khong khoa vinh vien tai khoan. Cach nay giam brute force nhung tranh viec attacker loi dung hard lockout de khoa tai khoan nguoi dung.

### 4. Honeypot field

Form co field an ten `website`.

- User that khong nhin thay field nay.
- Bot tu dong dien tat ca input se dien field nay.
- Neu field co gia tri, request bi chan va ghi log warning.

### 5. Timing check

Form co timestamp luc render.

- Neu submit qua nhanh duoi 1 giay, request bi xem la automated.
- Muc dich: chan script submit truc tiep ngay sau khi load.
- Khong nen dat nguong qua cao de tranh lam kho user dung password manager.

### 6. Cloudflare Turnstile CAPTCHA

Turnstile la lop CAPTCHA phu cho login.

- Widget chi hien khi bat cau hinh trong `.env`.
- Server verify token voi Cloudflare truoc khi check password.
- Token khong hop le thi dung login ngay va khong tao session.
- Khong hardcode secret key trong source code.

Bien cau hinh:

```env
TURNSTILE_ENABLED=true
TURNSTILE_SITE_KEY=site-key-tu-cloudflare
TURNSTILE_SECRET_KEY=secret-key-tu-cloudflare
```

Sau khi sua `.env`, chay:

```bash
php artisan config:clear
php artisan optimize:clear
```

Neu khong co key hoac `TURNSTILE_ENABLED=false`, CAPTCHA se tu tat de local/test khong bi chan.

### 7. Giam timing leak khi user khong ton tai

Khi login sai, code van chay `Hash::check` voi dummy bcrypt hash neu user khong ton tai.

Muc dich la lam duong xu ly cua "email khong ton tai" va "mat khau sai" gan giong nhau hon, tranh quick-exit qua som.

### 8. Loi dang nhap chung chung

He thong khong phan biet:

- Email khong ton tai.
- Ten dang nhap khong ton tai.
- Mat khau sai.
- Bot bi chan.

Tat ca deu tra ve thong diep chung de tranh lo thong tin tai khoan.

### 9. Password policy

`AppServiceProvider` cau hinh `Password::defaults()`:

- Toi thieu 12 ky tu.
- Toi da 128 ky tu.
- Can co chu hoa/chu thuong va so.

Khong cat ngam password va khong luu password dang text. Password duoc hash qua co che cua Laravel.

### 10. Session safety

Sau khi login thanh cong:

- Session duoc regenerate.
- Rate limit theo account duoc clear.
- Logout invalidate session va regenerate CSRF token.

## Cau hinh production nen bat

Trong `.env` production:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://xlap.com.vn

SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
```

Sau khi sua `.env`, chay:

```bash
php artisan config:clear
php artisan cache:clear
```

## Deploy checklist

Sau khi deploy code login/security:

```bash
composer install --no-dev --optimize-autoloader
php artisan optimize:clear
php artisan route:clear
php artisan config:clear
npm ci
npm run build
```

Dam bao server co cac file build:

```text
public/build/manifest.json
public/build/assets/*.css
public/build/assets/*.js
```

## Huong phat trien tiep theo

- Them Cloudflare Turnstile neu bi bot tan cong that.
- Turnstile da co san trong code; bat bang `.env` khi co site key va secret key.
- Luu login attempts vao database de xem lich su IP dang ngo.
- Them 2FA cho tai khoan admin.
- Them passkey/WebAuthn khi can bao mat cao hon password.
- Them man hinh lich su dang nhap va session dang hoat dong cho user.
- Dat Cloudflare/WAF rule cho `/login` va `/livewire/update`.
