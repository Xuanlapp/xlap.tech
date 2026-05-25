# 💌 Invoice Chase - Theo Dõi & Thu Hồi Hoá Đơn

**Mục đích:** Hệ thống tự động theo dõi, gửi reminder, và follow-up hoá đơn chưa thanh toán.

---

## 🎯 Chức Năng Chính

### 1. **Automatic Invoice Tracking**
- Tạo hoá đơn → Send via email → Track status
- Detect khi client mở email (pixel tracking)
- Track khi client click link trong email

### 2. **Smart Reminders**
```
Invoice Created
    ↓ (Day 1)
Send Initial Invoice + Link
    ↓ (Day 7 - No Payment)
Gentle Reminder: "Just checking in..."
    ↓ (Day 14 - No Payment)
Follow-up: "Still waiting for payment"
    ↓ (Day 21 - No Payment)
Urgent: "Invoice now overdue"
    ↓ (Day 30+ - Overdue)
Critical Alert: "OVERDUE - Immediate action needed"
```

### 3. **Payment Tracking**
- Automatic status update khi payment diterima
- Reconciliation dengan bank/payment gateway
- Send thank you email khi paid

### 4. **Escalation Rules**
```
Days Overdue | Action
0-7         | Auto reminder email
7-14        | Follow-up email + Slack alert
14-30       | Phone call (if phone available)
30+         | Management review + escalation
```

---

## 🔄 Invoice Status Flow

```
┌─────────────┐
│   DRAFT     │ ← User creating invoice
└──────┬──────┘
       │ (User clicks Send)
       ▼
┌─────────────┐
│    SENT     │ ← Email sent to client
└──────┬──────┘
       │ (Client opens/clicks)
       ▼
┌─────────────┐
│   VIEWED    │ ← Tracked when client opens
└──────┬──────┘
       │ (Payment received)
       ▼
┌─────────────┐
│    PAID     │ ← Transaction complete
└─────────────┘

Alternative paths:
DRAFT → (deleted/voided) → CANCELLED
SENT → (payment received) → PAID
SENT → (never paid) → OVERDUE
```

---

## 📊 Data Points Tracked

```
Per Invoice:
├── invoice_id
├── status (draft, sent, viewed, paid, overdue, cancelled)
├── client_id
├── amount
├── sent_at
├── first_viewed_at
├── payment_received_at
├── due_date
├── last_reminder_sent_at
├── reminder_count
└── notes

Per Tracking Event:
├── event_type (sent, viewed, clicked, paid, reminder_sent)
├── timestamp
├── ip_address
├── email_id
├── device_info
└── location
```

---

## 🤖 Automation Rules

### Rule 1: Auto Reminder on Day 7
```php
if (invoice.status == 'sent' 
    && days_since_sent >= 7 
    && no_payment_received) {
    send_reminder_email(invoice);
    update_invoice(reminder_count++);
}
```

### Rule 2: Escalate to Overdue on Due Date
```php
if (today > invoice.due_date 
    && invoice.status != 'paid') {
    update_invoice(status = 'overdue');
    notify_slack('#business-alerts', 
        "Invoice {id} is OVERDUE");
    alert_team_member();
}
```

### Rule 3: Auto-reconcile with Payment
```php
if (payment_received_for(invoice_id)) {
    update_invoice(status = 'paid', paid_at = now());
    send_thank_you_email(client);
    update_business_metrics();
    log_activity('Invoice paid');
}
```

---

## 📱 Component Structure

```
InvoiceChaseComponent
├── Properties
│   ├── $invoices = []
│   ├── $filter = "all" (all, pending, overdue, paid)
│   ├── $sortBy = "due_date"
│   └── $searchQuery = ""
├── Methods
│   ├── loadInvoices()
│   ├── sendReminder(invoiceId)
│   ├── markAsPaid(invoiceId)
│   ├── followUp(invoiceId)
│   └── filter()
└── Views
    ├── invoice-list.blade.php
    ├── invoice-detail.blade.php
    ├── send-reminder-modal.blade.php
    └── tracking-timeline.blade.php
```

---

## 🔗 Integration Points

### Gmail Integration
- Send invoices via Gmail
- Include tracking pixel
- Track open/clicks
- See in Gmail sent folder

### Slack Integration
- Alert on overdue invoices
- Notify when payment received
- Daily summary report
- Critical alerts to channel

### QuickBooks Integration
- Sync invoice data
- Get payment notifications
- Update invoice status
- Export for reconciliation

### Banking API
- Detect incoming payments
- Auto-update invoice status
- Reconcile amounts
- Flag discrepancies

---

## 📈 Reporting

### Dashboard Shows:
- Total invoices (this month, MTD)
- Revenue pending
- Average days to payment
- Overdue invoices count
- Collection rate %
- Trending

### Reports Available:
- Aging Report (30/60/90+ days)
- Client Payment History
- Reminder Effectiveness
- Revenue Forecast

---

## 🚀 Implementation Checklist

- [ ] Create InvoiceModel with all fields
- [ ] Create InvoiceTrackingModel for events
- [ ] Create SendInvoiceAction
- [ ] Create SendReminderAction
- [ ] Create EmailTemplates (invoice, reminder, thank you)
- [ ] Setup Gmail integration
- [ ] Setup Slack integration
- [ ] Create scheduled jobs (reminders, escalation)
- [ ] Create InvoiceChase Livewire component
- [ ] Create tests

---

## 💾 Database Schema

```sql
CREATE TABLE invoices (
    id INT PRIMARY KEY,
    client_id INT,
    invoice_number VARCHAR(50),
    amount DECIMAL(10,2),
    status ENUM('draft','sent','viewed','paid','overdue','cancelled'),
    sent_at TIMESTAMP NULL,
    first_viewed_at TIMESTAMP NULL,
    due_date DATE,
    payment_received_at TIMESTAMP NULL,
    last_reminder_sent_at TIMESTAMP NULL,
    reminder_count INT DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id)
);

CREATE TABLE invoice_tracking (
    id INT PRIMARY KEY,
    invoice_id INT,
    event_type VARCHAR(50),
    event_data JSON,
    created_at TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id)
);
```

---

## 🎯 Success Metrics

- ✅ Average payment time reduced by 30%
- ✅ Overdue invoices < 5% of total
- ✅ Reminder open rate > 60%
- ✅ System responds to reminders within 1 minute
- ✅ 99.9% email delivery rate

---

**Created:** May 25, 2026  
**Status:** Documentation Complete
