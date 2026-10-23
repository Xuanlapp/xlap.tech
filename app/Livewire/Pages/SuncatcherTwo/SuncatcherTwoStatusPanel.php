<?php

namespace App\Livewire\Pages\SuncatcherTwo;

use App\Models\DataSuncatcherTwo;
use App\Models\SuncatcherTwoDraft;
use App\Services\SuncatcherTwo\SuncatcherTwoService;
use App\Services\SuncatcherTwo\PsdMockupTemplateService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class SuncatcherTwoStatusPanel extends Component
{
    use WithPagination;

    private const STATUS_OPTIONS = ['all', 'unapproved', 'approved', 'no_mockup'];

    public string $status;

    public int $perPage;

    public ?string $activePsdTemplateName = null;

    public ?string $providerKey = null;

    public ?string $imageModel = null;

    public ?string $textModel = null;

    /**
     * @var array{all?: int, unapproved?: int, approved?: int, no_mockup?: int}
     */
    public array $statusCounts = [];

    /**
     * @var array<int, int>
     */
    public array $hiddenAssetIds = [];

    /**
     * @param array{all?: int, unapproved?: int, approved?: int, no_mockup?: int} $statusCounts
     */
    public function mount(
        string $status,
        int $perPage,
        ?string $activePsdTemplateName = null,
        ?string $providerKey = null,
        ?string $imageModel = null,
        ?string $textModel = null,
        array $statusCounts = [],
    ): void {
        $this->status = in_array($status, self::STATUS_OPTIONS, true) ? $status : 'all';
        $this->perPage = $perPage;
        $this->activePsdTemplateName = $activePsdTemplateName;
        $this->providerKey = $providerKey;
        $this->imageModel = $imageModel;
        $this->textModel = $textModel;
        $this->statusCounts = $statusCounts;
    }


    public function updatedPerPage(): void
    {
        $this->resetPage($this->pageName());
    }

    public function clearAllUnapproved(): void
    {
        $count = app(SuncatcherTwoService::class)->deleteAllUnapprovedAssets(auth()->user());
        SuncatcherTwoDraft::query()->where('user_id', auth()->id())->delete();
        $this->dispatch('suncatcher-two-draft-clear');
        $this->dispatch('product-design-created');
        $this->dispatch('toast', type: 'success', title: 'Da xoa item chua duyet', message: "Da xoa {$count} item chua duyet.");
    }

    public function setActiveStatus(string $status): void
    {
        if (! in_array($status, self::STATUS_OPTIONS, true)) {
            return;
        }

        if ($this->status === $status) {
            return;
        }

        $this->status = $status;
        $this->resetPage($this->pageName());
        $this->dispatch('suncatcher-two-active-status-changed', status: $status);
    }
    #[On('suncatcher-two-tab-changed')]
    public function resetPageWhenTabChanges(string $tab): void
    {
        if ($tab !== $this->status) {
            return;
        }

        $this->resetPage($this->pageName());
    }

    #[On('product-design-created')]
    #[On('suncatcher-two-product-design-updated')]
    #[On('suncatcher-two-product-design-approval-updated')]
    #[On('suncatcher-two-product-design-workflow-updated')]
    public function refreshAssets(): void
    {
        //
    }

    public function pollRunningAssets(): void
    {
        $this->refreshRunningCardsFromDatabase();
    }

    #[On('psd-mockup-template-updated')]
    public function refreshPsdTemplate(): void
    {
        $this->activePsdTemplateName = app(PsdMockupTemplateService::class)
            ->activeSuncatcherTwoTemplateForUser(auth()->user())?->name;
    }


    #[On('product-design-hide-now')]
    public function hideAssetNow(int $assetId, string $productSlug): void
    {
        if ($productSlug !== 'suncatcher-two') {
            return;
        }

        if (! in_array($assetId, $this->hiddenAssetIds, true)) {
            $this->hiddenAssetIds[] = $assetId;
        }
    }

    public function placeholder(): View
    {
        return view('livewire.pages.suncatcher-two.suncatcher-two-status-panel-placeholder');
    }

    public function render(): View
    {
        $this->statusCounts = app(SuncatcherTwoService::class)->statusCountsForUser(auth()->user());

        $assets = app(SuncatcherTwoService::class)->paginatedAssetsForUser(
            auth()->user(),
            $this->perPage,
            $this->status,
            $this->pageName(),
        );

        if ($this->hiddenAssetIds !== []) {
            $assets->setCollection(
                $assets->getCollection()->reject(fn ($asset) => in_array($asset->id, $this->hiddenAssetIds, true))->values()
            );
        }

        return view('livewire.pages.suncatcher-two.suncatcher-two-status-panel', [
            'assets' => $assets,
            'pageName' => $this->pageName(),
        ]);
    }

    private function pageName(): string
    {
        return "suncatcher-two_{$this->status}_page";
    }

    private function refreshRunningCardsFromDatabase(): void
    {
        if (! Schema::hasTable('data_ornament_amazon')) {
            return;
        }

        $this->statusCounts = app(SuncatcherTwoService::class)->statusCountsForUser(auth()->user());

        $assets = app(SuncatcherTwoService::class)->paginatedAssetsForUser(
            auth()->user(),
            $this->perPage,
            $this->status,
            $this->pageName(),
        );

        $assetIds = $assets->getCollection()
            ->pluck('id')
            ->filter(fn ($id): bool => is_numeric($id))
            ->map(fn ($id): int => (int) $id)
            ->values();

        if ($assetIds->isEmpty()) {
            return;
        }

        $runningAssetIds = DataSuncatcherTwo::query()
            ->where('product_slug', 'suncatcher-two')
            ->whereIn('product_design_asset_id', $assetIds->all())
            ->whereIn('workflow_status', ['running', 'waiting'])
            ->pluck('product_design_asset_id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $service = app(SuncatcherTwoService::class);

        foreach ($runningAssetIds as $assetId) {
            $automation = $service->automationForUser(auth()->user(), $assetId);

            if (! $automation || ! in_array(($automation->workflow_status ?? null), ['running', 'waiting'], true)) {
                continue;
            }

            $this->dispatch("suncatcher-two-product-design-updated.{$assetId}")->to(ProductDesignCard::class);
        }
    }
}
