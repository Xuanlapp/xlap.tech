<?php

namespace App\Livewire\Modals\OrnamentEtsy;

use App\Livewire\Pages\OrnamentEtsy\ListOrnamentEtsy;
use App\Livewire\Pages\OrnamentEtsy\OrnamentEtsyStatusPanel;
use App\Services\Image\ImageLinkPreviewService;
use App\Services\OrnamentEtsy\OrnamentEtsyService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;

class AddProductDesign extends Component
{
    public bool $isOpen = false;

    public string $keyword = '';

    public string $imageLink = '';

    public ?bool $isImageLink = null;

    public ?string $imagePreviewUrl = null;

    /**
     * Open this modal through the shared modal event used by product pages.
     */
    #[On('openModal')]
    public function openModal(string $component, array $arguments = []): void
    {
        if ($component !== 'modals.ornament-etsy.add-product-design') {
            return;
        }

        $this->open(
            is_string($arguments['keyword'] ?? null) ? $arguments['keyword'] : '',
            is_string($arguments['imageLink'] ?? null) ? $arguments['imageLink'] : '',
        );
    }

    #[On('open-add-product-design')]
    public function open(string $keyword = '', string $imageLink = ''): void
    {
        $this->resetValidation();
        $this->reset(['keyword', 'imageLink', 'isImageLink', 'imagePreviewUrl']);
        $this->keyword = $keyword;
        $this->imageLink = $imageLink;
        $this->refreshImageState();
        $this->isOpen = true;
    }

    public function close(): void
    {
        $this->resetValidation();
        $this->reset(['isOpen', 'keyword', 'imageLink', 'isImageLink', 'imagePreviewUrl']);
    }

    public function updatedImageLink(): void
    {
        $this->refreshImageState();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'keyword' => ['required', 'string', 'max:255', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! is_string($value) || ! Str::contains(Str::lower($value), 'ornament')) {
                    $fail("Keyword phai chua tu 'ornament' cho trang Ornament Etsy.");
                }
            }],
            'imageLink' => ['required', 'string', 'max:1000', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! is_string($value) || ! app(ImageLinkPreviewService::class)->looksLikeImageUrl($value)) {
                    $fail('Link nay chua giong link anh.');
                }
            }],
        ]);

        app(OrnamentEtsyService::class)->createAsset(auth()->user(), $validated['keyword'], $validated['imageLink']);

        $this->dispatch('ornament-etsy-product-design-created')->to(ListOrnamentEtsy::class);
        $this->dispatch('ornament-etsy-product-design-created')->to(OrnamentEtsyStatusPanel::class);
        $this->dispatch('toast', type: 'success', title: 'Successfully saved!', message: 'Da them item Ornament Etsy moi.');
        $this->close();
    }

    public function render(): View
    {
        return view('livewire.modals.ornament-etsy.add-product-design');
    }

    private function refreshImageState(): void
    {
        $this->isImageLink = $this->imageLink === ''
            ? null
            : app(ImageLinkPreviewService::class)->looksLikeImageUrl($this->imageLink);
        $this->imagePreviewUrl = $this->isImageLink
            ? app(ImageLinkPreviewService::class)->previewUrl($this->imageLink)
            : null;
    }
}
