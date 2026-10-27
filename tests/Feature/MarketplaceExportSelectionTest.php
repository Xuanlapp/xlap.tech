<?php

namespace Tests\Feature;

use App\Livewire\Pages\Marketplace\MarketplaceExports;
use App\Models\ProductDesignAsset;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MarketplaceExportSelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_preserves_selection_and_export_order(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);
        $product = Product::where('slug', 'ceramic')->firstOrFail();
        $assets = collect(['SKU-B', 'SKU-A'])->map(fn (string $sku, int $index) => ProductDesignAsset::create([
            'user_id' => $user->id, 'product_id' => $product->id, 'item_number' => $index + 1,
            'sku' => $sku, 'title' => $sku, 'is_approved' => true,
            'redesign' => 'https://drive.google.com/file/d/example/view',
            'marketplace_listing_marketplace' => 'amazon',
        ]));

        Livewire::test(MarketplaceExports::class)
            ->call('setAssetSelection', $assets[0]->id, true)
            ->set('exactSku', true)
            ->set('search', 'SKU-A')
            ->call('setAssetSelection', $assets[1]->id, true)
            ->assertSee('Export Excel Pattern (2)')
            ->assertDontSee('Vui lòng chọn ít nhất 2 item hợp lệ');

        $regular = new MarketplaceExports;
        $regular->selectedUnexported = $assets->pluck('id')->all();
        $regular->search = 'SKU-A';
        $regular->exactSku = true;
        $regular->skuSort = 'desc';
        $regularResponse = $regular->exportSelected();
        $this->assertNotNull($regularResponse);
        ob_start();
        $regularResponse->sendContent();
        $regularCsv = ob_get_clean();
        $this->assertStringContainsString('SKU-A', $regularCsv);
        $this->assertStringContainsString('SKU-B', $regularCsv);
        $this->assertLessThan(strpos($regularCsv, 'SKU-A'), strpos($regularCsv, 'SKU-B'));

        $component = new MarketplaceExports;
        $component->selectedUnexported = $assets->pluck('id')->all();
        $component->search = 'SKU-A';
        $component->exactSku = true;
        $component->skuSort = 'asc';
        $response = $component->exportPattern();
        $this->assertNotNull($response);
        ob_start();
        $response->sendContent();
        $csv = ob_get_clean();
        $this->assertStringContainsString('SKU-A', $csv);
        $this->assertStringContainsString('SKU-B', $csv);
        $this->assertLessThan(strpos($csv, 'SKU-B'), strpos($csv, 'SKU-A'));
        $this->assertDatabaseCount('product_design_assets', 2);
        $this->assertSame(2, ProductDesignAsset::whereNotNull('marketplace_exported_at')->count());
    }

    public function test_marketplace_filter_does_not_discard_selected_items_from_pattern_export(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);
        $product = Product::where('slug', 'ceramic')->firstOrFail();
        $assets = collect(['amazon', 'etsy'])->map(fn (string $marketplace, int $index) => ProductDesignAsset::create([
            'user_id' => $user->id, 'product_id' => $product->id, 'item_number' => $index + 1,
            'sku' => 'SKU-'.strtoupper($marketplace), 'title' => 'Item', 'is_approved' => true,
            'redesign' => 'https://drive.google.com/file/d/example/view',
            'marketplace_listing_marketplace' => $marketplace,
        ]));

        Livewire::test(MarketplaceExports::class)
            ->call('setAssetSelection', $assets[0]->id, true)
            ->call('setAssetSelection', $assets[1]->id, true)
            ->set('marketplace', 'amazon')
            ->assertSee('Export Excel Pattern (2)')
            ->assertDontSee('Vui lòng chọn ít nhất 2 item hợp lệ')
            ->call('exportPattern')
            ->assertFileDownloaded();

        $this->assertSame(2, ProductDesignAsset::whereNotNull('marketplace_exported_at')->count());
    }

    public function test_selected_items_can_be_prioritized_across_pages(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);
        $product = Product::where('slug', 'ceramic')->firstOrFail();
        $assets = collect(range(1, 7))->map(fn (int $number) => ProductDesignAsset::create([
            'user_id' => $user->id, 'product_id' => $product->id, 'item_number' => $number,
            'sku' => 'SKU-'.$number, 'title' => 'Item '.$number, 'is_approved' => true,
            'redesign' => 'https://drive.google.com/file/d/example/view',
            'marketplace_listing_marketplace' => 'amazon',
        ]));

        $component = Livewire::test(MarketplaceExports::class)
            ->set('perPage', 5)
            ->set('skuSort', 'asc')
            ->call('setAssetSelection', $assets[6]->id, true)
            ->set('search', 'SKU-1')
            ->set('marketplace', 'amazon')
            ->set('exactSku', true)
            ->call('toggleSelectedFirst')
            ->assertSet('search', 'SKU-1')
            ->assertSet('marketplace', 'amazon')
            ->assertSet('selectedFirst', true)
            ->assertSeeInOrder(['SKU-7', 'SKU-1']);

        $component->call('setVisibleSelection', true)
            ->assertSet('selectedUnexported', array_map('strval', [$assets[6]->id, $assets[0]->id]))
            ->call('toggleSelectedFirst')
            ->assertSet('selectedFirst', false)
            ->assertSee('SKU-1')
            ->assertDontSee('SKU-7');
    }

    public function test_sku_sort_toggles_and_orders_before_pagination(): void
    {
        $component = new MarketplaceExports;
        $order = new \ReflectionMethod($component, 'orderAssets');

        $component->sortSku();
        $this->assertSame('asc', $component->skuSort);
        $this->assertStringContainsString('"sku" asc', $order->invoke($component, ProductDesignAsset::query())->toSql());

        $component->sortSku();
        $this->assertSame('desc', $component->skuSort);
        $this->assertStringContainsString('"sku" desc', $order->invoke($component, ProductDesignAsset::query())->toSql());
    }

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
