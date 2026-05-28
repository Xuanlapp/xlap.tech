<?php

namespace App\Livewire\Pages\Sticker;

use App\Services\StickerService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class ListSticker extends Component
{
    public ?string $statusMessage = null;

    public ?string $errorMessage = null;

    #[On('product-design-created')]
    public function productDesignCreated(): void
    {
        $this->statusMessage = 'Da them item Sticker.';
        $this->errorMessage = null;
    }

    public function render(): View
    {
        $service = app(StickerService::class);

        return view('livewire.pages.sticker.list-sticker', [
            'assets' => $service->assetsForUser(auth()->user()),
            'product' => $service->product(),
        ])->layout('layouts.app');
    }
}
