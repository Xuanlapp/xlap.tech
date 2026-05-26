# 🧮 Tax Prep - Chuẩn Bị Tài Liệu Thuế

**Mục đích:** Hệ thống tổng hợp, sắp xếp, và chuẩn bị tài liệu thuế cho các kỳ báo cáo (Quý/Năm).

---

## 🎯 Chức Năng Chính

### 1. **Quarterly Tax Calculation**
- Calculate quarterly tax liability
- Estimate payments due
- Track paid vs owed
- Identify overpayment/underpayment

### 2. **Document Collection**
```
Invoices
├── All invoices for period
├── Revenue by category
├── Revenue by client
└── Uncollectible tracking

Expenses
├── By category
├── Receipts attached
├── Mileage log
└── Depreciation schedule

Deductions
├── Home office
├── Equipment
├── Software subscriptions
├── Professional fees
└── Other deductible expenses

Payment Records
├── Quarterly estimated taxes
├── Employment taxes
├── Sales tax
└── Other tax payments
```

### 3. **Tax Form Generation**
- 1040-ES (Estimated Tax)
- Schedule C (Self-Employment Income)
- Schedule SE (Self-Employment Tax)
- 1099 (If applicable)

### 4. **Compliance Reporting**
- Deadline tracking
- File status
- Payment confirmations
- Archive

---

## 📅 Tax Calendar

```
Q1 - Jan, Feb, Mar
├── Filing Deadline: April 15
├── Estimated Payment Due: April 15
└── Related Quarters: Q1

Q2 - Apr, May, Jun
├── Filing Deadline: June 15
├── Estimated Payment Due: June 15
└── Related Quarters: Q1+Q2 YTD

Q3 - Jul, Aug, Sep
├── Filing Deadline: Sep 15
├── Estimated Payment Due: Sep 15
└── Related Quarters: Q1+Q2+Q3 YTD

Q4/Annual - Oct, Nov, Dec
├── Filing Deadline: Jan 31 (next year)
├── Estimated Payment Due: Jan 15 (next year)
├── Annual Tax Return: April 15
└── Related Quarters: Full year
```

---

## 🔄 Tax Prep Process Flow

```
Quarter Starts
    │
    ▼
┌─────────────────────────────────┐
│ PHASE 1: Data Collection        │
├─────────────────────────────────┤
│ • Export all invoices           │
│ • Export all expenses           │
│ • Gather receipts               │
│ • Collect deduction docs        │
└─────────────┬───────────────────┘
              │
              ▼
┌─────────────────────────────────┐
│ PHASE 2: Categorization         │
├─────────────────────────────────┤
│ • Classify revenue sources      │
│ • Categorize expenses           │
│ • Verify supporting docs        │
│ • Flag items needing review     │
└─────────────┬───────────────────┘
              │
              ▼
┌─────────────────────────────────┐
│ PHASE 3: Calculation            │
├─────────────────────────────────┤
│ • Calculate taxable income      │
│ • Calculate deductions          │
│ • Calculate self-employment tax │
│ • Calculate tax liability       │
│ • Determine estimated payment   │
└─────────────┬───────────────────┘
              │
              ▼
┌─────────────────────────────────┐
│ PHASE 4: Review & Verification  │
├─────────────────────────────────┤
│ • CPA/Accountant review         │
│ • Verify calculations           │
│ • Check for red flags           │
│ • Approve for filing            │
└─────────────┬───────────────────┘
              │
              ▼
┌─────────────────────────────────┐
│ PHASE 5: Filing & Payment       │
├─────────────────────────────────┤
│ • File forms                    │
│ • Submit payments               │
│ • Confirm receipt               │
│ • Archive all docs              │
└─────────────────────────────────┘
```

---

## 💰 Tax Calculations

### Quarterly Estimated Tax Formula
```
Taxable Income = Revenue - Deductions
Tax Rate = Marginal tax rate for year
Estimated Tax = Taxable Income × Tax Rate

Safe Harbor Rule:
Option 1: 90% of current year tax
Option 2: 100% of prior year tax (110% if prior year > $150k)
```

### Self-Employment Tax
```
SE Income = Net Profit × 92.35%
SE Tax = SE Income × 15.3% (12.4% SS + 2.9% Medicare)
Deductible Amount = SE Tax × 50%
```

---

## 📊 Key Reports

### Report 1: Quarterly Summary
```
QUARTERLY TAX SUMMARY - Q2 2026
=====================================
Gross Revenue:          $50,000
Less: Deductions        -$12,000
Net Business Income:    $38,000

Self-Employment Tax:    $5,356
Federal Income Tax:     $7,125
State Tax:              $1,900
Total Tax:              $14,381

Less: Payments Made     -$3,600
Tax Due/Refund:         $10,781
```

### Report 2: Deduction Summary
```
DEDUCTION BREAKDOWN - Q2 2026
=====================================
Supplies:               $1,200
Equipment:              $800
Software:               $450
Professional Services:  $2,000
Home Office:            $300
Mileage (500 mi @ 67¢): $335
Travel:                 $1,500
Other:                  $4,415
=====================================
Total Deductions:       $11,000
```

### Report 3: Aging Report
```
DOCUMENTATION STATUS
=====================================
Invoices: 47 docs ✓ Complete
Receipts: 45/47 docs (2 missing)
Mileage: 4 trips logged
Deductions: 1 item needs verification
Status: 95% READY FOR FILING
```

---

## 📱 Component Structure

```
TaxPrepComponent
├── Properties
│   ├── $year
│   ├── $quarter
│   ├── $status (collecting, categorizing, calculating, reviewing, filed)
│   ├── $invoices = []
│   ├── $expenses = []
│   ├── $deductions = []
│   └── $calculations = {}
├── Methods
│   ├── collectData()
│   ├── categorizeInvoices()
│   ├── categorizeExpenses()
│   ├── calculateTaxes()
│   ├── generateReports()
│   ├── exportForCPA()
│   └── markAsFiled()
└── Views
    ├── tax-prep-wizard.blade.php
    ├── document-checklist.blade.php
    ├── calculation-detail.blade.php
    ├── reports-view.blade.php
    └── filing-status.blade.php
```

---

## 🔗 Integration Points

### QuickBooks
- Export trial balance
- Export P&L
- Get depreciation schedule
- Verify GL balances

### Email
- Send tax deadline reminders
- Send prepared forms to CPA
- Receive CPA feedback
- Send payment confirmations

### IRS
- E-file forms (if applicable)
- Track filing status
- Download confirmations

### Bank
- Get 1099-INT (Interest)
- Verify deposits for 1099-NEC

---

## 📋 Checklist

### Before Q Filing
- [ ] All invoices recorded
- [ ] All expenses documented
- [ ] All receipts attached
- [ ] Deductions verified
- [ ] Calculations reviewed
- [ ] CPA approval obtained

### Filing Day
- [ ] Forms completed
- [ ] Payment processed
- [ ] Confirmation received
- [ ] Archive completed

### After Filing
- [ ] Store backup copies
- [ ] Update estimated payment schedule
- [ ] Plan for next quarter
- [ ] Review for next year optimization

---

## 🎯 Success Criteria

- ✅ All documents collected 5 days before deadline
- ✅ CPA review completed 3 days before deadline
- ✅ Filed on time with no errors
- ✅ Payments received confirmation
- ✅ Archive complete and verified
- ✅ Zero missed deadlines

---

**Created:** May 25, 2026  
**Status:** Documentation Complete
