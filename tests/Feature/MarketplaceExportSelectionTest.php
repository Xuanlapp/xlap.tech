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

    public function test_export_fields_exclude_removed_lifestyle_columns(): void
    {
        $source = file_get_contents(app_path('Livewire/Pages/Marketplace/MarketplaceExports.php'));
        $view = file_get_contents(resource_path('views/livewire/pages/marketplace/marketplace-exports.blade.php'));

        $this->assertStringNotContainsString("'lifestyle1'", $source);
        $this->assertStringNotContainsString("'lifestyle2'", $source);
        $this->assertStringNotContainsString("'lifestyle3'", $source);
        $this->assertStringNotContainsString("'lifestyle1'", $view);
        $this->assertStringNotContainsString("'lifestyle2'", $view);
        $this->assertStringNotContainsString("'lifestyle3'", $view);
    }
}
