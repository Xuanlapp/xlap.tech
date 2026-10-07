<?php

namespace App\Livewire\Pages\Ceramic;

use App\Services\Ceramic\CeramicService;
use App\Services\Ceramic\PsdMockupTemplateService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;
use Livewire\Attributes\Session;
use Livewire\Component;

class ListCeramic extends Component
{
    private const PER_PAGE_OPTIONS = [5, 10, 20, 30, 50];

    #[Session(key: 'ceramic.per-page')]
    public int $perPage = 5;

    #[Session(key: 'ceramic.search')]
    public string $search = '';
    public ?string $selectedAiProvider = null;

    #[Session(key: 'ceramic.image-model')]
    public ?string $selectedImageModel = null;

    #[On('product-design-created')]
    #[On('ceramic-product-design-updated')]
    public function productDesignCreated(): void
    {
        //
    }

    #[On('ceramic-product-design-approval-updated')]
    #[On('ceramic-counts-updated')]
    public function productDesignApprovalUpdated(): void
    {
        //
    }

    #[On('ceramic-product-design-workflow-updated')]
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
        $this->dispatch('ceramic-search-updated')->to(CeramicStatusPanel::class);
    }

    public function render(): View
    {
        $service = app(CeramicService::class);
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
        $v98StoreBalance = Cache::remember("ceramic:v98-balance:{$userId}:{$this->selectedAiProvider}", 60, fn () => $service->v98StoreBalanceForUser(auth()->user(), $this->selectedAiProvider));
        $cheapKeyAiBalance = Cache::remember("ceramic:cheapkey-balance:{$userId}:{$this->selectedAiProvider}", 60, fn () => $service->cheapKeyAiBalanceForUser(auth()->user(), $this->selectedAiProvider));

        return view('livewire.pages.ceramic.list-ceramic', [
            'statusCounts' => Cache::remember(
                'ceramic:status-counts:'.auth()->id().':'.sha1(trim($this->search)),
                now()->addSeconds(5),
                fn () => $service->statusCountsForUser(auth()->user(), $this->search),
            ),
            'activePsdTemplateName' => app(PsdMockupTemplateService::class)->activeCeramicTemplateForUser(auth()->user())?->name,
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


