<?php

namespace Tests\Feature;

use App\Livewire\Pages\Admin\DataSkuSearch;
use App\Models\Product;
use App\Models\ProductDesignAsset;
use App\Models\User;
use App\Repositories\Product\ProductDesignAssetRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class DataSkuSearchUnapproveTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_unapprove_clears_every_matching_users_listing_data_only(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $firstOwner = User::factory()->create();
        $secondOwner = User::factory()->create();
        $product = Product::where('slug', 'ceramic')->firstOrFail();
        $assets = collect([$firstOwner, $secondOwner])->map(fn (User $owner, int $index) => ProductDesignAsset::create([
            'user_id' => $owner->id,
            'product_id' => $product->id,
            'sku' => 'CMEX1',
            'item_number' => $index + 1,
            'keyword' => 'Ceramic item '.$index,
            'is_approved' => true,
            'approved_at' => now(),
            'title' => 'Generated title '.$index,
            'marketplace_listing_status' => 'failed',
            'marketplace_listing_attempts' => 3,
            'marketplace_listing_error' => 'Extra: description_please_ignore',
        ]));
        $unrelated = ProductDesignAsset::create([
            'user_id' => $firstOwner->id,
            'product_id' => $product->id,
            'sku' => 'OTHER1',
            'item_number' => 3,
            'keyword' => 'Unrelated ceramic item',
            'is_approved' => true,
            'approved_at' => now(),
            'title' => 'Keep this title',
        ]);

        $this->actingAs($admin);
        Livewire::test(DataSkuSearch::class)
            ->set('skuSearch', 'CMEX1')
            ->call('resetAllFilteredAssets');

        foreach ($assets as $asset) {
            $asset->refresh();
            $this->assertFalse($asset->is_approved);
            $this->assertNull($asset->title);
            $this->assertNull($asset->marketplace_listing_error);
            $this->assertSame(0, $asset->marketplace_listing_attempts);
        }
        $this->assertTrue($unrelated->refresh()->is_approved);
        $this->assertSame('Keep this title', $unrelated->title);
    }
    public function test_unapproving_clears_listing_error_and_stale_worker_cannot_restore_it(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $product = Product::where('slug', 'ceramic')->firstOrFail();
        $asset = ProductDesignAsset::create([
            'user_id' => $admin->id,
            'product_id' => $product->id,
            'sku' => 'CMEX1',
            'item_number' => 700,
            'keyword' => 'Personalized Family Garden Christmas Ceramic Ornament',
            'image_link' => 'https://example.com/source.png',
            'redesign' => 'https://example.com/master.png',
            'is_approved' => true,
            'approved_at' => now(),
            'title' => 'Old generated title',
            'marketplace_listing_status' => 'processing',
            'marketplace_listing_attempts' => 3,
            'marketplace_listing_started_at' => now()->subMinute(),
            'marketplace_listing_error' => 'Extra: description_please_ignore',
        ]);
        $staleWorkerAsset = $asset->fresh();

        $this->actingAs($admin);
        Livewire::test(DataSkuSearch::class)
            ->call('resetAssetToUnapproved', $asset->id, false);

        $asset->refresh();
        $this->assertFalse($asset->is_approved);
        $this->assertNull($asset->marketplace_listing_error);
        $this->assertNull($asset->title);
        $this->assertSame('https://example.com/source.png', $asset->image_link);

        try {
            app(ProductDesignAssetRepository::class)->updateListingMetadata($staleWorkerAsset, ['title' => 'Stale result']);
            $this->fail('Expected stale worker output to be rejected.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('canceled', $exception->getMessage());
        }

        $this->assertNull($asset->refresh()->title);
        $this->assertNull($asset->marketplace_listing_error);
    }
}
