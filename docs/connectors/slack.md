# 💬 Slack Integration

**Mục đích:** Gửi notifications, alerts, và reports tới Slack channels để team có cập nhật realtime.

---

## 🎯 Chức Năng

- Send alerts & notifications
- Daily/weekly reports
- Invoice reminders & status
- Team notifications
- Error/exception alerts
- Interactive messages (buttons, reactions)

---

## 🔑 Setup & Authentication

### Step 1: Create Slack App
```
1. Go to Slack API portal
2. Create new app: "XLAP Tech Bot"
3. Enable permissions:
   - chat:write
   - chat:read
   - files:write
   - reactions:read
4. Get Bot Token
```

### Step 2: Environment Setup
```env
SLACK_BOT_TOKEN=xoxb-xxx
SLACK_CHANNEL_ALERTS=#business-alerts
SLACK_CHANNEL_INVOICES=#invoices
SLACK_CHANNEL_TEAM=#team-updates
```

### Step 3: Add Bot to Channels
```
1. Go to each channel in Slack
2. Click settings → Add app
3. Add XLAP Tech Bot
4. Bot can now post to these channels
```

---

## 📢 Notification Types

### Alert 1: Invoice Created
```
🧾 NEW INVOICE
━━━━━━━━━━━━━━━
Invoice #1001
Client: Acme Corp
Amount: $5,000
Due: June 15, 2026
Status: Draft

[Send Invoice] [View Details]
```

### Alert 2: Invoice Sent
```
📤 INVOICE SENT
━━━━━━━━━━━━━━━
Invoice #1001
Client: Acme Corp
Amount: $5,000
Sent: 2 minutes ago

✓ Tracking enabled
[View]
```

### Alert 3: Payment Received
```
💰 PAYMENT RECEIVED
━━━━━━━━━━━━━━━━
Invoice #1001: PAID ✓
Amount: $5,000
Received: Just now
Balance due: $0

[View Invoice]
```

### Alert 4: Invoice Overdue
```
⚠️  INVOICE OVERDUE
━━━━━━━━━━━━━━━━━
Invoice #1001
Client: Acme Corp
Amount: $5,000
Overdue by: 5 days

ACTION NEEDED:
[Send Reminder] [Call Client] [View]
```

### Daily Report
```
📊 DAILY BUSINESS SUMMARY
━━━━━━━━━━━━━━━━━━━━━━
Today: June 10, 2026

Revenue: $12,500
├─ Paid: $7,000
├─ Pending: $5,500
└─ Overdue: $0

Invoices: 3 sent, 1 paid
Team: 5 active, 0 away

Next Deadline: Jun 15 (Invoice #1001 due)

[View Dashboard]
```

---

## 💻 Implementation

### Action: SendSlackNotificationAction.php
```php
<?php

namespace App\Actions\Slack;

use Slack\BlockKit\Blocks\Block;
use Slack\BlockKit\Blocks\Section;
use Slack\BlockKit\Kit;
use Slack\BlockKit\Objects\Text;
use Slack\Client;

/**
 * Gửi notifications tới Slack
 */
class SendSlackNotificationAction
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client(['token' => config('slack.bot_token')]);
    }

    /**
     * Gửi đơn giản text notification
     * 
     * @param string $channel Channel name (with #)
     * @param string $message Message text
     * @param string $type Type (info, warning, error, success)
     * @return bool
     */
    public function sendSimple(
        string $channel,
        string $message,
        string $type = 'info'
    ): bool {
        $icon = match ($type) {
            'warning' => '⚠️',
            'error' => '❌',
            'success' => '✓',
            default => 'ℹ️'
        };

        return (bool) $this->client->post(
            'chat.postMessage',
            [
                'channel' => $channel,
                'text' => "{$icon} {$message}",
                'mrkdwn' => true,
            ]
        );
    }

    /**
     * Gửi rich formatted message
     * 
     * @param string $channel
     * @param array $blocks Block array
     * @return bool
     */
    public function sendRich(string $channel, array $blocks): bool
    {
        return (bool) $this->client->post(
            'chat.postMessage',
            [
                'channel' => $channel,
                'blocks' => json_encode($blocks),
            ]
        );
    }

    /**
     * Build invoice notification blocks
     * 
     * @param Invoice $invoice
     * @return array
     */
    public static function buildInvoiceBlocks($invoice): array
    {
        return [
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => "*💰 INVOICE PAID*\nInvoice #{$invoice->number}\nAmount: ${$invoice->amount}"
                ]
            ],
            [
                'type' => 'section',
                'fields' => [
                    [
                        'type' => 'mrkdwn',
                        'text' => "*Client*\n{$invoice->client->name}"
                    ],
                    [
                        'type' => 'mrkdwn',
                        'text' => "*Status*\n✓ PAID"
                    ]
                ]
            ],
            [
                'type' => 'actions',
                'elements' => [
                    [
                        'type' => 'button',
                        'text' => ['type' => 'plain_text', 'text' => 'View Invoice'],
                        'url' => route('invoices.show', $invoice->id),
                        'style' => 'primary'
                    ]
                ]
            ]
        ];
    }
}
```

### Livewire Component Usage
```php
public function markAsPaid($invoiceId)
{
    $invoice = Invoice::findOrFail($invoiceId);
    $invoice->update(['status' => 'paid', 'paid_at' => now()]);
    
    // Send Slack notification
    $action = new SendSlackNotificationAction();
    $action->sendRich(
        config('slack.channel_alerts'),
        SendSlackNotificationAction::buildInvoiceBlocks($invoice)
    );
    
    $this->success('Invoice marked as paid');
}
```

---

## 📅 Scheduled Reports

### Daily Summary (9 AM)
```php
Schedule::call(new SendDailySummaryAction())
    ->dailyAt('09:00')
    ->timezone('UTC');
```

### Weekly Report (Monday 9 AM)
```php
Schedule::call(new SendWeeklyReportAction())
    ->weeklyOn(1, '09:00')
    ->timezone('UTC');
```

### Overdue Alert (Daily 8 AM)
```php
Schedule::call(new SendOverdueInvoicesAlert())
    ->dailyAt('08:00')
    ->timezone('UTC');
```

---

## 🔗 Channel Organization

```
#business-alerts
├── Invoice sent
├── Payment received
├── Overdue alerts
└── Critical errors

#invoices
├── All invoice updates
├── Status changes
└── Reminders sent

#team-updates
├── Team performance
├── Attendance
└── Announcements

#daily-summary
└── Daily report at 9 AM
```

---

## 🔘 Interactive Messages

### Message with Buttons
```php
$blocks = [
    [
        'type' => 'section',
        'text' => [
            'type' => 'mrkdwn',
            'text' => '⚠️ Invoice #1001 is OVERDUE'
        ]
    ],
    [
        'type' => 'actions',
        'elements' => [
            [
                'type' => 'button',
                'text' => ['type' => 'plain_text', 'text' => 'Send Reminder'],
                'value' => '1001_reminder',
                'action_id' => 'invoice_action'
            ],
            [
                'type' => 'button',
                'text' => ['type' => 'plain_text', 'text' => 'Mark as Paid'],
                'value' => '1001_paid',
                'action_id' => 'invoice_action',
                'style' => 'primary'
            ]
        ]
    ]
];

$action->sendRich(config('slack.channel_alerts'), $blocks);
```

---

## ⚠️ Error Handling

### Retry Strategy
```
Attempt 1: Immediate
Attempt 2: After 5 seconds
Attempt 3: After 1 minute
Attempt 4: After 5 minutes → Log error but don't retry
```

### Fallback
```
If Slack fails:
├── Log to Laravel logs
├── Send email to admin (fallback)
└── Don't fail the main operation
```

---

## 🧪 Testing

```php
// Manual test
php artisan tinker
> $action = new SendSlackNotificationAction();
> $action->sendSimple('#test', 'Hello from XLAP', 'success');

// Check Slack
// Message should appear in #test channel
```

---

## 🔒 Security

- ✅ Bot token stored in .env (not in code)
- ✅ Only posts to authorized channels
- ✅ No sensitive data in messages
- ✅ IP whitelist enabled (if available)
- ✅ Audit log of all posts

---

**Rate Limiting:** 1 message/second per channel  
**Maximum Channels:** 10 (can increase)  
**File Upload:** Up to 10MB  
**Message Retention:** Slack default (usually 90 days free)
