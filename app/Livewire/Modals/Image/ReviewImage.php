<?php

namespace App\Livewire\Modals\Image;

use App\Livewire\Pages\Sticker\ListSticker;
use App\Livewire\Pages\Sticker\StickerStatusPanel;
use App\Models\ProductDesignAsset;
use App\Services\Image\ImageLinkPreviewService;
use App\Services\Logging\ActivityLogService;
use App\Services\Sticker\StickerService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Livewire\Attributes\On;
use Livewire\Component;
use RuntimeException;
use Throwable;

class ReviewImage extends Component
{
    public bool $isOpen = false;

    public ?string $src = null;

    public ?string $original = null;

    public string $title = 'Review image';

    /** @var array<int, array{src: string|null, original: string|null, title?: string|null}> */
    public array $gallery = [];

    public int $currentIndex = 0;

    public ?string $action = null;

    public ?string $productSlug = null;

    public ?int $assetId = null;

    public ?string $keyword = null;

    public string $customPrompt = '';

    /**
     * @var array<int, array{label: string, value: string}>
     */
    public array $listingInfo = [];

    #[On('review-image')]
    public function open(
        string $src,
        ?string $original = null,
        ?string $title = null,
        array $gallery = [],
        int $currentIndex = 0,
        ?string $action = null,
        ?string $productSlug = null,
        ?int $assetId = null,
        ?string $keyword = null,
    ): void
    {
        $this->gallery = $gallery ?: [[
            'src' => $src,
            'original' => $original ?: $src,
            'title' => $title ?: 'Review image',
        ]];
        $this->currentIndex = max(0, min($currentIndex, count($this->gallery) - 1));
        $this->action = $action;
        $this->productSlug = $productSlug;
        $this->assetId = $assetId;
        $this->keyword = $keyword;
        $this->customPrompt = '';
        $this->setCurrentFromGallery();
        $this->loadListingInfo();
        $this->isOpen = true;
    }

    public function previous(): void
    {
        if (count($this->gallery) <= 1) {
            return;
        }

        $this->currentIndex = $this->currentIndex === 0
            ? count($this->gallery) - 1
            : $this->currentIndex - 1;
        $this->setCurrentFromGallery();
    }

    public function next(): void
    {
        if (count($this->gallery) <= 1) {
            return;
        }

        $this->currentIndex = ($this->currentIndex + 1) % count($this->gallery);
        $this->setCurrentFromGallery();
    }

    public function selectAsStickerRedesign(): void
    {
        if ($this->action !== 'sticker-redesign' || ! $this->assetId || ! $this->original) {
            return;
        }

        try {
            app(StickerService::class)->selectRedesign(auth()->user(), $this->assetId, $this->original);
        } catch (RuntimeException $exception) {
            $this->dispatch('toast', type: 'error', title: 'Action failed!', message: $exception->getMessage());

            return;
        }

        $this->dispatch('sticker-product-design-workflow-updated')->to(ListSticker::class);
        $this->dispatch('sticker-product-design-workflow-updated')->to(StickerStatusPanel::class);
        $this->dispatch('toast', type: 'success', title: 'Successfully saved!', message: 'Da chon lai anh Create Master.');
        $this->close();
    }

    public function createStickerItemFromImage(): void
    {
        if ($this->action !== 'sticker-redesign' || ! $this->original) {
            return;
        }

        $this->dispatch('openModal', component: 'modals.sticker.add-product-design', arguments: [
            'keyword' => $this->keyword ?: '',
            'imageLink' => $this->original,
            'sourceAssetId' => $this->assetId,
            'sourceRedesignCandidate' => $this->original,
        ]);
        $this->close();
    }

    public function customizeStickerRedesign(): void
    {
        if ($this->action !== 'sticker-redesign' || ! $this->assetId || ! $this->original) {
            return;
        }

        try {
            $asset = app(StickerService::class)->customizeRedesign(
                auth()->user(),
                $this->assetId,
                $this->original,
                $this->customPrompt,
            );
            app(ActivityLogService::class)->record(
                event: 'sticker.master_customized',
                description: 'User customized Sticker master image from preview.',
                subject: $asset,
                properties: ['item_number' => $asset->item_number, 'redesign' => $asset->redesign],
            );
        } catch (InvalidArgumentException|RuntimeException $exception) {
            $this->dispatch('toast', type: 'error', title: 'Action failed!', message: $exception->getMessage());

            return;
        } catch (Throwable $exception) {
            Log::error('Sticker master customization failed unexpectedly.', [
                'asset_id' => $this->assetId,
                'message' => $exception->getMessage(),
            ]);

            $this->dispatch('toast', type: 'error', title: 'Action failed!', message: 'Loi he thong khi custom anh master. Hay xem log de biet chi tiet.');

            return;
        }

        $previewUrl = app(ImageLinkPreviewService::class)->previewUrl($asset->redesign);
        $nextTitleNumber = count($this->gallery) + 1;
        $this->gallery[] = [
            'src' => $previewUrl,
            'original' => $asset->redesign,
            'title' => 'Create Master '.$nextTitleNumber,
        ];
        $this->currentIndex = count($this->gallery) - 1;
        $this->customPrompt = '';
        $this->setCurrentFromGallery();

        $this->dispatch('sticker-product-design-workflow-updated')->to(ListSticker::class);
        $this->dispatch('sticker-product-design-workflow-updated')->to(StickerStatusPanel::class);
        $this->dispatch('toast', type: 'success', title: 'Successfully saved!', message: 'Da custom anh so 2.');
    }

    public function close(): void
    {
        $this->reset(['isOpen', 'src', 'original', 'gallery', 'currentIndex', 'action', 'productSlug', 'assetId', 'keyword', 'customPrompt', 'listingInfo']);
        $this->title = 'Review image';
    }

    public function render(): View
    {
        return view('livewire.modals.image.review-image');
    }

    private function setCurrentFromGallery(): void
    {
        $current = $this->gallery[$this->currentIndex] ?? [];

        $this->src = is_string($current['src'] ?? null) ? $current['src'] : null;
        $this->original = is_string($current['original'] ?? null) ? $current['original'] : $this->src;
        $this->title = is_string($current['title'] ?? null) && $current['title'] !== ''
            ? $current['title']
            : 'Review image';
    }

    private function loadListingInfo(): void
    {
        $this->listingInfo = [];

        if (! $this->assetId) {
            return;
        }

        $asset = ProductDesignAsset::query()
            ->select([
                'id',
                'user_id',
                'is_approved',
                'title',
                'description',
                'bullet_point_1',
                'bullet_point_2',
                'bullet_point_3',
                'bullet_point_4',
                'bullet_point_5',
                'generic_keyword',
                'tags',
            ])
            ->when(! auth()->user()->is_admin, fn ($query) => $query->where('user_id', auth()->id()))
            ->find($this->assetId);

        if (! $asset || ! $asset->is_approved) {
            return;
        }

        $fields = [
            'title' => 'Title',
            'description' => 'Description',
            'bullet_point_1' => 'Bullet Point 1',
            'bullet_point_2' => 'Bullet Point 2',
            'bullet_point_3' => 'Bullet Point 3',
            'bullet_point_4' => 'Bullet Point 4',
            'bullet_point_5' => 'Bullet Point 5',
            'generic_keyword' => 'Generic Keyword',
            'tags' => 'Tags',
        ];

        foreach ($fields as $field => $label) {
            $value = $asset->getAttribute($field);

            if (is_string($value) && trim($value) !== '') {
                $this->listingInfo[] = [
                    'label' => $label,
                    'value' => trim($value),
                ];
            }
        }
    }
}
