<?php

namespace App\Livewire\Pages\Decal;

use App\Services\Decal\DecalService;
use App\Services\Decal\PsdMockupTemplateService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;
use Livewire\Attributes\Session;
use Livewire\Component;

class ListDecal extends Component
{
    private const PER_PAGE_OPTIONS = [5, 10, 20, 30, 50];

    #[Session(key: 'decal.per-page')]
    public int $perPage = 5;

    #[Session(key: 'decal.search')]
    public string $search = '';
    public ?string $selectedAiProvider = null;

    #[Session(key: 'decal.image-model')]
    public ?string $selectedImageModel = null;

    #[On('product-design-created')]
    #[On('decal-product-design-updated')]
    public function productDesignCreated(): void
    {
        //
    }

    #[On('decal-product-design-approval-updated')]
    #[On('decal-counts-updated')]
    public function productDesignApprovalUpdated(): void
    {
        //
    }

    #[On('decal-product-design-workflow-updated')]
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
        $this->dispatch('decal-search-updated')->to(DecalStatusPanel::class);
    }

    public function render(): View
    {
        $service = app(DecalService::class);
        $perPage = in_array($this->perPage, self::PER_PAGE_OPTIONS, true) ? $this->perPage : 5;
        $providerOptions = $service->providerOptionsForUser(auth()->user());
        $this->selectedAiProvider = array_key_exists((string) $this->selectedAiProvider, $providerOptions) ? $this->selectedAiProvider : array_key_first($providerOptions);
        $imageModelOptions = $service->imageModelOptionsForProvider($this->selectedAiProvider);
        $this->selectedImageModel = array_key_exists((string) $this->selectedImageModel, $imageModelOptions) ? $this->selectedImageModel : array_key_first($imageModelOptions);
        $cacheKey = auth()->id().':'.($this->selectedAiProvider ?? 'none');
        $v98StoreBalance = Cache::remember("decal:v98-balance:$cacheKey", 30, fn () => $service->v98StoreBalanceForUser(auth()->user(), $this->selectedAiProvider));
        $cheapKeyAiBalance = Cache::remember("decal:cheapkey-balance:$cacheKey", 30, fn () => $service->cheapKeyAiBalanceForUser(auth()->user(), $this->selectedAiProvider));

        return view('livewire.pages.decal.list-decal', [
            'statusCounts' => Cache::remember('decal:status-counts:'.auth()->id().':'.sha1(trim($this->search)), 5, fn () => $service->statusCountsForUser(auth()->user(), $this->search)),
            'activePsdTemplateName' => app(PsdMockupTemplateService::class)->activeDecalTemplateForUser(auth()->user())?->name,
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
