<?php

namespace Tests\Feature;

use App\Livewire\Pages\Marketplace\ListingMetadataStatus;
use App\Models\Product;
use App\Models\ProductDesignAsset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class ListingMetadataRetryUserFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_retry_all_failed_only_queues_assets_for_selected_user(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $owner = User::factory()->create();
        $otherOwner = User::factory()->create();
        $product = Product::where('slug', 'ornament-amazon-2')->firstOrFail();
        $ownedAsset = $this->failedAsset($owner, $product, 1);
        $otherAsset = $this->failedAsset($otherOwner, $product, 2);
        Cache::put('provider-pause:cheapkeyai:user:'.$owner->id, ['reason' => 'quota'], now()->addHours(6));
        Cache::put('provider-pause:cheapkeyai:user:'.$otherOwner->id, ['reason' => 'quota'], now()->addHours(6));

        $this->actingAs($admin);
        Livewire::test(ListingMetadataStatus::class)
            ->set('userId', (string) $owner->id)
            ->call('retryAllFailed')
            ->assertSet('retryMessage', 'Da dua 1 item loi ve hang doi. Worker se tu xu ly.');

        $this->assertSame('waiting', $ownedAsset->refresh()->marketplace_listing_status);
        $this->assertSame('failed', $otherAsset->refresh()->marketplace_listing_status);
        $this->assertFalse(Cache::has('provider-pause:cheapkeyai:user:'.$owner->id));
        $this->assertTrue(Cache::has('provider-pause:cheapkeyai:user:'.$otherOwner->id));
    }

    private function failedAsset(User $user, Product $product, int $itemNumber): ProductDesignAsset
    {
        return ProductDesignAsset::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'item_number' => $itemNumber,
            'keyword' => 'retry test',
            'is_approved' => true,
            'approved_at' => now(),
            'marketplace_listing_marketplace' => 'amazon',
            'marketplace_listing_status' => 'failed',
            'marketplace_listing_error' => 'Previous failure',
        ]);
    }
}

