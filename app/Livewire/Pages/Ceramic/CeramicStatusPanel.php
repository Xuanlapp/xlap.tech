<?php

namespace App\Livewire\Pages\Ceramic;

use App\Livewire\Concerns\ReportsUserActionErrors;
use App\Services\Logging\ActivityLogService;
use App\Services\Ceramic\CeramicService;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use Livewire\Component;
use Livewire\WithPagination;

class CeramicStatusPanel extends Component
{
    use WithPagination;
    use ReportsUserActionErrors;

    private const STATUS_OPTIONS = ['all', 'unapproved', 'approved', 'no_mockup'];

    public string $status;

    #[Reactive]
    public int $perPage;

    #[Reactive]
    public string $search = '';

    public ?string $activePsdTemplateName = null;

    #[Reactive]
    public ?string $providerKey = null;

    #[Reactive]
    public ?string $imageModel = null;

    /**
     * @var array{all?: int, unapproved?: int, approved?: int, no_mockup?: int}
     */
    public array $statusCounts = [];

    /**
     * @param array{all?: int, unapproved?: int, approved?: int, no_mockup?: int} $statusCounts
     */
    public function mount(string $status, int $perPage, string $search = '', ?string $activePsdTemplateName = null, ?string $providerKey = null, ?string $imageModel = null, array $statusCounts = []): void
    {
        $this->status = in_array($status, self::STATUS_OPTIONS, true) ? $status : 'all';
        $this->perPage = $perPage;
        $this->search = trim($search);
        $this->activePsdTemplateName = $activePsdTemplateName;
        $this->providerKey = $providerKey;
        $this->imageModel = $imageModel;
        $this->statusCounts = $statusCounts;
    }

    public function generateRedesign(?int $assetId = null): void
    {
        if ($assetId === null) {
            $assetId = (int) (app(CeramicService::class)->paginatedAssetsForUser(
                auth()->user(),
                $this->perPage,
                $this->status,
                $this->pageName(),
                $this->search,
            )->first()?->id ?? 0);

            if ($assetId <= 0) {
                $this->dispatch('toast', type: 'error', title: 'Action failed!', message: 'Khong tim thay item de tao anh.');

                return;
            }
        }

        try {
            $asset = app(CeramicService::class)->generateRedesign(auth()->user(), $assetId, $this->providerKey, $this->imageModel);
            app(ActivityLogService::class)->record(
                event: 'ceramic.master_generated',
                description: 'User generated Ceramic master image.',
                subject: $asset,
                properties: ['item_number' => $asset->item_number, 'redesign' => $asset->redesign],
            );

            $this->dispatch('ceramic-product-design-workflow-updated')->to(ListCeramic::class);
            $this->dispatch('ceramic-product-design-workflow-updated')->to(self::class);
            $this->dispatch('toast', type: 'success', title: 'Successfully saved!', message: 'Da tao anh master.');
        } catch (RuntimeException $exception) {
            $this->reportUserActionError($exception, 'ceramic.generate_redesign', ['asset_id' => $assetId]);
            $this->dispatch('toast', type: 'error', title: 'Action failed!', message: $exception->getMessage());
        } catch (Throwable $exception) {
            $this->reportUserActionError($exception, 'ceramic.generate_redesign', ['asset_id' => $assetId]);
            Log::error('Ceramic master generation failed unexpectedly.', [
                'asset_id' => $assetId,
                'message' => $exception->getMessage(),
            ]);
            $this->dispatch('toast', type: 'error', title: 'Action failed!', message: 'Loi he thong khi tao anh master. Hay xem log de biet chi tiet.');
        } finally {
            $this->dispatch('ceramic-generation-finished');
        }
    }

    public function updatedPerPage(): void
    {
        $this->resetPage($this->pageName());
    }

    public function updatedSearch(): void
    {
        $this->search = trim($this->search);
        $this->resetPage($this->pageName());
    }

    #[On('ceramic-search-updated')]
    public function resetPageForSearch(): void
    {
        $this->resetPage($this->pageName());
    }

    #[On('product-design-created')]
    #[On('ceramic-product-design-updated')]
    #[On('ceramic-product-design-approval-updated')]
    #[On('ceramic-product-design-workflow-updated')]
    #[On('ceramic-counts-updated')]
    public function refreshAssets(): void
    {
        //
    }

    #[On('psd-mockup-template-updated')]
    public function refreshPsdTemplate(): void
    {
        //
    }

    public function placeholder(): View
    {
        return view('livewire.pages.ceramic.ceramic-status-panel-placeholder');
    }

    public function render(): View
    {

        return view('livewire.pages.ceramic.ceramic-status-panel', [
            'assets' => app(CeramicService::class)->paginatedAssetsForUser(
                auth()->user(),
                $this->perPage,
                $this->status,
                $this->pageName(),
                $this->search,
            ),
            'pageName' => $this->pageName(),
        ]);
    }

    private function pageName(): string
    {
        return "ceramic_{$this->status}_page";
    }
}


