<?php

namespace App\Livewire\Pages\Sticker;

use App\Services\Sticker\StickerService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class ListSticker extends Component
{
    #[On('product-design-created')]
    public function productDesignCreated(): void
    {
        //
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
