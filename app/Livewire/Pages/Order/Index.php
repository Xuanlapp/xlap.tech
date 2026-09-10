<?php

namespace App\Livewire\Pages\Order;

use App\Models\ProductDesignAsset;
use App\Models\Product;
use App\Models\SkuOrderItem;
use App\Models\User;
use App\Models\OrderProduct;
use App\Models\HistoryOrderReport;
use App\Services\Order\SkuOrderItemService;
use App\Services\Image\ImageLinkPreviewService;
use App\Services\Logging\ActivityLogService;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;

class Index extends Component
{
    use WithFileUploads, WithPagination;

    public int $perPage = 5;

    public string $search = '';

    public string $productFilter = '';

    public string $historyOrderSearch = '';

    /** @var array<int, int> */
    public array $selectedHistoryOrderIds = [];

    /** @var array<int, int> */
    public array $selectedSkuOrderItemIds = [];

    public bool $showManualFbaModal = false;

    public string $manualFbaPack = '3';

    public string $manualFbaSize = '3';

    public string $manualFbaQuantity = '1';

    /** @var array<int, array<string, string>> */
    public array $manualFbaPreviewRows = [];

    public bool $showImportModal = false;

    public bool $showOrderProductImportModal = false;

    public bool $showOrderReportImportModal = false;

    public bool $showEditOrderItemModal = false;

    public string $importUserId = '';

    public string $importProductId = '';

    public ?TemporaryUploadedFile $orderItemFile = null;

    public ?TemporaryUploadedFile $orderProductFile = null;

    public ?TemporaryUploadedFile $orderReportFile = null;

    public ?int $editingOrderItemId = null;

    public string $editingOrderItemProductId = '';

    public string $editingOrderItemImageLink = '';

    /** @var array<int, array{sku:string,image_link:string}> */
    public array $previewRows = [];

    /** @var array<int, array{source_id:string,fulfillment_type:string,order_product_id:string,product_name:string}> */
    public array $orderProductPreviewRows = [];

    /** @var array<int, array<string, string>> */
    public array $orderReportPreviewRows = [];

    /** @var array<int, string> */
    public array $orderReportHeaders = [];

    public string $orderReportFulfillment = 'FBM';

    public string $orderReportProductTag = '';

    public string $orderReportPack = '';

    public bool $orderReportHolo = false;

    /** @var array<int, array<string, string>> */
    public array $orderFulfillmentPreviewRows = [];

    public ?string $message = null;

    public ?string $error = null;

    public function toggleCurrentSkuPageSelection(array $ids): void
    {
        $ids = array_values(array_map('intval', $ids));
        $selected = array_map('intval', $this->selectedSkuOrderItemIds);
        $allSelected = $ids !== [] && count(array_intersect($ids, $selected)) === count($ids);
        $this->selectedSkuOrderItemIds = $allSelected
            ? array_values(array_diff($selected, $ids))
            : array_values(array_unique(array_merge($selected, $ids)));
    }

    public function openManualFbaModal(): void
    {
        if ($this->selectedSkuOrderItemIds === []) return;
        $items = SkuOrderItem::query()->whereIn('id', $this->selectedSkuOrderItemIds)
            ->when(! auth()->user()?->is_admin, fn ($query) => $query->where('user_id', auth()->id()))
            ->with('product')->get();
        $this->manualFbaPreviewRows = $items->map(fn (SkuOrderItem $item): array => [
            'sku' => $item->sku, 'product_name' => (string) ($item->product?->name ?? ''), 'link_design' => (string) $item->image_link,
            'quantity' => '1', 'product_id' => '', 'error' => '',
        ])->all();
        $this->manualFbaPack = '3';
        $this->manualFbaSize = '3';
        $this->manualFbaQuantity = '1';
        $this->buildManualFbaPreview();
        $this->showManualFbaModal = true;
    }

    public function closeManualFbaModal(): void
    {
        $this->showManualFbaModal = false;
        $this->reset(['manualFbaPack', 'manualFbaSize', 'manualFbaQuantity', 'manualFbaPreviewRows']);
    }

    public function updatedManualFbaPack(): void
    {
        $this->buildManualFbaPreview();
    }

    public function updatedManualFbaSize(): void
    {
        $this->buildManualFbaPreview();
    }

    public function updatedManualFbaQuantity(): void
    {
        $quantity = max(1, (int) $this->manualFbaQuantity);
        $this->manualFbaQuantity = (string) $quantity;
        foreach ($this->manualFbaPreviewRows as $index => $row) {
            $this->manualFbaPreviewRows[$index]['quantity'] = (string) $quantity;
        }
    }

    private function buildManualFbaPreview(): void
    {
        $catalog = OrderProduct::query()->where('fulfillment_type', 'FBA')->get();
        $pack = trim($this->manualFbaPack);
        $size = trim($this->manualFbaSize);
        foreach ($this->manualFbaPreviewRows as $index => $row) {
            $product = $catalog->first(function (OrderProduct $product) use ($row, $pack, $size): bool {
                $name = strtolower($product->product_name);
                return str_contains($name, strtolower($row['product_name']))
                    && $pack !== '' && str_contains($name, 'pack '.strtolower($pack))
                    && $size !== '' && (str_contains($name, strtolower($size).'in') || str_contains($name, strtolower($size).' in'));
            });
            $this->manualFbaPreviewRows[$index]['product_id'] = (string) ($product?->order_product_id ?? '');
            $this->manualFbaPreviewRows[$index]['error'] = $pack === '' || $size === '' ? 'Vui long nhap Size (in) va Pack.' : ($product ? '' : 'Khong tim thay Order Product FBA dung Product/Size/Pack.');
        }
    }

    public function exportManualFbaOrders(): mixed
    {
        $validRows = collect($this->manualFbaPreviewRows)->filter(fn (array $row): bool => $row['error'] === '');
        if ($validRows->isEmpty()) return null;
        app(ActivityLogService::class)->record('order.manual_fba_exported', 'Exported manually selected FBA SKU Order Items.', properties: ['count' => $validRows->count(), 'pack' => $this->manualFbaPack]);
        $html = '<table><thead><tr><th>Product ID</th><th>Quantity</th><th>Link Design</th></tr></thead><tbody>';
        foreach ($validRows as $row) $html .= '<tr><td>'.htmlspecialchars($row['product_id'], ENT_QUOTES, 'UTF-8').'</td><td>'.htmlspecialchars($row['quantity'], ENT_QUOTES, 'UTF-8').'</td><td>'.htmlspecialchars($row['link_design'], ENT_QUOTES, 'UTF-8').'</td></tr>';
        $html .= '</tbody></table>';
        return response()->streamDownload(fn () => print $html, 'manual-fba-orders-'.now()->format('Ymd-His').'.xls', ['Content-Type' => 'application/vnd.ms-excel']);
    }

    public function exportSelectedHistoryOrders(): mixed
    {
        abort_unless(auth()->check(), 403);
        $orders = HistoryOrderReport::query()->whereIn('id', $this->selectedHistoryOrderIds)
            ->when(! auth()->user()?->is_admin, fn ($query) => $query->where('user_id', auth()->id()))->get();
        if ($orders->isEmpty()) return null;
        app(ActivityLogService::class)->record('order.history_exported', 'Exported selected history orders.', properties: ['count' => $orders->count(), 'order_ids' => $orders->pluck('order_id')->values()->all()]);
        $keys = ['id_order','product_id','quantity','link_design','to_name','to_company','to_phone','to_address_1','to_address_2','to_city','to_state','to_postcode','to_country'];
        $headers = ['ID ORDER','Product ID','Quantity','Link Design','TO NAME','TO_COMPANY','TO_PHONE','TO ADDRESS 1','TO ADDRESS 2','TO CITY','TO STATE','TO POSTCODE','TO COUNTRY'];
        $html = '<table><thead><tr>'.implode('', array_map(fn ($h) => '<th>'.htmlspecialchars($h, ENT_QUOTES, 'UTF-8').'</th>', $headers)).'</tr></thead><tbody>';
        foreach ($orders as $order) { $data = $order->report_data ?? []; $data['id_order'] = $order->order_id; $data['size'] = $order->size ?: ($data['size'] ?? ''); $html .= '<tr>'.implode('', array_map(fn ($k) => '<td>'.htmlspecialchars((string) ($data[$k] ?? ''), ENT_QUOTES, 'UTF-8').'</td>', $keys)).'</tr>'; }
        $html .= '</tbody></table>';
        return response()->streamDownload(fn () => print $html, 'amazon-history-orders-'.now()->format('Ymd-His').'.xls', ['Content-Type' => 'application/vnd.ms-excel']);
    }

    public function openImportModal(): void
    {
        abort_unless((bool) auth()->user()?->is_admin, 403);
        $this->reset(['importUserId', 'importProductId', 'orderItemFile', 'previewRows']);
        $this->resetValidation();
        $this->showImportModal = true;
    }

    public function closeImportModal(): void
    {
        $this->showImportModal = false;
        $this->reset(['importUserId', 'importProductId', 'orderItemFile', 'previewRows']);
        $this->resetValidation();
    }

    public function openOrderProductImportModal(): void
    {
        abort_unless((bool) auth()->user()?->is_admin, 403);
        $this->reset(['orderProductFile', 'orderProductPreviewRows']);
        $this->resetValidation();
        $this->showOrderProductImportModal = true;
    }

    public function closeOrderProductImportModal(): void
    {
        $this->showOrderProductImportModal = false;
        $this->reset(['orderProductFile', 'orderProductPreviewRows']);
        $this->resetValidation();
    }

    public function openOrderReportImportModal(): void
    {
        abort_unless(auth()->check(), 403);
        $this->reset(['orderReportFile', 'orderReportPreviewRows', 'orderReportHeaders', 'orderFulfillmentPreviewRows']);
        $this->orderReportFulfillment = 'FBM';
        $this->orderReportProductTag = '';
        $this->orderReportPack = '';
        $this->orderReportHolo = false;
        $this->resetValidation();
        $this->showOrderReportImportModal = true;
    }

    public function closeOrderReportImportModal(): void
    {
        $this->showOrderReportImportModal = false;
        $this->reset(['orderReportFile', 'orderReportPreviewRows', 'orderReportHeaders', 'orderFulfillmentPreviewRows']);
        $this->resetValidation();
    }

    public function openEditOrderItemModal(int $itemId): void
    {
        abort_unless((bool) auth()->user()?->is_admin, 403);
        $item = SkuOrderItem::query()->findOrFail($itemId);
        $this->editingOrderItemId = $item->id;
        $this->editingOrderItemProductId = (string) $item->product_id;
        $this->editingOrderItemImageLink = (string) $item->image_link;
        $this->resetValidation();
        $this->showEditOrderItemModal = true;
    }

    public function closeEditOrderItemModal(): void
    {
        $this->showEditOrderItemModal = false;
        $this->reset(['editingOrderItemId', 'editingOrderItemProductId', 'editingOrderItemImageLink']);
        $this->resetValidation();
    }

    public function updateOrderItem(): void
    {
        abort_unless((bool) auth()->user()?->is_admin, 403);
        $data = $this->validate([
            'editingOrderItemId' => ['required', 'integer', 'exists:sku_order_items,id'],
            'editingOrderItemProductId' => ['required', 'integer', 'exists:products,id'],
            'editingOrderItemImageLink' => ['required', 'url', 'max:1000'],
        ]);
        $item = SkuOrderItem::query()->findOrFail($data['editingOrderItemId']);
        $item->update(['product_id' => (int) $data['editingOrderItemProductId'], 'image_link' => trim($data['editingOrderItemImageLink'])]);
        app(ActivityLogService::class)->record('order.sku_item_updated', "Updated SKU Order Item {$item->sku}.", $item, ['product_id' => (int) $data['editingOrderItemProductId']]);
        $this->message = "Da cap nhat SKU {$item->sku}.";
        $this->closeEditOrderItemModal();
    }

    public function updatedOrderReportFile(): void
    {
        $this->orderReportPreviewRows = [];
        $this->orderReportHeaders = [];
        $this->resetValidation('orderReportFile');
        if (! $this->orderReportFile) return;
        try {
            $rows = $this->reportRows($this->orderReportFile);
            $header = array_shift($rows) ?: [];
            $this->orderReportHeaders = array_values(array_map(fn ($value) => trim((string) $value), $header));
            if ($this->orderReportHeaders === [] || ! in_array('order-id', $this->orderReportHeaders, true)) throw new \RuntimeException('File report phai co cot order-id.');
            foreach (array_slice($rows, 0, 50) as $row) {
                $mapped = [];
                foreach ($this->orderReportHeaders as $index => $column) $mapped[$column] = trim((string) ($row[$index] ?? ''));
                if (implode('', $mapped) !== '') $this->orderReportPreviewRows[] = $mapped;
            }
            if ($this->orderReportPreviewRows === []) throw new \RuntimeException('File khong co dong du lieu.');
            $this->buildAmazonOrderPreview();
            app(ActivityLogService::class)->record('order.report_previewed', 'Previewed an Amazon order report.', properties: ['rows' => count($this->orderReportPreviewRows), 'filename' => $this->orderReportFile->getClientOriginalName()]);
        } catch (Throwable $exception) { $this->addError('orderReportFile', $exception->getMessage()); }
    }

    public function updatedOrderReportFulfillment(): void
    {
        $this->buildAmazonOrderPreview();
    }

    public function updatedOrderReportProductTag(): void
    {
        $this->buildAmazonOrderPreview();
    }

    public function updatedOrderReportPack(): void
    {
        $this->buildAmazonOrderPreview();
    }

    public function updatedOrderReportHolo(): void
    {
        $this->buildAmazonOrderPreview();
    }

    public function confirmOrderReport(): mixed
    {
        abort_unless(auth()->check(), 403);
        $validRows = collect($this->orderFulfillmentPreviewRows)->filter(fn (array $row): bool => ($row['error'] ?? '') === '');
        if ($validRows->isEmpty()) {
            $this->addError('orderReportFile', 'Khong co dong hop le de tai xuong.');
            return null;
        }

        foreach ($validRows as $row) {
            HistoryOrderReport::firstOrCreate(
                ['user_id' => auth()->id(), 'order_id' => $row['id_order'], 'sku' => (string) ($row['sku'] ?? ''), 'size' => (string) ($row['size'] ?? ''), 'quantity' => (string) ($row['quantity'] ?? '')],
                ['images_link' => array_values(array_filter([(string) $row['link_design']])),'report_data' => $row, 'ordered_at' => now()],
            );
        }
        app(ActivityLogService::class)->record('order.report_confirmed', 'Confirmed Amazon order report and exported valid rows.', properties: ['count' => $validRows->count(), 'fulfillment' => $this->orderReportFulfillment, 'holo' => $this->orderReportHolo, 'product_tag' => trim($this->orderReportProductTag)]);

        $isFba = $this->orderReportFulfillment === 'FBA';
        $headers = $isFba ? ['ID ORDER', 'Product ID', 'Quantity', 'Link Design'] : ['ID ORDER', 'Product ID', 'Quantity', 'Link Design', 'TO NAME', 'TO_COMPANY', 'TO_PHONE', 'TO ADDRESS 1', 'TO ADDRESS 2', 'TO CITY', 'TO STATE', 'TO POSTCODE', 'TO COUNTRY'];
        $filename = 'amazon-orders-'.now()->format('Ymd-His').'.xls';
        $html = '<table><thead><tr>'.implode('', array_map(fn (string $header): string => '<th>'.htmlspecialchars($header, ENT_QUOTES, 'UTF-8').'</th>', $headers)).'</tr></thead><tbody>';
        foreach ($validRows as $row) {
            $keys = $isFba ? ['id_order', 'product_id', 'quantity', 'link_design'] : ['id_order', 'product_id', 'quantity', 'link_design', 'to_name', 'to_company', 'to_phone', 'to_address_1', 'to_address_2', 'to_city', 'to_state', 'to_postcode', 'to_country'];
            $html .= '<tr>'.implode('', array_map(fn (string $key): string => '<td>'.htmlspecialchars((string) ($row[$key] ?? ''), ENT_QUOTES, 'UTF-8').'</td>', $keys)).'</tr>';
        }
        $html .= '</tbody></table>';
        return response()->streamDownload(fn () => print $html, $filename, ['Content-Type' => 'application/vnd.ms-excel']);
    }

    private function buildAmazonOrderPreview(): void
    {
        $this->orderFulfillmentPreviewRows = [];
        if ($this->orderReportPreviewRows === []) return;
        $items = SkuOrderItem::query()->where('user_id', auth()->id())->with('product')->get();
        $catalog = OrderProduct::query()->where('fulfillment_type', $this->orderReportFulfillment)->get();
        foreach ($this->orderReportPreviewRows as $source) {
            $incomingSku = trim($source['sku'] ?? '');
            $matched = $this->matchOrderItemSku($incomingSku, $items);
            $size = null;
            if (preg_match('/(\d+(?:\.\d+)?)\s*["”]/', $source['product-name'] ?? '', $matches)) $size = $matches[1].'in';
            $productKeyword = strtolower((string) ($matched?->product?->name ?? ''));
            $catalogMatch = $matched ? $catalog->first(function (OrderProduct $product) use ($productKeyword, $size): bool {
                $name = strtolower($product->product_name);
                $tag = strtolower(trim($this->orderReportProductTag));
                $pack = trim($this->orderReportPack);
                $isHoloProduct = str_contains($name, 'holo');
                return $productKeyword !== '' && str_contains($name, $productKeyword)
                    && (! $this->orderReportHolo || $isHoloProduct)
                    && ($tag === '' || str_contains($name, $tag))
                    && ($this->orderReportFulfillment !== 'FBA' || ($pack !== '' && str_contains($name, 'pack '.strtolower($pack))))
                    && ($size === null || str_contains($name, strtolower($size)));
            }) : null;
            $orderId = trim((string) ($source['order-id'] ?? ''));
            $historyVariant = ['user_id' => auth()->id(), 'order_id' => $orderId, 'sku' => $this->normalizeSku($incomingSku), 'size' => (string) ($size ?? ''), 'quantity' => (string) ($source['quantity-purchased'] ?? '')];
            $duplicate = $orderId !== '' && HistoryOrderReport::query()->where($historyVariant)->exists();
            $error = $duplicate ? 'Don nay da len roi, vui long kiem tra lai.' : ($this->orderReportFulfillment === 'FBA' && trim($this->orderReportPack) === '' ? 'FBA vui long nhap Pack (vi du: 3).' : ($matched ? ($catalogMatch ? '' : 'Khong tim thay Order Product dung Product/size/Pack.') : "SKU {$incomingSku} khong co trong SKU Order Items."));
            $this->orderFulfillmentPreviewRows[] = [
                'id_order' => $orderId, 'sku' => $historyVariant['sku'], 'product_id' => (string) ($catalogMatch?->order_product_id ?? ''),
                'quantity' => $source['quantity-purchased'] ?? '', 'link_design' => (string) ($matched?->image_link ?? ''),
                'size' => $size ?? '',
                'to_name' => $source['recipient-name'] ?? '', 'to_address_1' => $source['ship-address-1'] ?? '', 'to_address_2' => $source['ship-address-2'] ?? '',
                'to_city' => $source['ship-city'] ?? '', 'to_state' => $source['ship-state'] ?? '', 'to_postcode' => $source['ship-postal-code'] ?? '',
                'to_country' => $source['ship-country'] ?? '', 'error' => $error,
            ];
        }
    }

    /**
     * Resolve an incoming report SKU using exact match first, then the longest
     * stored SKU prefix. A shorter prefix can never outrank a longer match.
     */
    private function matchOrderItemSku(string $incomingSku, $items): ?SkuOrderItem
    {
        $normalizedIncoming = strtoupper(trim($incomingSku));
        if ($normalizedIncoming === '') return null;

        $exact = $items->first(function (SkuOrderItem $item) use ($normalizedIncoming): bool {
            return strtoupper(trim((string) $item->sku)) === $normalizedIncoming;
        });
        if ($exact) return $exact;

        return $items
            ->filter(function (SkuOrderItem $item) use ($normalizedIncoming): bool {
                $sku = strtoupper(trim((string) $item->sku));
                return $sku !== '' && str_starts_with($normalizedIncoming, $sku);
            })
            ->sort(function (SkuOrderItem $left, SkuOrderItem $right): int {
                $lengthComparison = strlen(trim((string) $right->sku)) <=> strlen(trim((string) $left->sku));
                return $lengthComparison !== 0 ? $lengthComparison : ((int) $left->id <=> (int) $right->id);
            })
            ->first();
    }

    private function normalizeSku(string $sku): string
    {
        return strtoupper(trim(str_replace("\xEF\xBB\xBF", '', $sku)));
    }

    private function reportRows(TemporaryUploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'rb');
        if (! $handle) throw new \RuntimeException('Khong mo duoc file report.');
        $first = fgets($handle); rewind($handle);
        $delimiter = is_string($first) && substr_count($first, "\t") > substr_count($first, ',') ? "\t" : ',';
        $rows = [];
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) $rows[] = array_map(fn ($value) => trim((string) $value), $row);
        fclose($handle);
        return $rows;
    }

    public function updatedOrderProductFile(): void
    {
        $this->orderProductPreviewRows = [];
        $this->resetValidation('orderProductFile');
        if (! $this->orderProductFile) return;
        try {
            $rows = $this->spreadsheetRows($this->orderProductFile);
            $header = array_shift($rows) ?: [];
            $headers = array_map(fn ($value) => strtolower(trim(preg_replace('/[^a-z0-9]+/i', '_', preg_replace('/^\xEF\xBB\xBF/', '', (string) $value)) ?? '')), $header);
            $required = ['id', 'fbm_fba', 'order_product_id', 'product_name'];
            foreach ($required as $column) if (! in_array($column, $headers, true)) throw new \RuntimeException("File phai co cot {$column}.");
            $positions = array_flip($headers);
            foreach ($rows as $row) {
                $values = ['source_id' => trim((string) ($row[$positions['id']] ?? '')), 'fulfillment_type' => strtoupper(trim((string) ($row[$positions['fbm_fba']] ?? ''))), 'order_product_id' => trim((string) ($row[$positions['order_product_id']] ?? '')), 'product_name' => trim((string) ($row[$positions['product_name']] ?? ''))];
                if (implode('', $values) !== '') $this->orderProductPreviewRows[] = $values;
            }
            if ($this->orderProductPreviewRows === []) throw new \RuntimeException('File khong co dong du lieu.');
        } catch (Throwable $exception) { $this->addError('orderProductFile', $exception->getMessage()); }
    }

    public function importOrderProducts(): void
    {
        abort_unless((bool) auth()->user()?->is_admin, 403);
        $this->validate(['orderProductFile' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:10240']]);
        if ($this->orderProductPreviewRows === []) { $this->addError('orderProductFile', 'Hay upload file de xem preview truoc.'); return; }
        $count = 0;
        foreach ($this->orderProductPreviewRows as $row) {
            if (! ctype_digit($row['source_id']) || ! in_array($row['fulfillment_type'], ['FBM', 'FBA'], true) || ! ctype_digit($row['order_product_id']) || $row['product_name'] === '') continue;
            OrderProduct::updateOrCreate(['source_id' => (int) $row['source_id']], ['fulfillment_type' => $row['fulfillment_type'], 'order_product_id' => (int) $row['order_product_id'], 'product_name' => $row['product_name']]);
            $count++;
        }
        $this->message = "Da import/cap nhat {$count} order product.";
        app(ActivityLogService::class)->record('order.products_imported', 'Imported or updated Order Products.', properties: ['count' => $count]);
        $this->closeOrderProductImportModal();
        $this->resetPage('orderProductsPage');
    }

    public function updatedOrderItemFile(): void
    {
        $this->previewRows = [];
        $this->resetValidation('orderItemFile');
        if (! $this->orderItemFile) return;
        try {
            $rows = $this->spreadsheetRows($this->orderItemFile);
            $header = array_shift($rows) ?: [];
            $headers = array_map(fn ($value) => strtolower(trim(preg_replace('/[^a-z0-9]+/i', '_', preg_replace('/^\xEF\xBB\xBF/', '', (string) $value)) ?? '')), $header);
            $skuIndex = array_search('sku', $headers, true);
            $imageIndex = array_search('images_link', $headers, true);
            if ($imageIndex === false) $imageIndex = array_search('image_link', $headers, true);
            if ($skuIndex === false || $imageIndex === false) throw new \RuntimeException('File phai co cot SKU va IMAGES_LINK.');
            foreach ($rows as $row) {
                $sku = trim((string) ($row[$skuIndex] ?? ''));
                $image = trim((string) ($row[$imageIndex] ?? ''));
                if ($sku !== '' || $image !== '') $this->previewRows[] = ['sku' => $sku, 'image_link' => $image];
            }
            if ($this->previewRows === []) throw new \RuntimeException('File khong co dong du lieu.');
        } catch (Throwable $exception) {
            $this->addError('orderItemFile', $exception->getMessage());
        }
    }

    public function importOrderItems(): void
    {
        abort_unless((bool) auth()->user()?->is_admin, 403);
        $data = $this->validate([
            'importUserId' => ['required', 'integer', 'exists:users,id'],
            'importProductId' => ['required', 'integer', 'exists:products,id'],
            'orderItemFile' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:10240'],
        ]);
        if ($this->previewRows === []) { $this->addError('orderItemFile', 'Hay upload file de xem preview truoc.'); return; }
        $user = $this->importableUsers()->whereKey($data['importUserId'])->first();
        if (! $user) { $this->addError('importUserId', 'Chi duoc chon user thuong, khong chon Admin.'); return; }
        $product = Product::query()->whereKey($data['importProductId'])->whereIn('slug', ['sticker', 'glass', 'ornament-etsy'])->first();
        if (! $product) { $this->addError('importProductId', 'Product khong hop le.'); return; }
        $count = 0;
        foreach ($this->previewRows as $row) {
            $sku = $row['sku'];
            $image = $row['image_link'];
            if ($sku === '' && $image === '') continue;
            if ($sku === '' || ! filter_var($image, FILTER_VALIDATE_URL) || ! str_contains(strtolower((string) parse_url($image, PHP_URL_HOST)), 'drive.google.com')) continue;
            $item = SkuOrderItem::query()->where('user_id', $user->id)->where('sku', $sku)->first();
            if ($item) $item->update(['product_id' => $product->id, 'image_link' => $image, 'source' => 'manual_import', 'product_design_asset_id' => null]);
            else SkuOrderItem::query()->create(['user_id' => $user->id, 'product_id' => $product->id, 'sku' => $sku, 'image_link' => $image, 'source' => 'manual_import', 'product_design_asset_id' => null]);
            $count++;
        }
        $this->message = "Da import/cap nhat {$count} Order Item cho {$user->name}.";
        app(ActivityLogService::class)->record('order.sku_items_imported', "Imported or updated SKU Order Items for {$user->name}.", properties: ['count' => $count, 'target_user_id' => $user->id, 'product_id' => $product->id]);
        $this->closeImportModal();
        $this->resetPage();
    }

    public function updatedSearch(): void { $this->resetPage(); }

    public function updatedProductFilter(): void { $this->resetPage(); }

    public function updatedPerPage(int|string $value): void
    {
        $value = (int) $value;
        $this->perPage = in_array($value, [5, 10, 20, 50, 100], true) ? $value : 5;
        $this->resetPage();
        $this->resetPage('orderProductsPage');
    }

    public function reloadItems(): void
    {
        abort_unless((bool) auth()->user()?->is_admin, 403);

        $this->message = null;
        $this->error = null;
        $this->resetPage();

        $assets = ProductDesignAsset::query()
            ->where('is_approved', true)
            ->whereNotNull('sku')
            ->where('sku', '!=', '')
            ->whereNotNull('redesign')
            ->where('redesign', 'like', '%drive.google.com%')
            ->whereHas('product', fn ($query) => $query->whereNotIn('slug', ['suncatcher', 'ornament-amazon-2']))
            ->whereDoesntHave('skuOrderItem')
            ->get();

        $created = 0;
        foreach ($assets as $asset) {
            try {
                if (app(SkuOrderItemService::class)->syncForAsset($asset)) {
                    $created++;
                }
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        $this->message = $created > 0
            ? "Da nap {$created} SKU vao danh sach Order."
            : 'Khong co item moi can nap.';
        app(ActivityLogService::class)->record('order.sku_items_reloaded', 'Reloaded approved asset SKU Order Items.', properties: ['created' => $created]);
    }

    public function render(): View
    {
        $preview = app(ImageLinkPreviewService::class);

        $items = SkuOrderItem::query()
            ->when(! auth()->user()?->is_admin, fn ($query) => $query->where('user_id', auth()->id()))
            ->when(trim($this->search) !== '', fn ($query) => $query->where('sku', 'like', '%'.trim($this->search).'%'))
            ->when($this->productFilter !== '', fn ($query) => $query->whereHas('product', fn ($product) => $product->where('slug', $this->productFilter)))
            ->with(['asset.product', 'product'])
            ->orderBy('sku')
            ->paginate($this->perPage);

        $items->getCollection()->each(fn (SkuOrderItem $item) => $item->setAttribute('image_preview_url', $preview->previewUrl($item->image_link)));

        $orderProducts = OrderProduct::query()->orderBy('product_name')->paginate($this->perPage, ['*'], 'orderProductsPage');
        $historyOrders = HistoryOrderReport::query()
            ->when(! auth()->user()?->is_admin, fn ($query) => $query->where('user_id', auth()->id()))
            ->when(trim($this->historyOrderSearch) !== '', fn ($query) => $query->where('order_id', 'like', '%'.trim($this->historyOrderSearch).'%'))
            ->with('user')
            ->latest('ordered_at')
            ->latest('id')
            ->paginate($this->perPage, ['*'], 'historyOrdersPage');
        $historyOrders->getCollection()->each(function (HistoryOrderReport $order) use ($preview): void {
            $image = (string) (($order->images_link ?? [])[0] ?? '');
            $order->setAttribute('image_preview_url', $image !== '' ? $preview->previewUrl($image) : '');
        });

        return view('livewire.pages.order.index', [
            'items' => $items,
            'products' => Product::query()->whereIn('slug', ['sticker', 'glass', 'ornament-etsy'])->orderBy('name')->get(),
            'importUsers' => auth()->user()?->is_admin ? $this->importableUsers()->orderBy('name')->get(['id', 'name', 'email']) : collect(),
            'orderProducts' => $orderProducts,
            'historyOrders' => $historyOrders,
        ])->layout('layouts.app');
    }

    private function importableUsers()
    {
        return User::query()->where('is_admin', false)->where(fn ($query) => $query->whereNull('role')->orWhere('role', '!=', 'admin'));
    }

    /** @return array<int, array<int, string>> */
    private function spreadsheetRows(TemporaryUploadedFile $file): array
    {
        if (strtolower($file->getClientOriginalExtension()) !== 'xlsx') {
            $handle = fopen($file->getRealPath(), 'rb');
            if (! $handle) return [];
            $rows = []; while (($row = fgetcsv($handle)) !== false) $rows[] = array_map(fn ($value) => trim((string) $value), $row); fclose($handle); return $rows;
        }
        if (! class_exists(\ZipArchive::class)) throw new \RuntimeException('Server chua co php-zip; hay luu file thanh CSV va import lai.');
        $zip = new \ZipArchive(); if ($zip->open($file->getRealPath()) !== true) throw new \RuntimeException('Khong mo duoc file Excel.');
        $stringsXml = $zip->getFromName('xl/sharedStrings.xml'); $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml'); $zip->close();
        $strings = []; if (is_string($stringsXml) && ($xml = simplexml_load_string($stringsXml))) { $xml->registerXPathNamespace('a', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main'); foreach ($xml->xpath('//a:si') ?: [] as $node) $strings[] = trim(strip_tags((string) $node->asXML())); }
        if (! is_string($sheetXml) || ! ($sheet = simplexml_load_string($sheetXml))) throw new \RuntimeException('Khong doc duoc sheet dau tien.');
        $sheet->registerXPathNamespace('a', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main'); $rows = [];
        foreach ($sheet->xpath('//a:sheetData/a:row') ?: [] as $row) { $row->registerXPathNamespace('a', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main'); $values = []; foreach ($row->xpath('./a:c') ?: [] as $cell) { preg_match('/^[A-Z]+/i', (string) $cell['r'], $m); $i = 0; foreach (str_split(strtoupper($m[0] ?? 'A')) as $letter) $i = ($i * 26) + ord($letter) - 64; $value = trim((string) ($cell->v[0] ?? '')); $values[$i - 1] = (string) $cell['t'] === 's' ? ($strings[(int) $value] ?? '') : $value; } ksort($values); $rows[] = $values ? array_replace(array_fill(0, max(array_keys($values)) + 1, ''), $values) : []; }
        return $rows;
    }
}
