# 📋 XLAP Tech - Architecture & Business Logic Documentation

**Mục đích:** File này giúp AI (Claude, ChatGPT, v.v.) hiểu rõ toàn bộ cấu trúc project, logic kinh doanh, và cách hệ thống hoạt động.

---

## 🏢 Project Overview

**Project Name:** XLAP Tech  
**Type:** Laravel + Livewire Web Application  
**Purpose:** Quản lý business processes, invoices, contracts, và team coordination  
**Stack:** PHP/Laravel, Livewire, Vue/Alpine, Tailwind CSS, Vite

---

## 📁 Cấu Trúc Thư Mục Chi Tiết

**Nguồn chuẩn để đọc tree project:** [docs/architecture.md](docs/architecture.md).  
Khi bạn nói "đọc doc", ưu tiên đọc file này trước để nắm cấu trúc mới nhất.

```
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

---

## 🔄 Business Logic Flow

### 1. **Business Pulse** - Tình trạng kinh doanh
```
┌─────────────────────────────────────────┐
│ Business Health Dashboard               │
├─────────────────────────────────────────┤
│ • Revenue Overview                      │
│ • Pending Invoices                      │
│ • Upcoming Deadlines                    │
│ • Team Performance Metrics              │
└─────────────────────────────────────────┘
```

### 2. **Invoice Chase** - Theo dõi hoá đơn
```
Invoice Created → Sent → Remind → Follow-up → Paid/Overdue
   |                |         |         |           |
   ├─ Store DB   ├─ Log    ├─ Auto   ├─ Alert    ├─ Update
   │             │  Email   │  Email  │  Slack    │  Status
   └─ Notify     └─ Record  └─ Track  └─ Report   └─ Done
```

### 3. **Close Month** - Đóng sổ tháng
```
Month End → Reconciliation → Report Generation → Approval → Archive
   │            │                    │               │          │
   ├─ Lock   ├─ Match Data      ├─ P&L Report   ├─ Review   ├─ Store
   │         ├─ Check Balance   ├─ Cash Flow    │           │
   └─ Notify └─ Flag Issues     └─ Balance Sheet└─ Sign     └─ Done
```

### 4. **Tax Prep** - Chuẩn bị thuế
```
Quarter/Year → Data Collection → Document Prep → Review → Submit
   │                  │                 │           │        │
   ├─ Start      ├─ Export from    ├─ Generate  ├─ Verify └─ File
   │             │  Accounting     │   PDF      │         
   └─ Schedule   └─ Organize       └─ Validate  └─ Sign
```

---

## 🔗 Data Flow Architecture

```
┌─────────────────────────────────────────────────────────┐
│                    User Interface                        │
│              (Livewire Components + Blade)              │
└────────────────┬────────────────────────────────────────┘
                 │ HTTP Request
                 ▼
┌─────────────────────────────────────────────────────────┐
│                  Livewire Component                      │
│        (Real-time state management & UI logic)          │
└────────────────┬────────────────────────────────────────┘
                 │ Call Action/Method
                 ▼
┌─────────────────────────────────────────────────────────┐
│                   Business Logic Layer                   │
│            (Actions, Services, Controllers)             │
└────────────────┬────────────────────────────────────────┘
                 │ Database Query
                 ▼
┌─────────────────────────────────────────────────────────┐
│                   Database Layer                         │
│              (Models, Eloquent ORM)                      │
└────────────────┬────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────┐
│              External Integrations                       │
│    (QuickBooks, Gmail, Slack, HubSpot APIs)            │
└─────────────────────────────────────────────────────────┘
```

---

## 🎯 Key Components & Their Responsibilities

### **Livewire Components** (`app/Livewire/`)
- **Real-time interactivity** - Không cần page reload
- **State management** - Quản lý state của UI
- **Two-way binding** - Tự động sync giữa UI và backend

**Example - Counter Component:**
```php
// Component logic
public $count = 0;

public function increment() {
    $this->count++;  // State thay đổi
    // Livewire tự động re-render view
}
```

### **Database Models** (`app/Models/`)
- Đại diện cho các entities (User, Invoice, Contract, etc.)
- Định nghĩa relationships giữa các bảng
- Contain business rules & validation

### **Actions** (`app/Livewire/Actions/`)
- Chứa business logic độc lập
- Reusable trong nhiều components
- Dễ test & maintain

### **External Connectors** (`docs/connectors/`)
- Kết nối với third-party services
- Xử lý API calls & data synchronization
- Manage authentication & error handling

---

## 📊 Database Schema Overview

```
Users
├── id
├── name
├── email
├── role (admin, user, accountant, etc.)
└── timestamps

Invoices
├── id
├── user_id (FK)
├── invoice_number
├── amount
├── status (draft, sent, paid, overdue)
├── due_date
├── created_at
└── updated_at

Contracts
├── id
├── title
├── content
├── status (draft, active, expired)
├── start_date
├── end_date
└── timestamps

BusinessMetrics
├── id
├── date
├── revenue
├── expenses
├── pending_invoices_count
└── timestamps
```

---

## 🚀 Common Workflows

### Workflow 1: Create & Send Invoice
```
1. User fills invoice form → Validates → Store in DB
2. Generate PDF from template
3. Send via Email (Gmail integration)
4. Log activity in invoice tracker
5. Update business metrics
6. Notify team via Slack
```

### Workflow 2: Monthly Close
```
1. Lock all transactions for the month
2. Run reconciliation checks
3. Generate financial reports
4. Calculate pending invoices
5. Export to QuickBooks (if needed)
6. Archive month data
7. Prepare for next month
```

---

## 🔐 Security & Validation Rules

1. **Authentication:**
   - All routes require login
   - Role-based access control

2. **Data Validation:**
   - Input sanitization
   - Email format validation
   - Amount validation (positive numbers)

3. **API Rate Limiting:**
   - External API calls throttled
   - Prevent abuse

---

## 📝 Documentation Guidelines for AI

Khi AI đọc code, cần có:

1. **PHPDoc Comments**
   ```php
   /**
    * Tạo hoá đơn mới
    * 
    * @param array $data Invoice data (amount, client, items)
    * @return Invoice Created invoice instance
    * @throws ValidationException Nếu data không hợp lệ
    */
   public function createInvoice(array $data): Invoice
   ```

2. **Inline Comments** giải thích logic phức tạp
3. **Type Hints** rõ ràng cho parameters & return types
4. **Error Handling** - Hiển thị cách xử lý lỗi

---

## 🔍 How to Read This Documentation

**For AI Assistant:**
1. Đọc file này trước (CLAUDE.md)
2. Đọc memory.md để hiểu context & rules
3. Đọc README.md trong từng business-process folder
4. Xem code với PHPDoc comments để hiểu chi tiết

**Order to Read:**
```
CLAUDE.md (this file)
    ↓
memory.md
    ↓
business-processes/[relevant-process]/README.md
    ↓
connectors/[relevant-tool].md
    ↓
Source code files with PHPDoc comments
```

---

## 🤝 Communication Tips

Khi nói chuyện với AI:
- "Dựa vào docs/CLAUDE.md, giúp tôi..."
- "Theo quy trình trong docs/business-processes/invoice-chase/,..."
- "Sử dụng integration đã define trong docs/connectors/gmail.md..."

---

## Current Feature Docs

- Sticker PSD custom mockup: [docs/sticker-psd-mockup.md](sticker-psd-mockup.md)

**Last Updated:** May 29, 2026  
**Version:** 1.0
