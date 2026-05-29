<?php

namespace App\Livewire\Pages\Sticker;

use App\Models\ProductDesignAsset;
use App\Services\Image\ImageLinkPreviewService;
use App\Services\Sticker\PsdMockupTemplateService;
use App\Services\Sticker\StickerService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use RuntimeException;

class ProductDesignCard extends Component
{
    public int $assetId;

    public function mount(int $assetId): void
    {
        $this->assetId = $assetId;
    }

    #[On('sticker-product-design-updated')]
    public function refreshWhenUpdated(int $assetId): void
    {
        if ($assetId !== $this->assetId) {
            return;
        }
    }

    public function generateRedesign(): void
    {
        try {
            app(StickerService::class)->generateRedesign(auth()->user(), $this->assetId);

            $this->dispatch('toast', type: 'success', title: 'Successfully saved!', message: 'Da tao anh master.');
        } catch (RuntimeException $exception) {
            $this->dispatch('toast', type: 'error', title: 'Action failed!', message: $exception->getMessage());
        }
    }

    public function generateFinalImages(): void
    {
        try {
            app(StickerService::class)->generateFinalImages(auth()->user(), $this->assetId);

            $this->dispatch('toast', type: 'success', title: 'Successfully saved!', message: 'Da tao anh lifestyle va mockup.');
        } catch (RuntimeException $exception) {
            $this->dispatch('toast', type: 'error', title: 'Action failed!', message: $exception->getMessage());
        }
    }

    public function generatePsdMockups(): void
    {
        try {
            app(StickerService::class)->generatePsdMockups(auth()->user(), $this->assetId);

            $this->dispatch('toast', type: 'success', title: 'Successfully saved!', message: 'Da render PSD mockup.');
        } catch (RuntimeException $exception) {
            $this->dispatch('toast', type: 'error', title: 'Action failed!', message: $exception->getMessage());
        }
    }

    #[On('psd-mockup-template-updated')]
    public function refreshWhenPsdTemplateUpdated(): void
    {
        //
    }

    public function render(): View
    {
        $asset = app(StickerService::class)->assetForUser(auth()->user(), $this->assetId);
        $this->appendPreviewUrls($asset);

        return view('livewire.pages.sticker.product-design-card', [
            'asset' => $asset,
            'activePsdTemplate' => app(PsdMockupTemplateService::class)->activeStickerTemplateForUser(auth()->user()),
        ]);
    }

    private function appendPreviewUrls(ProductDesignAsset $asset): void
    {
        $imagePreview = app(ImageLinkPreviewService::class);

        $asset->setAttribute('image_preview_url', $imagePreview->previewUrl($asset->image_link));
        $asset->setAttribute('redesign_preview_url', $imagePreview->previewUrl($asset->redesign));
        $asset->setAttribute('mockup1_preview_url', $imagePreview->previewUrl($asset->mockup1));
        $asset->setAttribute('mockup2_preview_url', $imagePreview->previewUrl($asset->mockup2));

        for ($slot = 3; $slot <= 11; $slot++) {
            $asset->setAttribute("mockup{$slot}_preview_url", $imagePreview->previewUrl($asset->{"mockup{$slot}"}));
        }
    }
}
