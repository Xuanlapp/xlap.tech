# 🤝 HubSpot CRM Integration

**Mục đích:** Đồng bộ dữ liệu khách hàng, leads, và contacts giữa XLAP và HubSpot CRM.

---

## 🎯 Chức Năng

- Sync clients → HubSpot contacts
- Sync leads → HubSpot deals
- Track interactions (emails, calls)
- Update contact properties
- Create tasks & follow-ups
- Generate reports

---

## 🔑 Setup & Authentication

### Step 1: Create HubSpot App
```
1. Go to HubSpot Developer Portal
2. Create new app: "XLAP Tech"
3. Select scopes:
   - crm.objects.contacts.read
   - crm.objects.contacts.write
   - crm.objects.deals.read
   - crm.objects.deals.write
   - crm.lists.read
4. Get API Key
```

### Step 2: Environment Setup
```env
HUBSPOT_API_KEY=xxx
HUBSPOT_PORTAL_ID=xxx
```

---

## 🔄 Data Sync Flow

### Contact Sync (XLAP → HubSpot)
```
New Client in XLAP
    │
    ├─ Queue job: SyncClientToHubSpot
    │
    ▼
Prepare Contact Data
    │
    ├─ First Name
    ├─ Last Name
    ├─ Email
    ├─ Phone
    ├─ Company
    ├─ Custom Fields
    │
    ▼
Send to HubSpot API
    │
    ├─ Check if exists (by email)
    ├─ Create or update contact
    ├─ Get HubSpot contact ID
    ├─ Store mapping (XLAP ID ↔ HS ID)
    │
    └─ Mark as synced
```

---

## 💻 Implementation

### Action: SyncClientToHubSpotAction.php
```php
<?php

namespace App\Actions\HubSpot;

use HubSpot\Factory;
use HubSpot\Client\Crm\Contacts\Model\SimplePublicObjectInput;

/**
 * Đồng bộ client tới HubSpot
 */
class SyncClientToHubSpotAction
{
    private $client;

    public function __construct()
    {
        $this->client = Factory::createClient([
            'accessToken' => config('hubspot.api_key')
        ]);
    }

    /**
     * Sync client to HubSpot
     * 
     * @param \App\Models\Client $client
     * @return array Result with HS contact ID
     * @throws Exception
     */
    public function execute($client): array
    {
        try {
            // Check if exists
            $existing = $this->findExistingContact($client->email);
            
            if ($existing) {
                $result = $this->updateContact($existing, $client);
                $isNew = false;
            } else {
                $result = $this->createContact($client);
                $isNew = true;
            }

            if ($result) {
                $client->update([
                    'hubspot_id' => $result->getId(),
                    'hubspot_synced_at' => now()
                ]);
                
                return [
                    'synced' => true,
                    'hs_id' => $result->getId(),
                    'is_new' => $isNew
                ];
            }
        } catch (Exception $e) {
            \Log::error('HubSpot sync failed', [
                'client_id' => $client->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function findExistingContact(string $email)
    {
        try {
            $response = $this->client->crm()->contacts()->basicApi()
                ->getById($email, null, null, 'email');
            return $response;
        } catch (Exception $e) {
            // Contact not found
            return null;
        }
    }

    private function createContact($client)
    {
        $properties = $this->buildProperties($client);
        
        $input = new SimplePublicObjectInput();
        $input->setProperties($properties);
        
        return $this->client->crm()->contacts()->basicApi()
            ->create($input);
    }

    private function updateContact($existing, $client)
    {
        $properties = $this->buildProperties($client);
        
        $input = new SimplePublicObjectInput();
        $input->setProperties($properties);
        
        return $this->client->crm()->contacts()->basicApi()
            ->update($existing->getId(), $input);
    }

    private function buildProperties($client): array
    {
        return [
            'firstname' => $client->first_name,
            'lastname' => $client->last_name,
            'email' => $client->email,
            'phone' => $client->phone,
            'company' => $client->company,
            'lifecyclestage' => 'customer',
            'hs_lead_status' => 'CUSTOMER',
        ];
    }
}
```

---

## 🏷️ Custom Properties

### Contact Properties
```
├── xlap_client_id (internal ID)
├── xlap_invoice_total (lifetime value)
├── xlap_last_invoice_date
├── xlap_status (active, inactive)
└── xlap_notes
```

### Deal Properties
```
├── xlap_invoice_id (linked invoice)
├── xlap_invoice_date
├── xlap_invoice_amount
├── xlap_payment_status
└── xlap_payment_date
```

---

## 📊 Reporting

### Sync Status Report
```
Total Clients: 150
├── Synced: 145 (96.7%)
├── Pending: 5
└── Failed: 0

Last Sync: 2 hours ago
Next Sync: In 4 hours
Errors: None
```

---

## 🔗 Activity Tracking

### Log Email Sent
```php
LogActivityAction::execute(
    contact_id: $client->hubspot_id,
    activity_type: 'email_sent',
    subject: 'Invoice #1001',
    timestamp: now()
);
```

### Log Payment
```php
LogActivityAction::execute(
    contact_id: $client->hubspot_id,
    activity_type: 'payment_received',
    amount: 5000,
    timestamp: now()
);
```

---

## ⚠️ Error Handling

### Retry Logic
```
Attempt 1: Immediate
Attempt 2: After 5 minutes
Attempt 3: After 1 hour
Attempt 4: After 24 hours → Manual review
```

### Common Errors
```
Error: Contact already exists
→ Update instead of create

Error: Invalid email
→ Skip sync for invalid emails

Error: Rate limit exceeded
→ Queue job with exponential backoff

Error: API key invalid
→ Alert admin immediately
```

---

## 🧪 Testing

```php
// Manual test
php artisan tinker
> $client = Client::find(1);
> $action = new SyncClientToHubSpotAction();
> $action->execute($client);

// Check HubSpot
// Login to HubSpot and verify contact appears
```

---

## 🔒 Security

- ✅ API key stored in .env (not in code)
- ✅ No PII in log files
- ✅ HTTPS for all requests
- ✅ Rate limiting: 100 requests/10 seconds
- ✅ Audit log maintained
- ✅ Token rotation yearly

---

**Sync Frequency:** Real-time on client create/update  
**Batch Processing:** Nightly sync at 2 AM  
**Activity Tracking:** Logged every interaction  
**Webhook Support:** HubSpot → XLAP for deal updates
