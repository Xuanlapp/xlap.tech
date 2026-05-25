# 📧 Gmail Integration

**Mục đích:** Gửi emails (invoices, reminders, notifications) qua Gmail API một cách an toàn và hiệu quả.

---

## 🎯 Chức Năng

- Gửi invoices tới clients
- Gửi reminders cho unpaid invoices
- Gửi notifications cho team
- Track email opens & clicks (pixel tracking)
- Template-based emails
- Attachment support (PDFs, etc.)

---

## 🔑 Setup & Authentication

### Step 1: Create Google Service Account
```
1. Go to Google Cloud Console
2. Create new project: "XLAP Tech"
3. Enable Gmail API
4. Create Service Account
5. Download JSON key file
6. Save to: /config/gmail-service-account.json
```

### Step 2: Environment Setup
```env
# .env
GMAIL_SERVICE_ACCOUNT_JSON=/path/to/service-account.json
GMAIL_FROM_EMAIL=noreply@xlap-tech.com
GMAIL_FROM_NAME="XLAP Tech"
```

### Step 3: Grant Gmail Permissions
```
1. In service account settings, add:
   - Gmail scope: https://mail.google.com/
2. In Gmail settings, add service account as delegate
```

---

## 📧 Email Templates

### Template 1: Invoice Email
```
Subject: Invoice #{invoice_number} - {amount} due on {due_date}

Body:
Hi {client_name},

Please find attached your invoice for {amount}.
Invoice #: {invoice_number}
Due Date: {due_date}

[Open Invoice in Browser] (link with tracking pixel)

Thank you for your business!

Best regards,
{business_name}
```

### Template 2: Reminder Email
```
Subject: Reminder: Invoice #{invoice_number} is due

Body:
Hi {client_name},

Just checking in on invoice {invoice_number} for {amount}.
It's due on {due_date}.

If you've already paid, please disregard this email.
If you have questions, reply to this email.

[View Invoice]
[Pay Now]

Thanks!
```

### Template 3: Notification Email
```
Subject: {event}: {description}

Body:
{notification_body}

{action_buttons}

Details:
- Date: {date}
- Amount: {amount}
- Status: {status}
```

---

## 🔄 Usage Flow

```
App Code
    │
    ├─ Create EmailData (recipient, template, variables)
    │
    ▼
SendEmailAction
    │
    ├─ Validate email data
    ├─ Render template with variables
    ├─ Add tracking pixel (if needed)
    ├─ Send via Gmail API
    │
    ▼
Gmail API
    │
    ├─ Authenticate with service account
    ├─ Create MIME message
    ├─ Send message
    │
    ▼
Gmail Sends Email
    │
    ├─ Track open (pixel)
    ├─ Track clicks (link)
    │
    ▼
Log Activity
    │
    └─ Update invoice status
    └─ Store tracking data
```

---

## 💻 Implementation

### Action: SendEmailAction.php
```php
<?php

namespace App\Actions\Email;

use Google\Client;
use Google\Service\Gmail;
use Swift_Message;

/**
 * Gửi email qua Gmail API
 */
class SendEmailAction
{
    /**
     * Gửi email
     * 
     * @param string $to Email recipient
     * @param string $subject Email subject
     * @param string $body Email body (HTML)
     * @param array $attachments Optional file paths
     * @return bool Success
     * @throws Exception
     */
    public function execute(
        string $to,
        string $subject,
        string $body,
        array $attachments = []
    ): bool {
        try {
            $client = $this->authenticateGmail();
            $gmail = new Gmail($client);
            
            $message = $this->createMessage($to, $subject, $body, $attachments);
            $result = $gmail->users_messages->send('me', $message);
            
            return (bool) $result->getId();
        } catch (Exception $e) {
            \Log::error('Gmail send failed', [
                'to' => $to,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function authenticateGmail(): Client
    {
        $client = new Client();
        $client->setAuthConfig(config('gmail.service_account_json'));
        $client->addScope(Gmail::GMAIL_SEND);
        return $client;
    }

    private function createMessage(
        $to,
        $subject,
        $body,
        $attachments
    ): \Google\Service\Gmail\Message {
        // Create MIME message
        $message = new Swift_Message();
        $message->setTo($to);
        $message->setSubject($subject);
        $message->setBody($body, 'text/html');
        
        // Add attachments
        foreach ($attachments as $file) {
            $message->attach(Swift_Attachment::fromPath($file));
        }
        
        // Convert to Gmail API format
        $encoded = base64_encode($message->toString());
        $msg = new \Google\Service\Gmail\Message();
        $msg->setRaw($encoded);
        
        return $msg;
    }
}
```

### Livewire Component Usage
```php
public function sendInvoice($invoiceId)
{
    $invoice = Invoice::findOrFail($invoiceId);
    
    try {
        $action = new SendEmailAction();
        $action->execute(
            to: $invoice->client->email,
            subject: "Invoice #{$invoice->number}",
            body: view('emails.invoice', ['invoice' => $invoice])->render(),
            attachments: [$invoice->getPdfPath()]
        );
        
        $invoice->update(['status' => 'sent', 'sent_at' => now()]);
        $this->notify('Invoice sent successfully');
    } catch (Exception $e) {
        $this->error('Failed to send invoice: ' . $e->getMessage());
    }
}
```

---

## 🔍 Email Tracking

### Tracking Pixel
```html
<!-- Add to email body -->
<img src="https://tracking.xlap-tech.com/track/email/{tracking_id}/open" 
     width="1" height="1" alt="" style="display:none;">
```

### Link Tracking
```
Original: https://invoices.xlap-tech.com/view/123
Tracked:  https://tracking.xlap-tech.com/track/email/{tracking_id}/click?redirect_to=https://invoices.xlap-tech.com/view/123
```

### Tracking Data Stored
```
email_tracking_events
├── id
├── invoice_id
├── event_type (sent, opened, clicked, bounced)
├── timestamp
├── user_agent
├── ip_address
└── metadata (JSON)
```

---

## ⚠️ Rate Limiting & Quotas

- **Daily Limit:** 1,000 emails per account
- **Concurrent Connections:** Max 10
- **Batch Size:** Send in batches of 100 max
- **Retry Strategy:** Exponential backoff

---

## 🧪 Testing

```php
// Test sending
php artisan tinker
> $action = new SendEmailAction();
> $action->execute('test@example.com', 'Test', 'Body');

// Check logs
tail -f storage/logs/laravel.log

// Verify in Gmail
// Check Gmail sent folder for "XLAP Tech" sender
```

---

## 🔒 Security Considerations

- ✅ Use service account (not personal account)
- ✅ Rotate keys every 90 days
- ✅ Store JSON key securely (.env, not in code)
- ✅ Use TLS/SSL for all connections
- ✅ Log all email sends
- ✅ Monitor for bounces/complaints

---

**Configuration:** Async via Laravel Queue  
**Error Handling:** Retry up to 3 times before giving up  
**Fallback:** Log and notify admin if persistent failure
