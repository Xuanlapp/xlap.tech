<?php

namespace App\Livewire\Pages\Ornament;

use App\Services\Ornament\OrnamentService;
use App\Services\Ornament\PsdMockupTemplateService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Attributes\Session;
use Livewire\Component;

class ListOrnament extends Component
{
    private const PER_PAGE_OPTIONS = [5, 10, 20, 50, 100, 200, 400];

    #[Session(key: 'ornament.per-page')]
    public int $perPage = 5;

    #[On('product-design-created')]
    public function productDesignCreated(): void
    {
        //
    }

    #[On('ornament-product-design-approval-updated')]
    public function productDesignApprovalUpdated(): void
    {
        //
    }

    #[On('ornament-product-design-workflow-updated')]
    public function productDesignWorkflowUpdated(): void
    {
        //
    }

    #[On('psd-mockup-template-updated')]
    public function psdMockupTemplateUpdated(): void
    {
        //
    }

    public function updatedPerPage(int|string $perPage): void
    {
        $perPage = (int) $perPage;

        if (! in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $this->perPage = 5;
        }

    }

    public function render(): View
    {
        $service = app(OrnamentService::class);
        $perPage = in_array($this->perPage, self::PER_PAGE_OPTIONS, true) ? $this->perPage : 5;

        return view('livewire.pages.ornament.list-ornament', [
            'statusCounts' => $service->statusCountsForUser(auth()->user()),
            'activePsdTemplateName' => app(PsdMockupTemplateService::class)->activeOrnamentTemplateForUser(auth()->user())?->name,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'product' => $service->product(),
        ])->layout('layouts.app');
    }
}
