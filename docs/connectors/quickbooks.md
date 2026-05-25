# 📊 QuickBooks Integration

**Mục đích:** Đồng bộ dữ liệu tài chính giữa hệ thống XLAP và QuickBooks Online.

---

## 🎯 Chức Năng

- Sync invoices → QBO
- Sync expenses → QBO
- Import transactions từ QBO
- Generate reports từ QBO data
- Reconciliation support
- Tax document export

---

## 🔑 Setup & Authentication

### Step 1: Create QBO App
```
1. Go to QuickBooks Developer Portal
2. Create new app: "XLAP Tech"
3. Get Client ID & Client Secret
4. Set Redirect URI: https://xlap-tech.com/callback/quickbooks
```

### Step 2: OAuth Setup
```env
QUICKBOOKS_CLIENT_ID=xxx
QUICKBOOKS_CLIENT_SECRET=xxx
QUICKBOOKS_REALM_ID=xxx (Company ID)
QUICKBOOKS_REDIRECT_URI=https://xlap-tech.com/callback/quickbooks
```

### Step 3: Authorize
```
1. User clicks "Connect to QuickBooks"
2. Redirected to QB login
3. User authorizes XLAP app
4. Store access token + refresh token
5. Ready to sync!
```

---

## 🔄 Data Sync Flow

### Outbound Sync (XLAP → QB)
```
Invoice Created in XLAP
    │
    ├─ Queue job: SyncInvoiceToQB
    │
    ▼
Job Executes (Async)
    │
    ├─ Prepare invoice data
    ├─ Add QB account mappings
    ├─ Validate amounts
    │
    ▼
Send to QB API
    │
    ├─ Create invoice in QB
    ├─ Get QB invoice ID
    ├─ Store mapping (XLAP ID ↔ QB ID)
    │
    ▼
Log Success
    │
    └─ Mark as synced in XLAP
```

### Inbound Sync (QB → XLAP)
```
QB Transaction Webhook
    │
    ├─ Payment received in QB
    │
    ▼
Process Webhook
    │
    ├─ Extract transaction data
    ├─ Find matching XLAP invoice
    ├─ Verify amount & date
    │
    ▼
Update XLAP
    │
    ├─ Mark invoice as paid
    ├─ Log transaction
    ├─ Update business metrics
    │
    └─ Send notification
```

---

## 💻 Implementation

### Action: SyncInvoiceToQBAction.php
```php
<?php

namespace App\Actions\QuickBooks;

use App\Models\Invoice;
use QuickBooksOnline\API\Core\ServiceContext;
use QuickBooksOnline\API\DataService\DataService;

/**
 * Đồng bộ hoá đơn tới QuickBooks
 */
class SyncInvoiceToQBAction
{
    private DataService $dataService;

    /**
     * Execute sync
     * 
     * @param Invoice $invoice
     * @return array Result with QB invoice ID
     * @throws Exception
     */
    public function execute(Invoice $invoice): array
    {
        $this->dataService = $this->getDataService();
        
        if (!$invoice->shouldSync()) {
            return ['synced' => false, 'reason' => 'Not eligible'];
        }

        try {
            $qbInvoice = $this->createQBInvoice($invoice);
            $result = $this->dataService->add($qbInvoice);

            if ($result) {
                $invoice->update([
                    'qb_id' => $result->Id,
                    'qb_synced_at' => now()
                ]);
                
                return ['synced' => true, 'qb_id' => $result->Id];
            }
        } catch (Exception $e) {
            \Log::error('QB sync failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function createQBInvoice(Invoice $invoice): \QuickBooksOnline\API\Entities\Invoice
    {
        $qbInvoice = new \QuickBooksOnline\API\Entities\Invoice();
        
        $qbInvoice->setDocNumber($invoice->number);
        $qbInvoice->setTxnDate($invoice->created_at->format('Y-m-d'));
        $qbInvoice->setDueDate($invoice->due_date->format('Y-m-d'));
        
        // Set customer
        $qbInvoice->setCustomerReference($this->getQBCustomer($invoice->client));
        
        // Add line items
        foreach ($invoice->items as $item) {
            $lineItem = new \QuickBooksOnline\API\Entities\Line();
            $lineItem->setDetailType('SalesItemLineDetail');
            $lineItem->setDescription($item->description);
            $lineItem->setAmount($item->amount);
            
            $qbInvoice->addLine($lineItem);
        }
        
        // Set totals
        $qbInvoice->setTotalAmt($invoice->total);
        
        return $qbInvoice;
    }

    private function getQBCustomer($client)
    {
        // Get or create QB customer
        if ($client->qb_id) {
            return $this->dataService->findbyId('Customer', $client->qb_id);
        }
        
        // Create new QB customer
        $qbCustomer = new \QuickBooksOnline\API\Entities\Customer();
        $qbCustomer->setDisplayName($client->name);
        // ... add more details
        
        $result = $this->dataService->add($qbCustomer);
        $client->update(['qb_id' => $result->Id]);
        
        return $result;
    }

    private function getDataService(): DataService
    {
        $realmId = config('quickbooks.realm_id');
        $accessToken = auth()->user()->quickbooks_access_token;
        
        $serviceContext = new ServiceContext(
            config('quickbooks.client_id'),
            config('quickbooks.client_secret'),
            $accessToken,
            $realmId
        );
        
        return DataService::Configure($serviceContext);
    }
}
```

---

## 📋 Account Mappings

```
XLAP Category → QB Account
├── Revenue → 1000 (Income from Services)
├── Salary → 6100 (Salaries & Wages)
├── Supplies → 6200 (Office Supplies)
├── Equipment → 1500 (Fixed Assets)
├── Travel → 6300 (Travel & Meals)
└── Other → 6900 (Miscellaneous)
```

---

## 🔗 Data Relationships

```
QB Customer ←→ XLAP Client
├── qb_id stored in client
├── Synced bidirectionally
└── Manual override possible

QB Invoice ←→ XLAP Invoice
├── qb_id stored in invoice
├── One-way sync (XLAP → QB)
└── Marked as synced after

QB Account ←→ XLAP Categories
├── Stored in config
├── Used for categorization
└── Updated quarterly
```

---

## ⚠️ Error Handling

### Retry Strategy
```
Attempt 1: Immediate
Attempt 2: After 5 minutes
Attempt 3: After 1 hour
Attempt 4: After 24 hours → Manual review
```

### Common Errors
```
Error: Customer not found
→ Solution: Sync customer first

Error: Invalid account
→ Solution: Check account mappings

Error: Duplicate invoice
→ Solution: Check QB invoice list

Error: Auth expired
→ Solution: Refresh OAuth token
```

---

## 🧪 Testing

```php
// Manual test
php artisan tinker
> $invoice = Invoice::find(1);
> $action = new SyncInvoiceToQBAction();
> $action->execute($invoice);

// Check QB
// Login to QuickBooks and verify invoice appears
```

---

## 🔒 Security

- ✅ OAuth tokens stored securely
- ✅ Refresh tokens rotated automatically
- ✅ API calls over HTTPS only
- ✅ Rate limiting: 120 requests/minute
- ✅ Log all sync activities
- ✅ Audit trail maintained

---

## 📊 Reports from QB

### Generate P&L
```php
$report = new GetQBProfitLossAction();
$pnl = $report->execute($startDate, $endDate);
```

### Generate Balance Sheet
```php
$report = new GetQBBalanceSheetAction();
$bs = $report->execute($asOfDate);
```

---

**Sync Frequency:** Real-time on invoice save  
**Batch Processing:** Nightly reconciliation at 2 AM  
**Webhook Support:** QB → XLAP for payment notifications
