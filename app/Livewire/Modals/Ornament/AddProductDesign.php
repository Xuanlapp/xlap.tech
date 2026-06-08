<?php

namespace App\Livewire\Modals\Ornament;

use App\Livewire\Pages\Ornament\ListOrnament;
use App\Livewire\Pages\Ornament\OrnamentStatusPanel;
use App\Services\Image\ImageLinkPreviewService;
use App\Services\Ornament\OrnamentService;
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

    /**
     * Open this modal through the shared modal event used by product pages.
     */
    #[On('openModal')]
    public function openModal(string $component, array $arguments = []): void
    {
        if ($component !== 'modals.ornament.add-product-design') {
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
            'keyword' => ['required', 'string', 'max:255', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! is_string($value) || ! Str::contains(Str::lower($value), 'ornament')) {
                    $fail("Keyword phai chua tu 'ornament' cho trang Ornament.");
                }
            }],
            'imageLink' => ['required', 'url', 'max:1000', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! is_string($value) || ! app(ImageLinkPreviewService::class)->looksLikeImageUrl($value)) {
                    $fail('Link này chưa giống link ảnh.');
                }
            }],
        ]);

        app(OrnamentService::class)->createAsset(auth()->user(), $validated['keyword'], $validated['imageLink']);

        $this->dispatch('product-design-created')->to(ListOrnament::class);
        $this->dispatch('product-design-created')->to(OrnamentStatusPanel::class);
        $this->dispatch('toast', type: 'success', title: 'Successfully saved!', message: 'Da them item Ornament moi.');
        $this->close();
    }

    public function render(): View
    {
        return view('livewire.modals.ornament.add-product-design');
    }

    private function looksLikeImageUrl(string $url): bool
    {
        return app(ImageLinkPreviewService::class)->looksLikeImageUrl($url);
    }
}
