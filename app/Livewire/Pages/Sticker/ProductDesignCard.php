<?php

namespace App\Livewire\Pages\Sticker;

use App\Models\ProductDesignAsset;
use App\Services\ImageLinkPreviewService;
use App\Services\StickerService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use RuntimeException;

class ProductDesignCard extends Component
{
    public int $assetId;

    public ?string $statusMessage = null;

    public ?string $errorMessage = null;

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

        $this->statusMessage = 'Da cap nhat item.';
        $this->errorMessage = null;
    }

    public function generateRedesign(): void
    {
        try {
            app(StickerService::class)->generateRedesign(auth()->user(), $this->assetId);

            $this->statusMessage = 'Da tao anh redesign.';
            $this->errorMessage = null;
        } catch (RuntimeException $exception) {
            $this->errorMessage = $exception->getMessage();
        }
    }

    public function generateFinalImages(): void
    {
        try {
            app(StickerService::class)->generateFinalImages(auth()->user(), $this->assetId);

            $this->statusMessage = 'Da tao anh bang prompt 2 va prompt 3.';
            $this->errorMessage = null;
        } catch (RuntimeException $exception) {
            $this->errorMessage = $exception->getMessage();
        }
    }

    public function render(): View
    {
        $asset = app(StickerService::class)->assetForUser(auth()->user(), $this->assetId);
        $this->appendPreviewUrls($asset);

        return view('livewire.pages.sticker.product-design-card', [
            'asset' => $asset,
        ]);
    }

    private function appendPreviewUrls(ProductDesignAsset $asset): void
    {
        $imagePreview = app(ImageLinkPreviewService::class);

        $asset->setAttribute('image_preview_url', $imagePreview->previewUrl($asset->image_link));
        $asset->setAttribute('redesign_preview_url', $imagePreview->previewUrl($asset->redesign));
        $asset->setAttribute('mockup1_preview_url', $imagePreview->previewUrl($asset->mockup1));
        $asset->setAttribute('mockup2_preview_url', $imagePreview->previewUrl($asset->mockup2));
    }
}
