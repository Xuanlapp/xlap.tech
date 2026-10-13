# XLAP Error Log

Ghi lỗi theo khu chức năng. Dự án: XLAP Web Platform (`D:\FFACTORY\API\xlap`). Không xóa hoặc ghi đè lịch sử: khi có cách sửa tốt hơn, thêm mục "Cập nhật giải pháp" cùng mã lỗi và ngày mới ngay dưới mục gốc.

## Mục lục

- [IMG — Ảnh & Mockup](#img--ảnh--mockup): Source Image, Create Master, PSD, thumbnail, preview, đồng bộ ảnh.
- [IMP — Import & dữ liệu](#imp--import--dữ-liệu): Excel/CSV, SKU, cột database, đồng bộ bản ghi.
- [MKT — Marketplace & Export](#mkt--marketplace--export): listing metadata, prompt, export Excel/Pattern.
- [DRV — Drive & lưu trữ](#drv--drive--lưu-trữ): upload Drive, quyền file, đường dẫn, Syncthing.
- [DAS — Dashboard & thống kê](#das--dashboard--thống-kê): bộ lọc, số đếm, báo cáo.
- [ACC — Tài khoản & phân quyền](#acc--tài-khoản--phân-quyền): user, admin, xác thực.
- [INF — Server & hiệu năng](#inf--server--hiệu-năng): VPS, queue, PHP-FPM, lỗi tải/chậm.
- [OTH — Khác](#oth--khác): lỗi chưa xác định được khu; chuyển đúng khu khi tìm ra.

Mỗi lỗi thuộc **một khu chính**; nếu ảnh hưởng khu khác, ghi tại `Liên quan` và tham chiếu mã lỗi, không sao chép nhiều bản. Tìm bằng `rg "IMG-|IMP-|MKT-|DRV-|DAS-|ACC-|INF-|OTH-" ERROR_LOG.md` hoặc tìm từ khóa trong khu tương ứng.

## Mẫu ghi lỗi

### [KHU-YYYYMMDD-01] Tên lỗi
- Ngày phát hiện:
- Hiện tượng:
- Phạm vi:
- Liên quan:
- Nguyên nhân gốc:
- Giải pháp đã áp dụng:
- Kiểm chứng:
- Ảnh hưởng deploy/queue/dữ liệu:
- Giải pháp tốt hơn có thể thử:
- Trạng thái: Open | Fixed | Monitoring

Khi cải tiến giải pháp, thêm dưới mục lỗi đó: `#### Cập nhật giải pháp YYYY-MM-DD` với lý do, thay đổi, kiểm chứng và trạng thái mới.

## IMG — Ảnh & Mockup
### [IMG-20261012-01] Mockup thumbnail rollout audit
- Symptom: Mockup cards previously loaded full PNG files; new thumbnails must not break originals or Drive URLs.
- Scope: Glass, Ceramic, Decal, Sticker, Suncatcher, and Ornament Amazon 2 card flows; shared PSD renderer; local Glass worker.
- Root cause: No single verified thumbnail path/fallback contract existed. The older ImageThumbnailService also uses a legacy `source.ext.webp` naming rule for other image flows.
- Applied solution: New mockup-only card selector uses `thumbnails/generated/{product}/mockups/{asset_id}/MOCKUP N.webp`, verifies existence/size/mtime, and falls back to the original preview. Renderers write WebP through a temporary file and atomic rename. Original database fields and Drive upload fields remain unchanged.
- Validation: PHP lint, Node syntax checks, `php artisan view:cache`, and focused diff checks passed. No authenticated browser or real synchronized PSD render was available, so visual and VPS sync behavior remain under monitoring.
- Deploy/queue/data impact: Deploy the Laravel files and renderer/worker; restart local mockup worker. No migration. Existing originals remain safe; old thumbnails are not deleted automatically.
- Better future solution: Consolidate legacy and mockup thumbnail generation behind one versioned `ImageThumbnailService` after inventorying all callers; add an authenticated integration test for local file, missing thumbnail, stale thumbnail, Drive fallback, and delete cleanup.
- Status: Monitoring
### [IMG-20261012-02] Giới hạn PSD mockup theo user/trang
- Ngày phát hiện: 2026-10-12
- Hiện tượng: Một user có thể upload nhiều file PSD cho cùng một trang sản phẩm; danh sách phình to. Thời gian hiển thị có thể bị hiểu nhầm là thời gian cập nhật/chọn lại thay vì thời gian upload.
- Phạm vi: PSD Mockup Tu Chon của Glass, Ceramic, Decal, Sticker, Suncatcher, Ornament Amazon 2 và Ornament Etsy; giới hạn theo user + product + function.
- Liên quan: IMG-20261012-01; bản ghi mockup PNG và queue render không bị thay đổi.
- Nguyên nhân gốc: Repository chỉ tắt `is_active` của bản cũ nhưng không giới hạn số bản; file upload được lưu trước khi có chính sách dọn dẹp; giao diện chưa hiển thị rõ `created_at`.
- Giải pháp đã áp dụng: Sau khi upload và tạo bản ghi mới thành công, repository giữ lại 2 bản mới nhất theo `created_at`/ID, xóa bản cũ hơn khỏi database và storage nếu không còn bản ghi tham chiếu; khóa user trong transaction để tránh upload đồng thời vượt giới hạn; nếu tạo bản ghi lỗi thì xóa file vừa upload. Bảy service PSD dùng chung luồng upload mới. UI ghi rõ giới hạn 2 file và hiển thị `created_at` là thời gian Upload; việc activate không làm đổi thời gian này.
- Kiểm tra đã chạy: PHP lint cho repository và 7 service đạt; `php artisan view:cache` đạt; test upload PSD tập trung đạt (1 test, 4 assertions); test schema tổng thể còn các lỗi legacy không liên quan, không dùng làm tiêu chí pass cho thay đổi này.
- Ảnh hưởng deploy/queue/database: cần deploy repository, 7 service và 7 Blade modal; không cần migration; không đổi API, queue hay business logic render; upload thứ ba trở đi sẽ xóa file PSD cũ nhất của đúng user/trang.
- Giải pháp tốt hơn trong tương lai: thêm unique/retention policy ở database hoặc scheduled orphan-file cleanup có dry-run; bổ sung integration test upload lần 3, upload lỗi giữa chừng, concurrent upload và kiểm tra storage thật trên VPS.
- Trạng thái: Fixed

## IMP — Import & dữ liệu

### [IMP-20261012-01] XLSX import không đọc được worksheet
- Hiện tượng: XLSX hợp lệ báo `Spreadsheet has no readable worksheet`.
- Nguyên nhân gốc: SimpleXML bị kiểm tra truthy; cell có namespace prefix.
- Giải pháp đã áp dụng: Kiểm tra `=== false` và XPath có namespace.
- Kiểm chứng: File mẫu 124 dòng gồm header; PHP lint đạt, chưa import database.
- Deploy/queue/dữ liệu: Deploy importer; không migration hoặc queue.
- Giải pháp tốt hơn: Thêm XLSX namespace-prefixed vào test tự động.
- Trạng thái: Fixed

### [IMP-20261012-02] Trùng SKU khi đồng bộ order item
- Hiện tượng: MySQL `1062 Duplicate entry` theo user/SKU.
- Nguyên nhân gốc: Chỉ match theo asset, bỏ qua SKU cũ của cùng user.
- Giải pháp đã áp dụng: Transaction/lock kiểm tra cả asset và user/SKU.
- Kiểm chứng: 4 tests, 12 assertions passed.
- Deploy/queue/dữ liệu: Deploy service; không migration mới; không tự xóa xung đột cũ.
- Giải pháp tốt hơn: Màn hình giải quyết xung đột thủ công.
- Trạng thái: Fixed


## MKT — Marketplace & Export

_Chưa có mục._

## DRV — Drive & lưu trữ

_Chưa có mục._

## DAS — Dashboard & thống kê

### [DAS-20261012-01] Status counters cũ sau mutation
- Hiện tượng: Số Tất cả/Chưa duyệt/Đã duyệt/No Mockup không đổi ngay sau import, duyệt, xóa.
- Nguyên nhân gốc: Handler Livewire no-op và cache count 5 giây.
- Giải pháp đã áp dụng: Event handler xóa cache theo user/search trước khi render lại.
- Kiểm chứng: PHP lint 7 component và diff check đạt.
- Deploy/queue/dữ liệu: Deploy Livewire pages; không migration/queue.
- Giải pháp tốt hơn: Cache tags theo user/product.
- Trạng thái: Fixed


## ACC — Tài khoản & phân quyền

_Chưa có mục._

## INF — Server & hiệu năng

_Chưa có mục._

## OTH — Khác

_Chưa có mục._
