# 📅 Close Month - Đóng Sổ Cuối Tháng

**Mục đích:** Quy trình tự động hoặc bán tự động để đóng sổ kinh doanh hàng tháng một cách chính xác và đầy đủ.

---

## 🎯 Chức Năng Chính

### 1. **Month-End Lockdown**
- Lock tất cả transactions của tháng trước
- Prevent edit/delete của transactions đã lock
- Allow corrections thông qua amendment entries

### 2. **Reconciliation**
- Match invoices với payments
- Identify discrepancies
- Flag items cần investigation
- Auto-resolve easy cases (exact amounts)

### 3. **Financial Reporting**
- Generate P&L statement
- Generate Balance Sheet
- Generate Cash Flow statement
- Calculate key ratios (profit margin, current ratio, etc.)

### 4. **Approval Workflow**
- Report generated → Review by Accountant → Approve by Manager
- Comments & notes at each stage
- Audit trail of all changes

### 5. **Data Archive**
- Archive month's data (read-only)
- Generate backup
- Prepare for next month

---

## 🔄 Close Month Process Flow

```
┌───────────────────────────────────┐
│  Month End (Last day of month)    │
└───────────┬───────────────────────┘
            │
            ▼
┌───────────────────────────────────┐
│  PHASE 1: Preparation             │
├───────────────────────────────────┤
│ • Lock previous month data        │
│ • Verify all transactions entered │
│ • Identify gaps                   │
│ • Alert team for follow-up        │
└───────────┬───────────────────────┘
            │
            ▼
┌───────────────────────────────────┐
│  PHASE 2: Reconciliation          │
├───────────────────────────────────┤
│ • Match invoices to payments      │
│ • Check bank reconciliation       │
│ • Verify aging of AR              │
│ • Review AP for disputes          │
└───────────┬───────────────────────┘
            │
            ▼
┌───────────────────────────────────┐
│  PHASE 3: Reports Generation      │
├───────────────────────────────────┤
│ • P&L Statement                   │
│ • Balance Sheet                   │
│ • Cash Flow Statement             │
│ • KPI Dashboard                   │
│ • Executive Summary               │
└───────────┬───────────────────────┘
            │
            ▼
┌───────────────────────────────────┐
│  PHASE 4: Review & Approval       │
├───────────────────────────────────┤
│ • Accountant review               │
│ • Manager approval                │
│ • Comments & adjustments          │
│ • Sign-off                        │
└───────────┬───────────────────────┘
            │
            ▼
┌───────────────────────────────────┐
│  PHASE 5: Archive & Complete      │
├───────────────────────────────────┤
│ • Mark month as closed            │
│ • Archive data                    │
│ • Generate final backup           │
│ • Send confirmation               │
│ • Prepare for next month          │
└───────────────────────────────────┘
```

---

## 📊 Checklist

### Pre-Close Checklist
```
Day 25-28:
- [ ] Review all pending invoices
- [ ] Follow up on overdue invoices
- [ ] Verify all payments received
- [ ] Check for duplicate entries

Day 29-30:
- [ ] Lock month
- [ ] Run reconciliation
- [ ] Generate reports
- [ ] Review for errors
- [ ] Make adjustments

Day 31/Last day:
- [ ] Final approval
- [ ] Archive data
- [ ] Backup everything
- [ ] Send notification
```

### Validation Checks
```
Financial Validation:
├── Debit = Credit (Accounting equation)
├── AR aging report accuracy
├── AP aging report accuracy
├── Bank reconciliation matches
├── GL trial balance balances
└── No suspicious transactions

Data Quality Checks:
├── All transactions have descriptions
├── All payments reconciled
├── No duplicate entries
├── Categories assigned correctly
└── No locked/pending transactions
```

---

## 💾 Key Data Points

```
Monthly Summary:
├── Total Revenue
├── Total Expenses
├── Net Income/Loss
├── Beginning Balance
├── Ending Balance
├── Accounts Receivable
├── Accounts Payable
├── Cash on Hand
└── Key Ratios

Transaction Details:
├── Invoice count
├── Payment count
├── Expense count
├── Write-off count
├── Adjustment count
└── Cancelled transactions
```

---

## 🤖 Automation Opportunities

### Auto-Lock
```php
// Every month on the 1st at 00:00
schedule
    ->call(function () {
        Invoice::whereMonth('created_at', now()->subMonth())
            ->update(['locked' => true]);
    })
    ->monthlyOn(1, '00:00');
```

### Auto-Reconcile
```php
// Match invoices with exact payment amounts
function autoReconcile() {
    $unmatched = Invoice::where('status', 'sent')->get();
    
    foreach ($unmatched as $invoice) {
        $payment = Payment::where('amount', $invoice->amount)
            ->whereNull('invoice_id')
            ->first();
            
        if ($payment) {
            $invoice->update(['status' => 'paid', 'payment_id' => $payment->id]);
        }
    }
}
```

### Auto-Generate Reports
```php
// Generate all reports at month end
schedule
    ->call(new GenerateMonthlyReports())
    ->monthlyOn(1, '01:00');
```

---

## 📁 Files Generated/Archived

```
Archive Structure:
├── 2026-05/ (May 2026)
│   ├── invoices.json
│   ├── payments.json
│   ├── expenses.json
│   ├── p_and_l.pdf
│   ├── balance_sheet.pdf
│   ├── cash_flow.pdf
│   ├── reconciliation_report.json
│   ├── metadata.json
│   └── backup.sql
```

---

## 🔗 Related Integrations

### QuickBooks
- Export closed month data
- Sync GL accounts
- Verify against QB records
- Generate tax documents

### Bank
- Get final bank statement
- Reconcile
- Download transactions

### Email
- Send reports to stakeholders
- Notification of completion
- Archive link for reference

---

## 📱 Component Structure

```
CloseMonthComponent
├── Properties
│   ├── $month
│   ├── $year
│   ├── $status (preparing, locked, reconciled, reported, approved, archived)
│   ├── $discrepancies = []
│   └── $reports = []
├── Methods
│   ├── prepareForClose()
│   ├── lockMonth()
│   ├── runReconciliation()
│   ├── generateReports()
│   ├── submitForApproval()
│   ├── approve()
│   └── archiveMonth()
└── Views
    ├── close-month-wizard.blade.php
    ├── reconciliation-details.blade.php
    ├── reports-view.blade.php
    └── approval-workflow.blade.php
```

---

## 🎯 Success Criteria

- ✅ Process completes within 2 hours
- ✅ All discrepancies identified & resolved
- ✅ Reports accurate to the penny
- ✅ Audit trail complete
- ✅ Zero errors on first close
- ✅ 100% reconciliation

---

**Created:** May 25, 2026  
**Status:** Documentation Complete
