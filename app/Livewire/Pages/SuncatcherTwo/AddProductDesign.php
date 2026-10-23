<?php

namespace App\Livewire\Pages\SuncatcherTwo;

use Illuminate\Contracts\View\View;

class AddProductDesign extends \App\Livewire\Modals\SuncatcherTwo\AddProductDesign
{
    public function mount(bool $embedded = true, ?string $providerKey = null, ?string $textModel = null, ?string $imageModel = null): void
    {
        parent::mount(true, $providerKey, $textModel, $imageModel);
    }

    public function render(): View
    {
        return view('livewire.modals.suncatcher-two.add-product-design');
    }
}
