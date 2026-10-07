<?php

namespace App\Livewire\Pages\Sticker;

use App\Services\Sticker\StickerService;
use App\Services\Sticker\PsdMockupTemplateService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;
use Livewire\Attributes\Session;
use Livewire\Component;

class ListSticker extends Component
{
    private const PER_PAGE_OPTIONS = [5, 10, 20, 30, 50];

    #[Session(key: 'sticker.per-page')]
    public int $perPage = 5;

    #[Session(key: 'sticker.search')]
    public string $search = '';
    public ?string $selectedAiProvider = null;

    #[Session(key: 'sticker.image-model')]
    public ?string $selectedImageModel = null;

    #[On('product-design-created')]
    #[On('sticker-product-design-updated')]
    public function productDesignCreated(): void
    {
        //
    }

    #[On('sticker-product-design-approval-updated')]
    #[On('sticker-counts-updated')]
    public function productDesignApprovalUpdated(): void
    {
        //
    }

    #[On('sticker-product-design-workflow-updated')]
    public function productDesignWorkflowUpdated(): void
    {
        //
    }

    #[On('psd-mockup-template-updated')]
    public function psdMockupTemplateUpdated(): void
    {
        //
    }

    public function updatedSelectedAiProvider(?string $providerKey): void
    {
        $this->selectedAiProvider = $providerKey;
        $this->selectedImageModel = null;
    }

    #[On('v98store-key-updated')]
    public function v98StoreKeyUpdated(): void
    {
        $this->selectedAiProvider = 'v98store';
        $this->selectedImageModel = null;
    }

    #[On('cheapkeyai-key-updated')]
    public function cheapKeyAiKeyUpdated(): void
    {
        $this->selectedAiProvider = 'cheapkeyai';
        $this->selectedImageModel = null;
    }

    public function updatedPerPage(int|string $perPage): void
    {
        $perPage = (int) $perPage;

        if (! in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $this->perPage = 5;
        }

    }

    public function updatedSearch(string $search): void
    {
        $this->search = trim($search);
        $this->dispatch('sticker-search-updated')->to(StickerStatusPanel::class);
    }

    public function render(): View
    {
        $service = app(StickerService::class);
        $perPage = in_array($this->perPage, self::PER_PAGE_OPTIONS, true) ? $this->perPage : 5;
        $providerOptions = $service->providerOptionsForUser(auth()->user());
        $this->selectedAiProvider = array_key_exists((string) $this->selectedAiProvider, $providerOptions) ? $this->selectedAiProvider : array_key_first($providerOptions);
        $imageModelOptions = $service->imageModelOptionsForProvider($this->selectedAiProvider);
        $this->selectedImageModel = array_key_exists((string) $this->selectedImageModel, $imageModelOptions) ? $this->selectedImageModel : array_key_first($imageModelOptions);
        $cacheKey = auth()->id().':'.($this->selectedAiProvider ?? 'none');
        $v98StoreBalance = Cache::remember("sticker:v98-balance:$cacheKey", 30, fn () => $service->v98StoreBalanceForUser(auth()->user(), $this->selectedAiProvider));
        $cheapKeyAiBalance = Cache::remember("sticker:cheapkey-balance:$cacheKey", 30, fn () => $service->cheapKeyAiBalanceForUser(auth()->user(), $this->selectedAiProvider));

        return view('livewire.pages.sticker.list-sticker', [
            'statusCounts' => Cache::remember('sticker:status-counts:'.auth()->id().':'.sha1(trim($this->search)), 5, fn () => $service->statusCountsForUser(auth()->user(), $this->search)),
            'activePsdTemplateName' => app(PsdMockupTemplateService::class)->activeStickerTemplateForUser(auth()->user())?->name,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'providerOptions' => $providerOptions,
            'imageModelOptions' => $imageModelOptions,
            'selectedAiProvider' => $this->selectedAiProvider,
            'selectedImageModel' => $this->selectedImageModel,
            'v98StoreBalance' => $v98StoreBalance,
            'cheapKeyAiBalance' => $cheapKeyAiBalance,
            'product' => $service->product(),
        ])->layout('layouts.app');
    }
}
