<?php

namespace App\Livewire\Pages\Order;

use App\Models\HistoryOrderReport;
use App\Models\Product;
use App\Models\ListingPromptOverride;
use App\Models\UserApiCredential;
use App\Models\OrderProduct;
use App\Models\SkuOrderItem;
use App\Models\User;
use App\Services\Ai\ApiKeyImageGenerator;
use App\Services\Google\GoogleDriveService;
use App\Services\Glass\GlassService;
use Illuminate\Support\Facades\Cache;
use App\Services\Image\ImageLinkPreviewService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

class OrderAmazonPaste extends Component
{
    use WithFileUploads;

    public string $orderText = '';

    public string $orderTextId = '';

    public string $orderTextShipTo = '';

    public string $orderTextCompany = '';

    public string $orderTextPhone = '';

    public string $orderTextProduct = '';

    public string $orderTextSku = '';

    public string $orderTextQuantity = '';

    public bool $orderTextFbaOnly = false;

    public string $orderTextCustomizations = '';

    public bool $orderTextParsed = false;

    public bool $orderTextLocked = false;

    #[Locked]
    public array $orderTextRows = [];

    public array $orderTextTrackingFiles = [];

    public $orderAmazonBoundsTemplate;

    public string $orderAmazonBoundsType = 'glass';

    #[Locked]
    public array $orderAmazonBoundsTemplates = [];


    #[Locked]
    public array $orderTextTrackingLinks = [];

    #[Locked]
    public array $orderTextMasterImages = [];
    #[Locked]
    public array $orderTextTemplateImages = [];

    #[Locked]
    public array $orderTextMasterDriveLinks = [];

    #[Locked]
    public array $orderTextApproved = [];

    public array $orderTextSkipDesignLink = [];

    public array $orderTextReplacementSources = [];

    #[Locked]
    public array $orderTextMasterErrors = [];

    public string $orderAmazonPrompt = '';

    public string $orderAmazonImageModel = '';

    private const IMAGE_MODELS = ['gemini-2.5-flash-image', 'gemini-3-pro-image', 'gemini-3-pro-image-preview', 'gemini-3.1-flash-image', 'gemini-3.1-flash-image-preview', 'gemini-3.1-flash-lite-image', 'gpt-image-1', 'gpt-image-1-2025-04-15', 'gpt-image-1-mini', 'gpt-image-1.5', 'gpt-image-2', 'gpt-image-2-c', 'gpt-image-2.5-flare', 'gpt-image-2.5-flare-c', 'gpt-image-2.5-sunburst', 'gpt-image-2.5-sunburst-c', 'grok-imagine-image', 'grok-imagine-image-pro'];

    private const DEFAULT_PROMPT = 'Create a clean, production-ready product master image using the reference image. Keep the artwork faithful, centered, sharp, and suitable for fulfillment.';

    public function mount(): void
    {
        $draft = session()->get('order_amazon_paste.'.auth()->id(), []);
        foreach (['orderTextRows', 'orderTextTrackingLinks', 'orderTextMasterImages', 'orderTextTemplateImages', 'orderTextMasterDriveLinks', 'orderTextApproved', 'orderTextSkipDesignLink', 'orderTextReplacementSources', 'orderTextFbaOnly', 'orderText', 'orderTextLocked'] as $property) {
            if (array_key_exists($property, $draft)) $this->{$property} = $draft[$property];
        }
        foreach (array_keys($this->orderTextRows) as $index) {
            $this->orderTextReplacementSources[$index] = array_replace(['name_mode' => 'replace', 'name' => 'Name', 'name_position' => '', 'year_mode' => 'replace', 'year' => 'Year', 'year_position' => ''], $this->orderTextReplacementSources[$index] ?? []);
        }
        $this->orderTextParsed = $this->orderTextRows !== [];
        $this->orderAmazonBoundsTemplates = $this->loadOrderAmazonBoundsTemplates();
        if (auth()->user()?->isSuperAdmin()) {
            $this->orderAmazonPrompt = $this->loadOrderAmazonPrompt();
            $this->orderAmazonImageModel = $this->loadOrderAmazonImageModel();
        }
    }

    public function dehydrate(): void
    {
        session()->put('order_amazon_paste.'.auth()->id(), [
            'orderTextRows' => $this->orderTextRows,
            'orderTextTrackingLinks' => $this->orderTextTrackingLinks,
            'orderTextMasterImages' => $this->orderTextMasterImages,
            'orderTextTemplateImages' => $this->orderTextTemplateImages,
            'orderTextMasterDriveLinks' => $this->orderTextMasterDriveLinks,
            'orderTextApproved' => $this->orderTextApproved,
            'orderTextSkipDesignLink' => $this->orderTextSkipDesignLink,
            'orderTextReplacementSources' => $this->orderTextReplacementSources,
            'orderTextFbaOnly' => $this->orderTextFbaOnly,
            'orderText' => $this->orderText,
            'orderTextLocked' => $this->orderTextLocked,
        ]);
    }

    #[On('cheapkeyai-key-updated')]
    public function refreshCheapKeyAiBalance(): void {}

    public function parseOrderText(): void
    {
        $this->validate(['orderText' => 'required|string|max:100000']);
        if ($this->orderTextLocked) return;
        $input = str_replace(["\r\n", "\r"], "\n", $this->orderText);
        $parts = preg_split('/(?=Order\s+details\s+Order\s+ID\s*:)/i', $input, -1, PREG_SPLIT_NO_EMPTY) ?: [$input];
        if (count($parts) > 1 && ! preg_match('/\bOrder\s+details\s+Order\s+ID\s*:/i', $parts[0])) array_shift($parts);
        foreach ($parts as $orderText) {
            preg_match('/\bOrder\s*ID\s*:??\s*#?\s*(\d{3}-\d{7}-\d{7})/i', $orderText, $order);
            $orderId = trim($order[1] ?? '');
            preg_match('/\bShip\s+to\b[^\n]*\n(.*?)(?=\n\s*(?:Address\s+Type|Contact\s+Buyer|Phone|More\s+details|Order\s+contents)\b|\z)/is', $orderText, $ship);
            $shipTo = trim($ship[1] ?? '');
            preg_match('/^\s*Phone\s*:\s*([^\r\n]+)/im', $orderText, $phone);
            preg_match_all('/^[ \t]*SKU[ \t]*:/im', $orderText, $skuMarkers, PREG_OFFSET_CAPTURE);
            $offsets = array_map(fn (array $marker): int => $marker[1], $skuMarkers[0] ?? []);
            if ($offsets === []) $offsets = [0];
            foreach ($offsets as $index => $offset) {
                $itemText = substr($orderText, $offset, isset($offsets[$index + 1]) ? $offsets[$index + 1] - $offset : null) ?: $orderText;
                preg_match('/^[ \t]*SKU[ \t]*:[ \t]*([^\r\n]+)/im', $itemText, $sku);
                $itemSku = trim($sku[1] ?? '');
                preg_match('/Order\s+Item\s+ID\s*:\s*[^\n]+\n\s*(\d+)\s+\$[0-9]/i', $itemText, $quantity);
                preg_match('/^\s*Customizations\s*:\s*\n(.*?)(?=\n\s*(?:Customization\s+Information|Show\s+less|Condition\s*:|Order\s+Item\s+ID\s*:)|\z)/ims', $itemText, $customizations);
                $customizationText = trim($customizations[1] ?? '');
                $this->orderTextReplacementSources[count($this->orderTextRows)] = ['name_mode' => 'replace', 'name' => 'Name', 'name_position' => '', 'year_mode' => 'replace', 'year' => 'Year', 'year_position' => ''];
                $this->orderTextRows[] = [
                    'sku' => $itemSku, 'id' => $orderId, 'shipTo' => $shipTo,
                    'company' => '', 'phone' => trim($phone[1] ?? ''),
                    'quantity' => trim($quantity[1] ?? ''), 'customizations' => $customizationText,
                ];
            }
            preg_match('/\bOrder\s+contents\b.*?\bUnshipped\b\s*(.*?)(?=\n\s*ASIN\s*:)/is', $orderText, $product);
            $this->orderTextProduct = trim($product[1] ?? '');
            $this->orderTextSku = $itemSku;
            $this->orderTextQuantity = trim($quantity[1] ?? '');
            $this->orderTextCustomizations = $customizationText;
            $this->orderTextId = $orderId;
            $this->orderTextShipTo = $shipTo;
            $this->orderTextPhone = trim($phone[1] ?? '');
        }
        $this->orderTextParsed = $this->orderTextRows !== [];
        $this->orderTextLocked = true;
    }

    public function clearOrderTextInput(): void
    {
        $this->orderText = '';
        $this->orderTextLocked = false;
        $this->resetValidation('orderText');
    }

    public function removeOrderTextRow(int $index): void
    {
        if (! array_key_exists($index, $this->orderTextRows)) return;
        if (! $this->deleteOrderDraftImages([$this->orderTextMasterImages[$index] ?? null, $this->orderTextTemplateImages[$index] ?? null])) return;
        array_splice($this->orderTextRows, $index, 1);
        $this->orderTextTrackingFiles = collect($this->orderTextTrackingFiles)->reject(fn ($file, $key) => (int) $key === $index)->mapWithKeys(fn ($file, $key) => [(int) $key > $index ? (int) $key - 1 : (int) $key => $file])->all();
        $this->orderTextTrackingLinks = collect($this->orderTextTrackingLinks)->reject(fn ($link, $key) => (int) $key === $index)->mapWithKeys(fn ($link, $key) => [(int) $key > $index ? (int) $key - 1 : (int) $key => $link])->all();
        foreach (['orderTextMasterImages', 'orderTextTemplateImages', 'orderTextMasterDriveLinks', 'orderTextApproved', 'orderTextSkipDesignLink', 'orderTextReplacementSources', 'orderTextMasterErrors'] as $property) {
            $this->{$property} = collect($this->{$property})->reject(fn ($value, $key) => (int) $key === $index)->mapWithKeys(fn ($value, $key) => [(int) $key > $index ? (int) $key - 1 : (int) $key => $value])->all();
        }
        $this->orderTextParsed = $this->orderTextRows !== [];
    }

    public function updatedOrderTextTrackingFiles(mixed $value, string $key): void
    {
        unset($this->orderTextTrackingLinks[(int) $key]);
    }

    public function clearOrderTextRows(): void
    {
        if (! $this->deleteAllOrderDraftImages()) return;
        $this->orderTextRows = [];
        $this->orderTextTrackingFiles = [];
        $this->orderTextReplacementSources = [];
        $this->orderTextTrackingLinks = [];
        $this->orderTextMasterImages = [];
        $this->orderTextTemplateImages = [];
        $this->orderTextMasterDriveLinks = [];
        $this->orderTextApproved = [];
        $this->orderTextSkipDesignLink = [];
        $this->orderTextMasterErrors = [];
        $this->orderTextParsed = false;
        $this->orderText = '';
        $this->orderTextLocked = false;
        $this->resetErrorBag('orderTextMaster');
        $this->dispatch('close-modal', 'order-amazon-clear-data');
    }

    private function orderDraftImagePath(array $row, string $kind, ?string $type = null): string
    {
        $sku = preg_replace('/[^A-Za-z0-9_-]+/', '-', $row['sku']) ?: 'unknown';
        $productId = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) ($row['catalogProduct']?->order_product_id ?? 'unknown')) ?: 'unknown';
        $directory = 'order-amazon-paste/'.auth()->id().'/'.($type ?? $this->orderDraftProduct($row));
        $name = $sku.'_'.auth()->id().'_'.$productId.'_'.now()->format('dmY');
        return $directory.'/'.$name.($kind === 'master' ? '' : '_'.$kind.'_'.Str::uuid()).'.png';
    }

    private function orderDraftProduct(array $row): string
    {
        $names = $row['designs']->pluck('product.name')->filter()->unique()->values();
        $name = strtolower((string) ($names->count() === 1 ? $names->first() : '')); 

        return preg_match('/\b(glass|ceramic)\b/i', $name, $match) ? strtolower($match[1]) : 'unknown';
    }

    private function deleteAllOrderDraftImages(): bool
    {
        $disk = Storage::disk('public');
        $directory = 'order-amazon-paste/'.auth()->id();

        if ($disk->exists($directory) && ! $disk->deleteDirectory($directory)) {
            $this->addError('orderTextMaster', 'Cannot delete draft images. Please try again.');
            return false;
        }

        return true;
    }
    private function deleteOrderDraftImages(array $images): bool
    {
        $disk = Storage::disk('public');
        $prefix = '/storage/order-amazon-paste/'.auth()->id().'/';
        foreach (array_unique(array_filter($images, 'is_string')) as $image) {
            if (! str_starts_with($image, $prefix)) continue;
            $path = substr($image, strlen('/storage/'));
            if (str_contains(rawurldecode($path), '..') || str_contains($path, '\\')) continue;
            if ($disk->exists($path) && ! $disk->delete($path)) {
                $this->addError('orderTextMaster', 'Không thể xóa ảnh nháp. Vui lòng thử lại.');
                return false;
            }
        }
        return true;
    }

    private function orderTextCustomizationValue(string $label, ?string $customizations = null): ?string
    {
        foreach (preg_split('/\R/u', $customizations ?? $this->orderTextCustomizations) ?: [] as $line) {
            [$currentLabel, $value] = array_pad(preg_split('/:(?![^()]*\))/', trim($line), 2) ?: [], 2, '');
            if (strcasecmp(trim($currentLabel), $label) === 0) return trim($value) ?: null;
        }

        return null;
    }

    private function matchSimpleOrderProduct($catalog, string $productName, ?string $size): ?OrderProduct
    {
        $name = strtolower(trim($productName));
        if ($name === '') return null;
        $singleSize = $this->containsProductToken($name, 'glass') || $this->containsProductToken($name, 'ceramic');
        $requiredSize = $singleSize ? null : $this->extractProductSize((string) $size);
        if (! $singleSize && $requiredSize === null) return null;

        $matches = $catalog->filter(function (OrderProduct $orderProduct) use ($name, $requiredSize): bool {
            $catalogName = strtolower(trim($orderProduct->product_name));
            return $this->containsProductPhrase($catalogName, $name)
                && (! $this->containsProductToken($name, 'glass') || preg_match('/\b1[\s-]*side\b/i', $catalogName) === 1)
                && ($requiredSize === null || $this->containsProductSize($catalogName, $requiredSize));
        });

        return $matches->count() === 1 ? $matches->first() : null;
    }

    private function extractProductSize(string $value): ?string
    {
        return preg_match('/(?<![0-9])([0-9]+(?:\.[0-9]+)?)\s*(?:in|inch|inches|["”])/i', $value, $match)
            ? $match[1].'in' : null;
    }

    private function containsProductSize(string $name, string $size): bool
    {
        $number = preg_quote((string) preg_replace('/\s*(?:in|inch|inches)$/i', '', trim($size)), '/');
        return preg_match('/(?<![0-9])'.$number.'\s*(?:in|inch|inches|["”])(?![0-9])/i', $name) === 1;
    }

    private function containsProductToken(string $name, string $token): bool
    {
        $token = trim($token);
        if ($token === '') return true;
        return preg_match('/(?<![a-z0-9])'.preg_quote($token, '/').'(?![a-z0-9])/i', $name) === 1;
    }

    private function containsProductPhrase(string $name, string $phrase): bool
    {
        $phrase = trim($phrase);
        return $phrase !== '' && preg_match('/(?<![a-z0-9])'.preg_quote($phrase, '/').'(?![a-z0-9])/i', $name) === 1;
    }

    public function saveOrderAmazonBoundsTemplate(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
        $this->validate([
            'orderAmazonBoundsType' => ['required', 'in:glass,ceramic'],
            'orderAmazonBoundsTemplate' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:10240'],
        ]);

        $type = $this->orderAmazonBoundsType;
        $bytes = file_get_contents($this->orderAmazonBoundsTemplate->getRealPath());
        $size = @getimagesizefromstring($bytes);
        if (! is_array($size) || ($size[0] ?? 0) < 1 || ($size[1] ?? 0) < 1 || $size[0] > 4096 || $size[1] > 4096 || $size[0] * $size[1] > 16000000) {
            $this->addError('orderAmazonBoundsTemplate', 'Template không hợp lệ.');
            return;
        }

        $image = @imagecreatefromstring($bytes);
        if (! $image) {
            $this->addError('orderAmazonBoundsTemplate', 'Không đọc được ảnh template.');
            return;
        }
        imagealphablending($image, false);
        imagesavealpha($image, true);
        ob_start();
        imagepng($image);
        $png = ob_get_clean();
        imagedestroy($image);
        if (! is_string($png) || $png === '') {
            $this->addError('orderAmazonBoundsTemplate', 'Không lưu được ảnh template.');
            return;
        }
        $disk = Storage::disk('public');
        $base = 'admin/order-amazon-paste/bounds/'.$type;
        $disk->put($base.'.png', $png);
        $disk->put($base.'.json', json_encode([
            'type' => $type,
            'canvas' => ['width' => (int) $size[0], 'height' => (int) $size[1]],
            'updated_at' => now()->toIso8601String(),
        ], JSON_THROW_ON_ERROR));

        $this->orderAmazonBoundsTemplates = $this->loadOrderAmazonBoundsTemplates();
        $this->reset('orderAmazonBoundsTemplate');
        $this->dispatch('close-modal', 'order-amazon-bounds-template');
        $this->dispatch('toast', type: 'success', title: 'Đã lưu', message: 'Đã cập nhật template bounds '.ucfirst($type).'.');
    }

    public function saveOrderAmazonPrompt(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
        $this->validate(['orderAmazonPrompt' => 'required|string']);
        $product = Product::query()->where('slug', 'order-amazon-paste')->firstOrFail();
        ListingPromptOverride::query()->updateOrCreate(
            ['product_id' => $product->id, 'marketplace' => 'order-master'],
            ['content' => trim($this->orderAmazonPrompt), 'updated_by' => auth()->id()],
        );
        $this->dispatch('close-modal', 'order-amazon-prompt');
        $this->dispatch('toast', type: 'success', title: 'Da luu', message: 'Da luu prompt Order Amazon Paste.');
    }

    public function saveOrderAmazonImageModel(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
        $this->validate(['orderAmazonImageModel' => ['required', 'string', \Illuminate\Validation\Rule::in(self::IMAGE_MODELS)]]);
        $product = Product::query()->where('slug', 'order-amazon-paste')->firstOrFail();
        ListingPromptOverride::query()->updateOrCreate(
            ['product_id' => $product->id, 'marketplace' => 'order-model'],
            ['content' => $this->orderAmazonImageModel, 'updated_by' => auth()->id()],
        );
        $this->dispatch('close-modal', 'order-amazon-model');
        $this->dispatch('toast', type: 'success', title: 'Da luu', message: 'Da luu model tao anh dung chung.');
    }

    public function generateOrderTextMaster(int $index): void
    {
        abort_unless(auth()->user()?->canAccessProduct('order-amazon-paste'), 403);
        $rows = $this->buildOrderTextResults()[0];
        $row = $rows[$index] ?? null;
        $source = $row ? $row['designs']->pluck('image_link')->filter()->first() : null;
        if (! $row || ! is_string($source) || trim($source) === '') {
            $this->addError('orderTextMaster', 'SKU nay chua co Design Link de tao anh.');
            return;
        }

        $host = strtolower((string) parse_url($source, PHP_URL_HOST));
        if ((str_starts_with($source, '/storage/') && str_contains(rawurldecode($source), '..')) || (! str_starts_with($source, '/storage/') && ($host !== 'drive.google.com' || strtolower((string) parse_url($source, PHP_URL_SCHEME)) !== 'https'))) {
            $this->orderTextMasterErrors[$index] = 'Anh nguon phai la file local hoac link Google Drive.';
            return;
        }
        if (! UserApiCredential::query()->where('user_id', auth()->id())->where('provider_key', 'cheapkeyai')->where('is_active', true)->exists()) {
            $this->orderTextMasterErrors[$index] = 'Chua co CheapKeyAI API key. Hay them key trong trang da duoc cap quyen truoc.';
            return;
        }
        $name = trim((string) ($row['customizationValues']['ADD NAME'] ?? ''));
        $year = trim((string) ($row['customizationValues']['ADD YEAR'] ?? ''));
        if ($name === '-') $name = '';
        if ($year === '-') $year = '';
        $replacement = $this->orderTextReplacementSources[$index] ?? [];
        $nameMode = ($replacement['name_mode'] ?? 'replace') === 'add' ? 'add' : 'replace';
        $yearMode = ($replacement['year_mode'] ?? 'replace') === 'add' ? 'add' : 'replace';
        $nameSource = trim((string) ($replacement['name'] ?? 'Name'));
        $yearSource = trim((string) ($replacement['year'] ?? 'Year'));
        $namePosition = trim((string) ($replacement['name_position'] ?? ''));
        $yearPosition = trim((string) ($replacement['year_position'] ?? ''));
        if (($name !== '' && $nameMode === 'replace' && $nameSource === '') || ($year !== '' && $yearMode === 'replace' && $yearSource === '') || ($name !== '' && $nameMode === 'add' && $namePosition === '') || ($year !== '' && $yearMode === 'add' && $yearPosition === '')) {
            $this->orderTextMasterErrors[$index] = 'Nhập chữ gốc hoặc vị trí cần thêm trước khi tạo ảnh.';
            return;
        }
        $prompt = $this->loadOrderAmazonPrompt();
        if ($name !== '' || $year !== '') {
            $prompt .= "\nPersonalize the reference artwork with the following customer values. Follow each instruction below, preserving the artwork and its style. Spell each value exactly as provided.";
            if ($name !== '') $prompt .= $nameMode === 'add' ? "\nADD NAME: {$name}\nAdd this text at {$namePosition}; do not remove existing text." : "\nADD NAME: {$name}\nReplace only the exact text ".json_encode($nameSource, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)." with ".json_encode($name, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).".";
            if ($year !== '') $prompt .= $yearMode === 'add' ? "\nADD YEAR: {$year}\nAdd this text at {$yearPosition}; do not remove existing text." : "\nADD YEAR: {$year}\nReplace only the exact text ".json_encode($yearSource, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)." with ".json_encode($year, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).".";
        }

        try {
            $folder = 'order-amazon-paste/'.auth()->id().'/'.$this->orderDraftProduct($row);
            $oldImages = [$this->orderTextMasterImages[$index] ?? null, $this->orderTextTemplateImages[$index] ?? null];
            $newImage = app(ApiKeyImageGenerator::class)->generate(
                user: auth()->user(), providerKey: 'cheapkeyai', imageUri: $source,
                prompt: $prompt, folder: $folder, model: $this->loadOrderAmazonImageModel(), functionKey: 'order-amazon-paste',
            );
            $prefix = '/storage/order-amazon-paste/'.auth()->id().'/';
            if (str_starts_with($newImage, $prefix)) {
                $path = $this->orderDraftImagePath($row, 'master');
                if (Storage::disk('public')->exists($path) && ($this->orderTextMasterImages[$index] ?? null) !== '/storage/'.$path) {
                    $this->deleteOrderDraftImages([$newImage]);
                    $this->orderTextMasterErrors[$index] = 'Tên ảnh đã tồn tại cho đơn khác. Hãy xử lý đơn đó trước để tránh ghi đè.';
                    return;
                }
                if (! Storage::disk('public')->move(substr($newImage, strlen('/storage/')), $path)) {
                    throw new \RuntimeException('Cannot rename generated image.');
                }
                $newImage = '/storage/'.$path;
            }
            if (! $this->deleteOrderDraftImages(array_diff(array_filter($oldImages), [$newImage]))) {
                $this->deleteOrderDraftImages([$newImage]);
                return;
            }
            $this->orderTextMasterImages[$index] = $newImage;
            unset($this->orderTextMasterErrors[$index], $this->orderTextApproved[$index], $this->orderTextMasterDriveLinks[$index], $this->orderTextTemplateImages[$index]);
            $this->resetErrorBag('orderTextMaster');
        } catch (Throwable $exception) {
            report($exception);
            $this->orderTextMasterErrors[$index] = 'Khong the tao anh. Hay kiem tra key CheapKeyAI, anh nguon va thu lai.';
        }
    }

    public function saveOrderTextTemplate(int $index, string $type, string $data): void
    {
        abort_unless(auth()->user()?->canAccessProduct('order-amazon-paste'), 403);
        if (! isset($this->orderTextRows[$index], $this->orderTextMasterImages[$index]) || ! in_array($type, ['glass', 'ceramic'], true)) {
            $this->addError('orderTextMaster', 'Chưa có ảnh Create Master hoặc loại template không hợp lệ.');
            return;
        }
        $templatePath = 'admin/order-amazon-paste/bounds/'.$type.'.png';
        if (! Storage::disk('public')->exists($templatePath)) {
            $this->addError('orderTextMaster', 'Yêu cầu admin thêm template '.ucfirst($type).'!');
            return;
        }
        if (strlen($data) > 24000000 || ! preg_match('/^data:image\/png;base64,([A-Za-z0-9+\/=]+)$/', $data, $match)) {
            $this->addError('orderTextMaster', 'Ảnh template không hợp lệ.');
            return;
        }
        $bytes = base64_decode($match[1], true);
        $size = is_string($bytes) ? @getimagesizefromstring($bytes) : false;
        $templateSize = @getimagesize(Storage::disk('public')->path($templatePath));
        if (! $size || $size[2] !== IMAGETYPE_PNG || ! $templateSize || $size[0] !== $templateSize[0] || $size[1] !== $templateSize[1]) {
            $this->addError('orderTextMaster', 'Ảnh sau khi căn không khớp kích thước template.');
            return;
        }
        $row = $this->buildOrderTextResults()[0][$index];
        $path = $this->orderDraftImagePath($row, 'template', $type);
        if (! Storage::disk('public')->put($path, $bytes)) {
            $this->addError('orderTextMaster', 'Không lưu được ảnh sau khi căn.');
            return;
        }
        $oldTemplate = $this->orderTextTemplateImages[$index] ?? null;
        if ($oldTemplate !== '/storage/'.$path && ! $this->deleteOrderDraftImages([$oldTemplate])) {
            Storage::disk('public')->delete($path);
            return;
        }
        $this->orderTextTemplateImages[$index] = '/storage/'.$path;
        unset($this->orderTextApproved[$index], $this->orderTextMasterDriveLinks[$index]);
        $this->resetErrorBag('orderTextMaster');
        $this->dispatch('order-template-saved', index: $index);
    }
    public function approveOrderTextMaster(int $index): void
    {
        abort_unless(auth()->user()?->canAccessProduct('order-amazon-paste'), 403);
        if (! isset($this->orderTextRows[$index]) || empty($this->orderTextTemplateImages[$index])) {
            $this->addError('orderTextMaster', 'Hãy lưu ảnh Template trước khi duyệt.');
            return;
        }
        $this->validate(['orderTextTrackingFiles.'.$index => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240']]);
        try {
            $row = $this->buildOrderTextResults()[0][$index];
            $image = $this->orderTextTemplateImages[$index];
            if (empty($this->orderTextMasterDriveLinks[$index])) {
                $this->orderTextMasterDriveLinks[$index] = $this->uploadOrderTextImage($image, $row);
            }
            $this->uploadOrderTextTracking($index, $row);
            $this->resetErrorBag('orderTextMaster');
            $this->orderTextApproved[$index] = true;
        } catch (Throwable $exception) {
            report($exception);
            $this->addError('orderTextMaster', 'Không thể upload ảnh hoặc tracking lên Google Drive. Ảnh chưa được duyệt.');
        }
    }

    private function uploadOrderTextImage(string $image, array $row): string
    {
        if (! str_starts_with($image, '/storage/') || str_contains(rawurldecode($image), '..')) {
            throw new \RuntimeException('Invalid generated image path.');
        }
        $relativePath = ltrim(substr($image, strlen('/storage/')), '/');
        if (! Storage::disk('public')->exists($relativePath)) {
            throw new \RuntimeException('Generated image not found.');
        }
        return app(GoogleDriveService::class)->uploadLocalFile(
            Storage::disk('public')->path($relativePath),
            $row['sku'].'-'.$row['id'].'-master.png',
            'image/png',
            null,
            true,
        );
    }

    private function uploadOrderTextTracking(int $index, array $row): void
    {
        $file = $this->orderTextTrackingFiles[$index] ?? null;
        if (! $file || isset($this->orderTextTrackingLinks[$index])) return;
        $this->orderTextTrackingLinks[$index] = app(GoogleDriveService::class)->uploadLocalFile(
            $file->getRealPath(),
            $row['id'].'-tracking-'.bin2hex(random_bytes(8)).'.'.$file->extension(),
            $file->getMimeType(),
            null,
            true,
        );
    }

    public function exportOrderText(): mixed
    {
        abort_unless(auth()->user()?->canAccessProduct('order-amazon-paste'), 403);
        [$rows, $labels] = $this->buildOrderTextResults();
        if ($rows === []) {
            $this->addError('orderTextExport', 'Chưa có đơn hàng để export.');
            return null;
        }
        foreach ($rows as $row) {
            if (! preg_match('/^\d{3}-\d{7}-\d{7}$/', $row['id'])) {
                $this->addError('orderTextExport', 'Có đơn thiếu Order ID hợp lệ. Kiểm tra trước khi export.');
                return null;
            }
        }
        foreach ($rows as $index => $row) {
            if (empty($this->orderTextSkipDesignLink[$index]) && (empty($this->orderTextApproved[$index]) || empty($this->orderTextMasterDriveLinks[$index]))) {
                $this->addError('orderTextExport', 'Cần duyệt đủ ảnh Template hoặc chọn bỏ qua Design Link trước khi export.');
                return null;
            }
        }
        $this->validate(['orderTextTrackingFiles.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240']]);
        foreach ($rows as $index => $row) {
            if (empty($this->orderTextSkipDesignLink[$index]) && ! empty($this->orderTextApproved[$index]) && empty($this->orderTextTemplateImages[$index])) {
                $this->addError('orderTextExport', 'Hãy lưu và duyệt lại ảnh Template trước khi export.');
                return null;
            }
        }
        foreach ($rows as $index => $row) {
            if (empty($this->orderTextSkipDesignLink[$index]) && ! empty($this->orderTextApproved[$index]) && empty($this->orderTextMasterDriveLinks[$index])) {
                try {
                    $this->orderTextMasterDriveLinks[$index] = $this->uploadOrderTextImage($this->orderTextTemplateImages[$index], $row);
                } catch (Throwable $exception) {
                    report($exception);
                    $this->addError('orderTextExport', 'Khong the upload anh API len Google Drive. Don hang chua duoc export.');
                    return null;
                }
            }
            $file = $this->orderTextTrackingFiles[$index] ?? null;
            if (! $file || isset($this->orderTextTrackingLinks[$index])) continue;
            try {
                $this->uploadOrderTextTracking($index, $row);
            } catch (Throwable $exception) {
                report($exception);
                $this->addError('orderTextExport', 'Không thể upload file hoặc cấp quyền xem trên Google Drive. Các đơn chưa được export; vui lòng thử lại.');
                return null;
            }
        }
        $headers = array_merge(['SKU', 'Order ID', 'Order Product ID', 'Qty', 'Design Link', 'Tracking ID', 'TO_NAME', 'TO_COMPANY', 'TO_PHONE', 'TO_ADDRESS1', 'TO_ADDRESS2', 'TO_CITY', 'TO_STATE', 'TO_POSTCODE'], array_values($labels));
        $exportRows = [];
        foreach ($rows as $index => $row) {
            $links = ! empty($this->orderTextApproved[$index]) && ! empty($this->orderTextMasterDriveLinks[$index])
                ? [$this->orderTextMasterDriveLinks[$index]]
                : [];
            $values = array_merge([$row['sku'], $row['id'], (string) ($row['catalogProduct']?->order_product_id ?? ''), $row['quantity'], implode("\n", $links), $this->orderTextTrackingLinks[$index] ?? ''], $row['address']);
            foreach ($labels as $label) $values[] = $row['customizationValues'][$label] ?? '';
            $exportRows[] = ['source' => $row, 'links' => $links, 'values' => $values];
            if ($row['giftBox']) {
                $boxValues = array_fill(0, count($headers), '');
                $boxValues[0] = $row['giftBox']['sku'];
                $boxValues[2] = $row['giftBox']['productId'];
                $exportRows[] = ['source' => $row, 'links' => [], 'values' => $boxValues];
            }
        }
        $xml = '<?xml version="1.0" encoding="UTF-8"?><?mso-application progid="Excel.Sheet"?><Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"><Worksheet ss:Name="Orders"><Table>';
        foreach (array_merge([$headers], array_column($exportRows, 'values')) as $values) {
            $xml .= '<Row>';
            foreach ($values as $value) $xml .= '<Cell><Data ss:Type="String">'.htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</Data></Cell>';
            $xml .= '</Row>';
        }
        $xml .= '</Table></Worksheet></Workbook>';
        \Illuminate\Support\Facades\DB::transaction(function () use ($exportRows): void {
            User::query()->whereKey(auth()->id())->lockForUpdate()->firstOrFail();
            foreach (collect($exportRows)->groupBy(fn ($row) => $row['source']['id']) as $orderId => $orderRows) {
                $history = HistoryOrderReport::query()->firstOrNew(['user_id' => auth()->id(), 'order_id' => $orderId]);
                $previous = is_array($history->report_data) ? $history->report_data : [];
                $first = $orderRows->first();
                $source = $first['source'];
                $values = $first['values'];
                $history->fill([
                    'sku' => $source['sku'], 'quantity' => $source['quantity'],
                    'images_link' => $orderRows->flatMap(fn ($row) => $row['links'])->unique()->values()->all(),
                    'report_data' => array_merge($previous, [
                        'id_order' => $orderId, 'sku' => $source['sku'], 'product_id' => $values[2],
                        'quantity' => $source['quantity'], 'link_design' => $values[4],
                        'tracking_id' => $values[5], 'to_name' => $values[6], 'to_company' => $values[7], 'to_phone' => $values[8],
                        'to_address_1' => $values[9], 'to_address_2' => $values[10], 'to_city' => $values[11],
                        'to_state' => $values[12], 'to_postcode' => $values[13],
                        'fulfillment' => $this->orderTextFbaOnly ? 'FBA' : 'FBM',
                        'export_count' => max(0, (int) ($previous['export_count'] ?? ($history->exists ? 1 : 0))) + 1,
                        'order_text_rows' => $orderRows->map(fn ($row) => ['values' => $row['values'], 'customizations' => $row['source']['customizationValues']])->values()->all(),
                    ]),
                    'ordered_at' => now(),
                ])->save();
            }
        });
        $this->resetErrorBag('orderTextExport');
        return response()->streamDownload(fn () => print $xml, 'order-text-'.now()->format('Ymd-His').'.xls', ['Content-Type' => 'application/vnd.ms-excel; charset=UTF-8']);
    }

    private function buildOrderTextResults(): array
    {
        $orderTextResults = [];
        $orderTextCustomizationLabels = [];
        foreach ($this->orderTextRows as $row) {
            $designs = trim($row['sku']) === '' ? collect() : SkuOrderItem::query()
                ->when(! auth()->user()?->is_admin, fn ($query) => $query->where('user_id', auth()->id()))
                ->whereRaw('LOWER(TRIM(sku)) = ?', [strtolower(trim($row['sku']))])
                ->with(['user:id,name', 'product'])->orderBy('id')->get();
            $item = $designs->pluck('product_id')->unique()->count() === 1 ? $designs->first() : null;
            $productName = (string) ($item?->product?->name ?? '');
            $size = $this->orderTextCustomizationValue('SELECT SIZES', $row['customizations']);
            $catalog = $productName !== '' ? OrderProduct::query()->where('fulfillment_type', $this->orderTextFbaOnly ? 'FBA' : 'FBM')->get() : collect();
            $customizations = [];
            foreach (preg_split('/\R/u', $row['customizations']) ?: [] as $line) {
                if (trim($line) === '') continue;
                [$label, $value] = array_pad(preg_split('/:(?![^()]*\))/', trim($line), 2) ?: [], 2, '');
                $label = trim($label);
                $label = preg_match('/^ADD NAME\s*\(example:.*\)$/i', $label) ? 'ADD NAME' : $label;
                $customizations[$label] = trim($value);
                $orderTextCustomizationLabels[$label] = $label;
            }
            $giftBox = match ($this->orderTextFbaOnly ? '' : strtoupper(trim($customizations['Would you prefer your item to come with a gift box?'] ?? ''))) {
                'BOX' => ['sku' => 'BOX', 'productId' => '59046'],
                'BOX WITH BOW' => ['sku' => 'BOX WITH BOW', 'productId' => '70578'],
                default => null,
            };
            $orderTextResults[] = $row + ['address' => $this->orderTextAddress($row), 'designs' => $designs, 'catalogProduct' => $this->matchSimpleOrderProduct($catalog, $productName, $size), 'customizationValues' => $customizations, 'giftBox' => $giftBox];
        }

        return [$orderTextResults, $orderTextCustomizationLabels];
    }

    private function orderTextAddress(array $row): array
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\R/u', $row['shipTo']) ?: []), fn ($line) => $line !== ''));
        $address = ['', $row['company'], $row['phone'], '', '', '', '', ''];
        if (count($lines) >= 3 && preg_match('/^(.+),\s*([A-Za-z]{2})\s+(\d{5}(?:-\d{4})?)$/', end($lines), $match)) {
            $address = [$lines[0], $row['company'], $row['phone'], $lines[1], implode(', ', array_slice($lines, 2, -1)), trim($match[1]), strtoupper($match[2]), $match[3]];
        }

        return $address;
    }

    private function loadOrderAmazonPrompt(): string
    {
        $productId = Product::query()->where('slug', 'order-amazon-paste')->value('id');
        $prompt = $productId ? ListingPromptOverride::query()->where('product_id', $productId)->where('marketplace', 'order-master')->value('content') : null;

        return trim((string) ($prompt ?: self::DEFAULT_PROMPT));
    }

    private function loadOrderAmazonImageModel(): string
    {
        $productId = Product::query()->where('slug', 'order-amazon-paste')->value('id');
        $model = $productId ? ListingPromptOverride::query()->where('product_id', $productId)->where('marketplace', 'order-model')->value('content') : null;

        return in_array($model, self::IMAGE_MODELS, true) ? $model : (string) config('services.api_key_providers.cheapkeyai.model', 'gpt-image-2');
    }

    private function loadOrderAmazonBoundsTemplates(): array
    {
        $disk = Storage::disk('public'); $templates = [];
        foreach (['glass', 'ceramic'] as $type) {
            $path = 'admin/order-amazon-paste/bounds/'.$type.'.png';
            if ($disk->exists($path)) {
                $templates[$type] = route('image-preview.show', ['path' => '/storage/'.$path, 'v' => $disk->lastModified($path)], false);
            }
        }
        return $templates;
    }

    public function render(): View
    {
        [$orderTextResults, $orderTextCustomizationLabels] = $this->buildOrderTextResults();
        $preview = app(ImageLinkPreviewService::class);
        $orderTextSourcePreviews = collect($orderTextResults)->mapWithKeys(fn (array $row, int $index): array => [$index => $preview->previewUrl($row['designs']->pluck('image_link')->filter()->first())])->all();
        $orderTextMasterPreviews = collect($this->orderTextMasterImages)->mapWithKeys(fn (string $url, int $index): array => [$index => $preview->previewUrl($url)])->all();
        $orderTextTemplatePreviews = collect($this->orderTextTemplateImages)->mapWithKeys(fn (string $url, int $index): array => [$index => $preview->previewUrl($url)])->all();

        $credential = UserApiCredential::query()->where('user_id', auth()->id())->where('provider_key', 'cheapkeyai')->where('is_active', true)->latest('id')->first();
        $cheapKeyAiBalance = $credential ? Cache::remember('order-amazon-cheapkey-balance:'.$credential->id, 60, fn () => app(GlassService::class)->cheapKeyAiBalanceForUser(auth()->user(), 'cheapkeyai')) : null;

        return view('livewire.pages.order.OrderAmazonPaste', ['orderAmazonImageModels' => self::IMAGE_MODELS, 'orderAmazonBoundsTemplates' => $this->orderAmazonBoundsTemplates] + compact('orderTextResults', 'orderTextCustomizationLabels', 'orderTextSourcePreviews', 'orderTextMasterPreviews', 'orderTextTemplatePreviews', 'cheapKeyAiBalance'))->layout('layouts.app');
    }

}



