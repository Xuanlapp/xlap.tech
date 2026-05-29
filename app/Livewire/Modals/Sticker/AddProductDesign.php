<?php

namespace App\Livewire\Modals\Sticker;

use App\Livewire\Pages\Sticker\ListSticker;
use App\Services\Image\ImageLinkPreviewService;
use App\Services\Sticker\StickerService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class AddProductDesign extends Component
{
    public bool $isOpen = false;

    public string $keyword = '';

    public string $imageLink = '';

    public ?bool $isImageLink = null;

    /**
     * Open this modal through the shared modal event used by product pages.
     */
    #[On('openModal')]
    public function openModal(string $component, array $arguments = []): void
    {
        if ($component !== 'modals.sticker.add-product-design') {
            return;
        }

        $this->open();
    }

    #[On('open-add-product-design')]
    public function open(): void
    {
        $this->resetValidation();
        $this->reset(['keyword', 'imageLink', 'isImageLink']);
        $this->isOpen = true;
    }

    public function close(): void
    {
        $this->resetValidation();
        $this->reset(['isOpen', 'keyword', 'imageLink', 'isImageLink']);
    }

    public function updatedImageLink(): void
    {
        $this->isImageLink = $this->imageLink === ''
            ? null
            : $this->looksLikeImageUrl($this->imageLink);
    }

    public function save(): void
    {
        $validated = $this->validate([
            'keyword' => ['required', 'string', 'max:255'],
            'imageLink' => ['required', 'url', 'max:1000', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! is_string($value) || ! app(ImageLinkPreviewService::class)->looksLikeImageUrl($value)) {
                    $fail('Link này chưa giống link ảnh.');
                }
            }],
        ]);

        app(StickerService::class)->createAsset(auth()->user(), $validated['keyword'], $validated['imageLink']);

        $this->dispatch('product-design-created')->to(ListSticker::class);
        $this->dispatch('toast', type: 'success', title: 'Successfully saved!', message: 'Da them item Sticker moi.');
        $this->close();
    }

    public function render(): View
    {
        return view('livewire.modals.sticker.add-product-design');
    }

    private function looksLikeImageUrl(string $url): bool
    {
        return app(ImageLinkPreviewService::class)->looksLikeImageUrl($url);
    }
}
