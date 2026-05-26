# 🏗️ Project Architecture

**Mục đích:** Đây là tree chuẩn của project. Khi cần hiểu structure hiện tại, đọc file này trước.

## Tree chuẩn

```text
├── app/
│   ├── Actions/
│   │   ├── Auth/                    # Logic Jetstream/Fortify: tạo user, reset pass
│   │   └── Team/                    # Logic team Jetstream
│   ├── Console/
│   │   └── Commands/                # Lệnh artisan update NBA/WNBA/MLB data
│   ├── Domain/
│   │   ├── Sports/
│   │   │   ├── MLB/
│   │   │   │   ├── Models/
│   │   │   │   ├── Services/
│   │   │   │   ├── Imports/
│   │   │   │   ├── Exports/
│   │   │   │   └── Livewire/
│   │   │   ├── NBA/
│   │   │   │   ├── Models/
│   │   │   │   ├── Services/
│   │   │   │   ├── Imports/
│   │   │   │   ├── Exports/
│   │   │   │   └── Livewire/
│   │   │   └── WNBA/
│   │   │       ├── Models/
│   │   │       ├── Services/
│   │   │       ├── Imports/
│   │   │       ├── Exports/
│   │   │       └── Livewire/
│   │   ├── Programs/
│   │   │   ├── Models/
│   │   │   ├── Services/
│   │   │   ├── Imports/
│   │   │   ├── Exports/
│   │   │   └── Livewire/
│   │   ├── Contacts/
│   │   │   ├── Models/
│   │   │   ├── Services/
│   │   │   └── Livewire/
│   │   └── Logos/
│   │       ├── Models/
│   │       ├── Services/
│   │       └── Livewire/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   ├── Admin/
│   │   │   └── Web/
│   │   ├── Middleware/
│   │   └── Requests/
│   ├── Livewire/
│   │   ├── Shared/
│   │   ├── Admin/
│   │   ├── Dashboard/
│   │   └── Modals/
│   ├── Models/
│   ├── Policies/
│   ├── Providers/
│   └── Support/
│       ├── Traits/
│       ├── Helpers/
│       └── Constants/
├── config/
│   ├── basketball_logos.php
│   ├── repository.php
│   └── ...
├── database/
│   ├── migrations/
│   │   ├── auth/
│   │   ├── sports/
│   │   ├── programs/
│   │   ├── contacts/
│   │   └── logos/
│   ├── seeders/
│   └── factories/
├── docs/
│   ├── architecture.md
│   ├── authentication.md
│   ├── database.md
│   ├── import-export.md
│   ├── nba.md
│   ├── mlb.md
│   ├── wnba.md
│   └── program.md
├── resources/
│   ├── views/
│   │   ├── layouts/
│   │   ├── components/
│   │   │   ├── atomics/
│   │   │   ├── molecules/
│   │   │   ├── icons/
│   │   │   └── program/
│   │   ├── livewire/
│   │   │   ├── shared/
│   │   │   ├── sports/
│   │   │   │   ├── mlb/
│   │   │   │   ├── nba/
│   │   │   │   └── wnba/
│   │   │   ├── programs/
│   │   │   ├── contacts/
│   │   │   └── logos/
│   │   ├── admin/
│   │   ├── auth/
│   │   └── profile/
│   ├── css/
│   ├── js/
│   └── markdown/
├── routes/
│   ├── web.php
│   ├── api.php
│   ├── admin.php
│   ├── sports.php
│   └── console.php
├── storage/
│   └── app/
│       ├── imports/
│       ├── exports/
│       └── temp/
├── tests/
│   ├── Feature/
│   ├── Unit/
│   └── Browser/
├── .env.example
├── .gitignore
├── composer.json
├── package.json
├── README.md
└── vite.config.js
```

## Quy ước sử dụng

- `app/Actions/`: logic nghiệp vụ theo nhóm chức năng.
- `app/Domain/`: business domain chính của app.
- `app/Http/Controllers/Api/`: trả data cho frontend/app ngoài.
- `app/Http/Controllers/Admin/`: module quản trị.
- `app/Http/Controllers/Web/`: render page web chính.
- `app/Livewire/Shared/`: component dùng chung.
- `routes/admin.php`, `routes/sports.php`: tách route theo domain.
- `docs/`: mô tả kiến trúc, database, import/export, và spec theo domain.

## Ghi chú cho AI

Khi user nói "đọc doc" hoặc hỏi về tree project, ưu tiên:

1. [docs/architecture.md](docs/architecture.md)
2. [docs/CLAUDE.md](docs/CLAUDE.md)
3. Tài liệu trong [docs/business-processes/](docs/business-processes)
4. Tài liệu trong [docs/connectors/](docs/connectors)