# 🤖 AI Execution Standard (Copy/Paste)

Mục tiêu: bất kỳ AI/dev nào cũng phải đọc đúng cấu trúc, phân tích đúng yêu cầu, triển khai chuẩn, test kỹ, và chỉ bàn giao khi chạy ổn.

## Prompt chuẩn để dán mỗi lần giao việc

Đọc trước các docs sau và bám đúng theo đó:
- [docs/architecture.md](docs/architecture.md)
- [docs/CLAUDE.md](docs/CLAUDE.md)
- [docs/memory.md](docs/memory.md)
- Nếu liên quan nghiệp vụ thì đọc thêm [docs/business-processes/](docs/business-processes)
- Nếu liên quan tích hợp thì đọc thêm [docs/connectors/](docs/connectors)

Yêu cầu bắt buộc:
1. Phân tích yêu cầu thành checklist rõ ràng trước khi code.
2. Xác định đúng file cần sửa theo tree chuẩn, không sửa lan man.
3. Triển khai theo best practice Laravel + Livewire, ưu tiên an toàn và dễ bảo trì.
4. Giữ tương thích code cũ, không phá route/chức năng đang chạy.
5. Viết validation, xử lý lỗi, log cần thiết.
6. Sau khi code phải tự test:
   - chạy lint/compile nếu có
   - chạy test hiện có
   - test luồng chính bằng tay
7. Nếu lỗi thì tự fix tiếp đến khi chạy ổn.
8. Chỉ kết thúc khi:
   - không còn lỗi liên quan
   - luồng chính chạy được
   - báo cáo rõ file đã sửa, lý do sửa, cách test, kết quả test.

Định dạng trả kết quả:
- Tóm tắt phân tích
- Danh sách file đã sửa
- Logic chính đã triển khai
- Các bước test đã chạy + kết quả
- Rủi ro còn lại (nếu có) + đề xuất xử lý

## Definition of Done

Chỉ coi là hoàn thành khi đạt đủ:
- Đúng yêu cầu nghiệp vụ
- Không lỗi syntax/runtime liên quan phần vừa sửa
- Các route/chức năng chính hoạt động
- Có kiểm tra và xác nhận sau sửa
- Có ghi chú vận hành rõ ràng cho lần sau
