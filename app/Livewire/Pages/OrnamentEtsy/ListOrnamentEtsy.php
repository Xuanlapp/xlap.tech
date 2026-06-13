<?php

namespace App\Livewire\Pages\OrnamentEtsy;

use App\Services\OrnamentEtsy\OrnamentEtsyService;
use App\Services\OrnamentEtsy\PsdMockupTemplateService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Attributes\Session;
use Livewire\Component;

class ListOrnamentEtsy extends Component
{
    private const PER_PAGE_OPTIONS = [5, 10, 20, 50, 100, 200, 400];

    public string $pageTitle = 'Ornament Etsy';

    public string $pageSubtitle = 'Quan ly quy trinh tao anh ornament Etsy';

    public string $addButtonLabel = 'Them ornament Etsy';

    #[Session(key: 'ornament-etsy.per-page')]
    public int $perPage = 5;

    #[On('ornament-etsy-product-design-created')]
    public function productDesignCreated(): void
    {
        //
    }

    #[On('ornament-etsy-product-design-approval-updated')]
    public function productDesignApprovalUpdated(): void
    {
        //
    }

    #[On('ornament-etsy-product-design-workflow-updated')]
    public function productDesignWorkflowUpdated(): void
    {
        //
    }

    #[On('ornament-etsy-psd-mockup-template-updated')]
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
        $service = app(OrnamentEtsyService::class);
        $perPage = in_array($this->perPage, self::PER_PAGE_OPTIONS, true) ? $this->perPage : 5;

        return view('livewire.pages.ornament-etsy.list-ornament-etsy', [
            'statusCounts' => $service->statusCountsForUser(auth()->user()),
            'activePsdTemplateName' => app(PsdMockupTemplateService::class)->activeOrnamentTemplateForUser(auth()->user())?->name,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'product' => $service->product(),
            'pageTitle' => $this->pageTitle,
            'pageSubtitle' => $this->pageSubtitle,
            'addButtonLabel' => $this->addButtonLabel,
        ])->layout('layouts.app');
    }
}
