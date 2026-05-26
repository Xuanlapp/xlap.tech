# 📊 Business Pulse - Tình Trạng Kinh Doanh

**Mục đích:** Dashboard tổng hợp health của business với các chỉ số quan trọng, alerts, và trends.

---

## 🎯 Chức Năng Chính

### 1. **Real-time Dashboard**
Hiển thị:
- 💰 Revenue overview (ngày, tuần, tháng, năm)
- 📋 Pending invoices (chưa thanh toán)
- ⏰ Upcoming deadlines (hợp đồng, thuế, close-month)
- 👥 Team metrics (productivity, efficiency)

### 2. **Key Performance Indicators (KPIs)**
```
┌──────────────────────────────────────────┐
│ Monthly Revenue:      $X,XXX             │
├──────────────────────────────────────────┤
│ Pending Invoices:     $X,XXX (5 invoices)│
├──────────────────────────────────────────┤
│ Collection Rate:      X% (target: 95%)   │
├──────────────────────────────────────────┤
│ Average Days to Pay:  X days (target: 30)│
├──────────────────────────────────────────┤
│ Overdue Invoices:     X ($X,XXX)         │
└──────────────────────────────────────────┘
```

### 3. **Alerts & Notifications**
```
🔴 Critical
├── Invoices overdue > 30 days
├── Cash flow negative
└── Critical deadlines approaching

🟡 Warning
├── Invoices overdue > 7 days
├── Multiple unpaid invoices
└── Upcoming reconciliation

🟢 Info
├── Invoice paid
├── Contract signed
└── Monthly close complete
```

### 4. **Trending & Analytics**
- Month-over-month revenue growth
- Invoice payment velocity
- Client segments analysis
- Expense trends

---

## 🔄 Data Collection Flow

```
Database Queries
    ↓
├── Sum revenue (invoices marked as paid)
├── Count pending invoices (status = "sent")
├── Calculate collection rate (paid / total * 100)
├── Find overdue invoices (due_date < today)
├── Get upcoming deadlines (contracts, taxes)
└── Aggregate team metrics

    ↓
Calculate Metrics
    ↓
├── Trend calculation (compare to previous period)
├── KPI variance (actual vs target)
├── Alert generation (if thresholds exceeded)
└── Forecast (simple projection)

    ↓
Cache Results (Redis)
    ↓
Display in Dashboard
```

---

## 📱 Component Structure

```
BusinessPulseComponent
├── Properties
│   ├── $metrics = []
│   ├── $alerts = []
│   ├── $period = "month"
│   └── $startDate, $endDate
├── Methods
│   ├── calculateMetrics()
│   ├── generateAlerts()
│   ├── getKPIs()
│   └── refreshData()
└── Views
    ├── dashboard.blade.php
    ├── kpi-cards.blade.php
    ├── alerts.blade.php
    └── trending-chart.blade.php
```

---

## 💾 Database Queries Needed

```sql
-- Monthly Revenue
SELECT SUM(amount) FROM invoices 
WHERE status = 'paid' 
AND MONTH(created_at) = MONTH(NOW());

-- Pending Invoices
SELECT COUNT(*), SUM(amount) FROM invoices 
WHERE status IN ('sent', 'viewed');

-- Overdue Invoices
SELECT * FROM invoices 
WHERE due_date < DATE_NOW() 
AND status != 'paid';

-- Collection Rate
SELECT 
  COUNT(CASE WHEN status = 'paid' THEN 1 END) / COUNT(*) * 100 
FROM invoices 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY);
```

---

## 🚀 Implementation Steps

1. **Create DatabaseSeeder** - Populate sample data
2. **Create InvoiceModel** - With relationships
3. **Create BusinessMetricsModel** - Store calculated metrics
4. **Create Actions/CalculateMetricsAction.php**
5. **Create Livewire/BusinessPulse.php**
6. **Create Views/business-pulse.blade.php**
7. **Create Tests/BusinessPulseTest.php**

---

## 🔗 Related Files

- `app/Models/Invoice.php` - Invoice model & relationships
- `app/Actions/CalculateMetricsAction.php` - Business logic
- `app/Livewire/BusinessPulse.php` - Component
- `resources/views/livewire/business-pulse.blade.php` - Template
- `tests/Feature/BusinessPulseTest.php` - Tests

---

## 📈 Example Implementation

### Action - CalculateMetricsAction.php
```php
<?php

namespace App\Actions;

use App\Models\Invoice;
use Illuminate\Support\Collection;

/**
 * Tính toán các metrics kinh doanh
 * 
 * Action này collect dữ liệu từ database, tính toán KPIs,
 * và sinh ra alerts nếu cần thiết
 */
class CalculateMetricsAction
{
    /**
     * Tính toán tất cả metrics
     * 
     * @param \Carbon\Carbon|null $startDate
     * @param \Carbon\Carbon|null $endDate
     * @return array
     */
    public function execute($startDate = null, $endDate = null): array
    {
        $startDate = $startDate ?? now()->startOfMonth();
        $endDate = $endDate ?? now()->endOfMonth();

        return [
            'revenue' => $this->calculateRevenue($startDate, $endDate),
            'pending' => $this->calculatePending(),
            'overdue' => $this->calculateOverdue(),
            'collection_rate' => $this->calculateCollectionRate(),
            'average_days_to_pay' => $this->calculateAverageDaysTopay(),
            'alerts' => $this->generateAlerts(),
        ];
    }

    private function calculateRevenue($start, $end): float
    {
        return Invoice::whereBetween('paid_at', [$start, $end])
            ->sum('amount');
    }

    // ... more methods
}
```

---

## 🎯 Success Criteria

- ✅ Dashboard loads in < 2 seconds
- ✅ Data refreshes automatically every 5 minutes
- ✅ Alerts notify team within 1 minute of trigger
- ✅ KPI targets configurable in admin panel
- ✅ Metrics accurate within 1 day

---

**Created:** May 25, 2026  
**Status:** Documentation Complete
