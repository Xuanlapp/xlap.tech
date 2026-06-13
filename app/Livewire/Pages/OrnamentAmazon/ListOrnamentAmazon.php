<?php

namespace App\Livewire\Pages\OrnamentAmazon;

use App\Services\OrnamentAmazon\OrnamentAmazonService;
use App\Services\OrnamentAmazon\PsdMockupTemplateService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Attributes\Session;
use Livewire\Component;

class ListOrnamentAmazon extends Component
{
    private const PER_PAGE_OPTIONS = [5, 10, 20, 50, 100, 200, 400];

    public string $pageTitle = 'Ornament Amazon';

    public string $pageSubtitle = 'Quan ly quy trinh tao anh ornament Amazon';

    public string $addButtonLabel = 'Them ornament';

    #[Session(key: 'ornament.per-page')]
    public int $perPage = 5;

    #[On('product-design-created')]
    public function productDesignCreated(): void
    {
        //
    }

    #[On('ornament-amazon-product-design-approval-updated')]
    public function productDesignApprovalUpdated(): void
    {
        //
    }

    #[On('ornament-amazon-product-design-workflow-updated')]
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
        $service = app(OrnamentAmazonService::class);
        $perPage = in_array($this->perPage, self::PER_PAGE_OPTIONS, true) ? $this->perPage : 5;

        return view('livewire.pages.ornament-amazon.list-ornament-amazon', [
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
