<?php

namespace Tests\Feature;

use App\Livewire\Pages\Admin\DataSkuSearch;
use App\Models\Product;
use App\Models\ProductDesignAsset;
use App\Models\User;
use App\Models\ProductDriveUpload;
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
    public function test_bulk_approve_and_user_chip_filter_only_matching_rows(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $owner = User::factory()->create(['name' => 'Quang']);
        $other = User::factory()->create(['name' => 'Huy']);
        $product = Product::where('slug', 'glass')->firstOrFail();
        foreach ([[$owner, 'A1'], [$other, 'A2']] as [$user, $sku]) {
            ProductDesignAsset::create(['user_id' => $user->id, 'product_id' => $product->id, 'sku' => $sku, 'item_number' => 1, 'redesign' => '/storage/generated/master.png', 'mockup1' => 'https://example.com/1.png', 'mockup2' => 'https://example.com/2.png', 'mockup3' => 'https://example.com/3.png', 'is_approved' => false]);
        }
        $this->actingAs($admin);
        Livewire::test(DataSkuSearch::class)->set('skuSearch', 'A1,A2')->call('toggleUserFilter', $owner->id)->call('approveAllFilteredAssets');
        $this->assertTrue(ProductDesignAsset::where('sku', 'A1')->value('is_approved'));
        $this->assertSame('waiting', ProductDesignAsset::where('sku', 'A1')->value('marketplace_listing_status'));
        $this->assertSame('waiting', ProductDriveUpload::where('product_design_asset_id', ProductDesignAsset::where('sku', 'A1')->value('id'))->value('status'));
        $this->assertFalse(ProductDesignAsset::where('sku', 'A2')->value('is_approved'));
    }

    public function test_bulk_approval_requires_three_custom_mockups(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $product = Product::where('slug', 'glass')->firstOrFail();
        $asset = ProductDesignAsset::create(['user_id' => $admin->id, 'product_id' => $product->id, 'sku' => 'THRESHOLD', 'item_number' => 20, 'redesign' => 'https://example.com/master.png', 'mockup1' => 'https://example.com/1.png', 'mockup2' => 'https://example.com/2.png', 'is_approved' => false]);
        $this->actingAs($admin);
        $component = Livewire::test(DataSkuSearch::class)->set('skuSearch', 'THRESHOLD')->call('approveAllFilteredAssets');
        $this->assertFalse($asset->fresh()->is_approved);
        $asset->update(['mockup3' => 'https://example.com/3.png']);
        $component->call('approveAllFilteredAssets');
        $this->assertTrue($asset->fresh()->is_approved);
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
