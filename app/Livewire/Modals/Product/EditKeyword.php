<?php

namespace App\Livewire\Modals\Product;

use App\Livewire\Pages\Glass\GlassStatusPanel;
use App\Livewire\Pages\Glass\ListGlass;
use App\Livewire\Pages\OrnamentAmazonTwo\ListOrnamentAmazonTwo;
use App\Livewire\Pages\OrnamentAmazonTwo\OrnamentAmazonTwoStatusPanel;
use App\Livewire\Pages\OrnamentEtsy\ListOrnamentEtsy;
use App\Livewire\Pages\OrnamentEtsy\OrnamentEtsyStatusPanel;
use App\Livewire\Pages\Sticker\ListSticker;
use App\Livewire\Pages\Sticker\StickerStatusPanel;
use App\Livewire\Pages\Decal\ListDecal;
use App\Livewire\Pages\Decal\DecalStatusPanel;
use App\Livewire\Pages\Suncatcher\ListSuncatcher;
use App\Livewire\Pages\Suncatcher\SuncatcherStatusPanel;
use App\Services\Glass\GlassService;
use App\Services\Logging\ActivityLogService;
use App\Services\OrnamentAmazonTwo\OrnamentAmazonTwoService;
use App\Services\OrnamentEtsy\OrnamentEtsyService;
use App\Services\Sticker\StickerService;
use App\Services\Decal\DecalService;
use App\Services\Suncatcher\SuncatcherService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class EditKeyword extends Component
{
    public bool $isOpen = false;
    public ?int $assetId = null;
    public string $productSlug = '';
    public string $keyword = '';

    #[On('openModal')]
    public function openModal(string $component, array $arguments = []): void
    {
        if ($component !== 'modals.product.edit-keyword') {
            return;
        }

        $assetId = (int) ($arguments['assetId'] ?? 0);
        $productSlug = trim((string) ($arguments['productSlug'] ?? ''));
        if ($assetId < 1 || ! array_key_exists($productSlug, $this->serviceMap())) {
            return;
        }

        $asset = app($this->serviceMap()[$productSlug])->assetForUser(auth()->user(), $assetId);
        if ($asset->is_approved) {
            $this->dispatch('toast', type: 'error', title: 'Action failed!', message: 'Item da duyet. Hay bo duyet truoc khi edit keyword.');
            return;
        }

        $this->resetValidation();
        $this->assetId = $asset->id;
        $this->productSlug = $productSlug;
        $this->keyword = (string) $asset->keyword;
        $this->isOpen = true;
    }

    public function save(): void
    {
        $validated = $this->validate(['keyword' => ['required', 'string', 'max:255']]);
        if (! $this->assetId || ! array_key_exists($this->productSlug, $this->serviceMap())) {
            return;
        }

        $service = app($this->serviceMap()[$this->productSlug]);
        $service->updateKeyword(auth()->user(), $this->assetId, $validated['keyword']);
        $asset = $service->assetForUser(auth()->user(), $this->assetId);

        app(ActivityLogService::class)->record(
            event: $this->productSlug.'.keyword_updated',
            description: 'User updated an unapproved product keyword.',
            subject: $asset,
            properties: ['product_slug' => $this->productSlug, 'keyword' => $asset->keyword],
        );

        $this->dispatchProductRefresh();
        $this->dispatch('toast', type: 'success', title: 'Successfully saved!', message: 'Da cap nhat keyword.');
        $this->close();
    }

    public function close(): void
    {
        $this->resetValidation();
        $this->reset(['isOpen', 'assetId', 'productSlug', 'keyword']);
    }

    public function render(): View
    {
        return view('livewire.modals.product.edit-keyword');
    }

    private function serviceMap(): array
    {
        return [
            'glass' => GlassService::class,
            'sticker' => StickerService::class,
            'decal' => DecalService::class,
            'ornament-etsy' => OrnamentEtsyService::class,
            'ornament-amazon-2' => OrnamentAmazonTwoService::class,
            'suncatcher' => SuncatcherService::class,
        ];
    }

    private function dispatchProductRefresh(): void
    {
        match ($this->productSlug) {
            'glass' => [$this->dispatch('glass-product-design-updated')->to(ListGlass::class), $this->dispatch('glass-product-design-updated')->to(GlassStatusPanel::class)],
            'sticker' => [$this->dispatch('sticker-product-design-updated')->to(ListSticker::class), $this->dispatch('sticker-product-design-updated')->to(StickerStatusPanel::class)],
            'decal' => [$this->dispatch('decal-product-design-updated')->to(ListDecal::class), $this->dispatch('decal-product-design-updated')->to(DecalStatusPanel::class)],
            'ornament-etsy' => [$this->dispatch('ornament-etsy-product-design-updated')->to(ListOrnamentEtsy::class), $this->dispatch('ornament-etsy-product-design-updated')->to(OrnamentEtsyStatusPanel::class)],
            'ornament-amazon-2' => [$this->dispatch('ornament-amazon-two-product-design-updated')->to(ListOrnamentAmazonTwo::class), $this->dispatch('ornament-amazon-two-product-design-updated')->to(OrnamentAmazonTwoStatusPanel::class)],
            'suncatcher' => [$this->dispatch('suncatcher-product-design-updated')->to(ListSuncatcher::class), $this->dispatch('suncatcher-product-design-updated')->to(SuncatcherStatusPanel::class)],
            default => null,
        };
    }
}
