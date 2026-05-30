<?php

namespace App\Livewire\Pages\Sticker;

use App\Services\Sticker\StickerService;
use App\Services\Sticker\PsdMockupTemplateService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class StickerStatusPanel extends Component
{
    use WithPagination;

    private const STATUS_OPTIONS = ['all', 'unapproved', 'approved'];

    public string $status;

    public int $perPage;

    public ?string $activePsdTemplateName = null;

    /**
     * @var array{all?: int, unapproved?: int, approved?: int}
     */
    public array $statusCounts = [];

    /**
     * @param array{all?: int, unapproved?: int, approved?: int} $statusCounts
     */
    public function mount(string $status, int $perPage, ?string $activePsdTemplateName = null, array $statusCounts = []): void
    {
        $this->status = in_array($status, self::STATUS_OPTIONS, true) ? $status : 'all';
        $this->perPage = $perPage;
        $this->activePsdTemplateName = $activePsdTemplateName;
        $this->statusCounts = $statusCounts;
    }

    #[On('product-design-created')]
    #[On('sticker-product-design-updated')]
    #[On('sticker-product-design-approval-updated')]
    #[On('sticker-product-design-workflow-updated')]
    #[On('sticker-counts-updated')]
    public function refreshAssets(): void
    {
        $this->statusCounts = app(StickerService::class)->statusCountsForUser(auth()->user());
    }

    #[On('psd-mockup-template-updated')]
    public function refreshPsdTemplate(): void
    {
        $this->activePsdTemplateName = app(PsdMockupTemplateService::class)
            ->activeStickerTemplateForUser(auth()->user())?->name;
    }

    public function placeholder(): View
    {
        return view('livewire.pages.sticker.sticker-status-panel-placeholder');
    }

    public function render(): View
    {
        $this->statusCounts = app(StickerService::class)->statusCountsForUser(auth()->user());

        return view('livewire.pages.sticker.sticker-status-panel', [
            'assets' => app(StickerService::class)->paginatedAssetsForUser(
                auth()->user(),
                $this->perPage,
                $this->status,
                $this->pageName(),
            ),
            'pageName' => $this->pageName(),
        ]);
    }

    private function pageName(): string
    {
        return "sticker_{$this->status}_page";
    }
}
