<?php

namespace Tests\Feature;

use App\Livewire\Pages\Marketplace\MarketplaceExports;
use Tests\TestCase;

class MarketplaceExportSelectionTest extends TestCase
{
    public function test_row_selection_sets_the_requested_state_idempotently(): void
    {
        $component = new MarketplaceExports;
        $component->status = 'unexported';
        $component->selectedUnexported = ['10'];

        $component->setAssetSelection(11, true);
        $component->setAssetSelection(11, true);
        $this->assertSame(['10', '11'], $component->selectedUnexported);

        $component->setAssetSelection(11, false);
        $this->assertSame(['10'], $component->selectedUnexported);
    }
}
