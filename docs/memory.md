# 🧠 Memory & Context - Important Information

**Mục đích:** Lưu trữ các thông tin quan trọng, rules, và context mà AI cần luôn nhớ khi làm việc với project này.

---

## ⚡ Critical Rules

### Development Rules
- ✅ **Always add PHPDoc comments** khi tạo method mới
- ✅ **Use type hints** - `public function getName(): string`
- ✅ **Validate inputs** - Không bao giờ trust user input
- ✅ **Use transactions** khi modify multiple related records
- ✅ **Log important actions** - Invoice sent, payment received, etc.
- ❌ **Never hardcode secrets** - Sử dụng .env file
- ❌ **Never DELETE data** - Soft delete hoặc archive thay vì

### Naming Conventions
- **Class names:** `PascalCase` - `InvoiceTracker`, `EmailService`
- **Method names:** `camelCase` - `getInvoices()`, `sendEmail()`
- **Properties:** `camelCase` - `$invoiceAmount`, `$dueDate`
- **Constants:** `UPPER_SNAKE_CASE` - `MAX_RETRIES`, `API_TIMEOUT`

### File Organization
- Models → `app/Models/`
- Controllers → `app/Http/Controllers/`
- Livewire Components → `app/Livewire/`
- Business Logic → `app/Actions/` hoặc `app/Services/`
- Views → `resources/views/`
- Documentation → `docs/`

---

## 🎯 Key Business Rules

### Invoice Management
| Status | Meaning | Action |
|--------|---------|--------|
| Draft | Chưa hoàn thành | Có thể edit hoặc delete |
| Sent | Đã gửi cho client | Chờ thanh toán |
| Paid | Đã nhận thanh toán | Archive |
| Overdue | Quá hạn | Gửi reminder/alert |

### Monthly Close
- **Deadline:** Last day of month
- **Process:** Lock → Reconcile → Report → Archive
- **Checks:** All invoices must be accounted for
- **Notification:** Slack alert when complete

### Tax Preparation
- **Quarterly:** Q1, Q2, Q3, Q4
- **Annual:** December 31st deadline
- **Documents needed:** Invoices, Expenses, Receipts, Payments
- **Export format:** PDF, Excel compatible

---

## 🔄 Common Data Relationships

```
User (1) ──→ (M) Invoices
           ──→ (M) Contracts
           ──→ (M) Activities

Invoice (1) ──→ (M) Payments
          ──→ (1) Client

Contract (1) ──→ (M) Amendments
          ──→ (1) Party1
          ──→ (1) Party2

BusinessMetrics (1) ──→ (1) Month
                   ──→ (M) Transactions
```

---

## 🔧 External Integrations

### QuickBooks
- **Purpose:** Sync financial data
- **Auth:** OAuth 2.0
- **Rate Limit:** 500 requests/hour
- **Sync Frequency:** Every 6 hours
- **Docs:** `docs/connectors/quickbooks.md`

### Gmail
- **Purpose:** Send invoices, reminders
- **Auth:** OAuth 2.0 (service account)
- **Rate Limit:** 1000 emails/day
- **Template Storage:** `resources/views/emails/`
- **Docs:** `docs/connectors/gmail.md`

### Slack
- **Purpose:** Notifications & alerts
- **Auth:** Bot token
- **Channels:** #business-alerts, #invoices, #team-updates
- **Rate Limit:** No strict limit
- **Docs:** `docs/connectors/slack.md`

### HubSpot
- **Purpose:** Client & contact management
- **Auth:** API key
- **Sync:** Clients ↔ HubSpot contacts
- **Rate Limit:** 100 requests/10 seconds
- **Docs:** `docs/connectors/hubspot.md`

---

## 🧭 Source of Truth for Project Tree

- Khi cần hiểu cấu trúc project hiện tại, đọc [docs/architecture.md](docs/architecture.md) trước.
- File [docs/CLAUDE.md](docs/CLAUDE.md) là tài liệu AI tổng quan, còn [docs/architecture.md](docs/architecture.md) là tree chuẩn để bám theo.
- Nếu có thay đổi cấu trúc thư mục, cập nhật đồng thời cả hai file.

## 📊 Key Metrics & KPIs

```
Revenue Metrics:
├── Monthly Revenue
├── Average Invoice Value
├── Revenue per Client
└── Trending (MoM Growth)

Operational Metrics:
├── Invoice Count (monthly)
├── Paid Invoice Rate (%)
├── Average Days to Payment
└── Overdue Invoice Count

Financial Health:
├── Cash on Hand
├── Accounts Receivable
├── Accounts Payable
└── Profit Margin (%)
```

---

## 🛠️ Tech Stack Details

### Backend
- **Framework:** Laravel 11
- **Component Library:** Livewire 3.x
- **Database:** MySQL/PostgreSQL
- **Auth:** Laravel Sanctum / Session-based
- **Queue:** Redis (for async tasks)

### Frontend
- **UI Framework:** Tailwind CSS
- **JS Framework:** Alpine.js (lightweight interactivity)
- **Build Tool:** Vite
- **Icons:** Heroicons

### DevOps
- **Hosting:** (To be specified)
- **Database:** MySQL
- **File Storage:** Local or S3
- **Monitoring:** (To be specified)

---

## 🔒 Environment Variables Needed

```env
# Database
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=xlap_tech
DB_USERNAME=root
DB_PASSWORD=

# External APIs
QUICKBOOKS_CLIENT_ID=xxx
QUICKBOOKS_CLIENT_SECRET=xxx
GMAIL_SERVICE_ACCOUNT_JSON=/path/to/service-account.json
SLACK_BOT_TOKEN=xoxb-xxx
HUBSPOT_API_KEY=xxx

# App
APP_ENV=local
APP_DEBUG=true
APP_KEY=base64:xxx
APP_URL=http://localhost:8000
```

---

## 📞 Contact & Escalation

**Code Questions:** Check `docs/CLAUDE.md` first  
**Business Logic Questions:** Check relevant `docs/business-processes/*/README.md`  
**API Integration Issues:** Check `docs/connectors/*.md`  
**Unknown Issues:** Check `memory.md` then `CLAUDE.md`

---

## 🔄 Common Commands

```bash
# Development
php artisan serve                  # Start dev server
npm run dev                        # Vite watch mode
php artisan tinker                # PHP REPL

# Database
php artisan migrate               # Run migrations
php artisan seed                  # Run seeders
php artisan migrate:fresh         # Fresh database

# Testing
php artisan test                  # Run PHPUnit tests

# Livewire
php artisan livewire:make ComponentName  # Create component
php artisan livewire:make Actions/MyAction --action  # Create action

# Cache & Optimization
php artisan cache:clear           # Clear all cache
php artisan config:cache          # Cache config
php artisan route:cache           # Cache routes
```

---

## ⏰ Time Zones & Scheduling

- **Primary Time Zone:** UTC or Asia/Ho_Chi_Minh (to be confirmed)
- **Business Hours:** 9 AM - 6 PM
- **Invoice Reminders:** Sent at 10 AM
- **Daily Reports:** Generated at 8 AM
- **Monthly Close:** Last day of month, 5 PM

---

## 📋 Checklists

### Before Deploying Code
- [ ] All tests passing
- [ ] No console errors
- [ ] No database errors
- [ ] PHPDoc comments added
- [ ] Error handling implemented
- [ ] Performance tested

### Before Release
- [ ] Database migrations ready
- [ ] .env variables documented
- [ ] API endpoints tested
- [ ] External integrations verified
- [ ] Security audit passed
- [ ] User documentation updated

---

**Last Updated:** May 25, 2026  
**Version:** 1.0  
**Status:** Active
