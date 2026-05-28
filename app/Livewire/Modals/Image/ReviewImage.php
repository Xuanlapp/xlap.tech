<?php

namespace App\Livewire\Modals\Image;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class ReviewImage extends Component
{
    public bool $isOpen = false;

    public ?string $src = null;

    public ?string $original = null;

    public string $title = 'Review image';

    #[On('review-image')]
    public function open(string $src, ?string $original = null, ?string $title = null): void
    {
        $this->src = $src;
        $this->original = $original ?: $src;
        $this->title = $title ?: 'Review image';
        $this->isOpen = true;
    }

    public function close(): void
    {
        $this->reset(['isOpen', 'src', 'original']);
        $this->title = 'Review image';
    }

    public function render(): View
    {
        return view('livewire.modals.image.review-image');
    }
}
