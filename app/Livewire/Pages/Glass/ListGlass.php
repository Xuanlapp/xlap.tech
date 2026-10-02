<?php

namespace App\Livewire\Pages\Glass;

use App\Services\Glass\GlassService;
use App\Services\Glass\PsdMockupTemplateService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;
use Livewire\Attributes\Session;
use Livewire\Component;

class ListGlass extends Component
{
    private const PER_PAGE_OPTIONS = [5, 10, 20, 30, 50];

    #[Session(key: 'glass.per-page')]
    public int $perPage = 5;

    #[Session(key: 'glass.search')]
    public string $search = '';

    #[Session(key: 'glass.ai-provider')]
    public ?string $selectedAiProvider = null;

    #[Session(key: 'glass.image-model')]
    public ?string $selectedImageModel = null;

    #[On('product-design-created')]
    #[On('glass-product-design-updated')]
    public function productDesignCreated(): void
    {
        //
    }

    #[On('glass-product-design-approval-updated')]
    #[On('glass-counts-updated')]
    public function productDesignApprovalUpdated(): void
    {
        //
    }

    #[On('glass-product-design-workflow-updated')]
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

        if ($providerKey) {
            auth()->user()->setDefaultAiProvider($providerKey);
        }
    }

    #[On('v98store-key-updated')]
    public function v98StoreKeyUpdated(): void
    {
        $this->selectedAiProvider = 'v98store';
        $this->selectedImageModel = null;
        auth()->user()->setDefaultAiProvider('v98store');
    }

    #[On('cheapkeyai-key-updated')]
    public function cheapKeyAiKeyUpdated(): void
    {
        $this->selectedAiProvider = 'cheapkeyai';
        $this->selectedImageModel = null;
        auth()->user()->setDefaultAiProvider('cheapkeyai');
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
        $this->dispatch('glass-search-updated')->to(GlassStatusPanel::class);
    }

    public function render(): View
    {
        $service = app(GlassService::class);
        $perPage = in_array($this->perPage, self::PER_PAGE_OPTIONS, true) ? $this->perPage : 5;
        $providerOptions = $service->providerOptionsForUser(auth()->user());
        if (! array_key_exists((string) $this->selectedAiProvider, $providerOptions)) {
            $preferredProvider = auth()->user()->activeAiProviderKey();
            $this->selectedAiProvider = array_key_exists((string) $preferredProvider, $providerOptions)
                ? $preferredProvider
                : array_key_first($providerOptions);
        }
        $imageModelOptions = $service->imageModelOptionsForProvider($this->selectedAiProvider);
        $this->selectedImageModel = array_key_exists((string) $this->selectedImageModel, $imageModelOptions) ? $this->selectedImageModel : array_key_first($imageModelOptions);
        $userId = auth()->id();
        $v98StoreBalance = Cache::remember("glass:v98-balance:{$userId}:{$this->selectedAiProvider}", 60, fn () => $service->v98StoreBalanceForUser(auth()->user(), $this->selectedAiProvider));
        $cheapKeyAiBalance = Cache::remember("glass:cheapkey-balance:{$userId}:{$this->selectedAiProvider}", 60, fn () => $service->cheapKeyAiBalanceForUser(auth()->user(), $this->selectedAiProvider));

        return view('livewire.pages.glass.list-glass', [
            'statusCounts' => Cache::remember(
                'glass:status-counts:'.auth()->id().':'.sha1(trim($this->search)),
                now()->addSeconds(5),
                fn () => $service->statusCountsForUser(auth()->user(), $this->search),
            ),
            'activePsdTemplateName' => app(PsdMockupTemplateService::class)->activeGlassTemplateForUser(auth()->user())?->name,
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
