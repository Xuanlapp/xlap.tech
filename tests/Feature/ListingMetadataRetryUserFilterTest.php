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
        $owner->products()->sync([$product->id]);
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

    public function test_retry_all_failed_can_be_scoped_to_a_page_for_all_users(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $owner = User::factory()->create();
        $product = Product::where('slug', 'ornament-amazon-2')->firstOrFail();
        $otherProduct = Product::where('slug', 'ceramic')->firstOrFail();
        $matching = $this->failedAsset($owner, $product, 11);
        $other = $this->failedAsset($owner, $otherProduct, 12);

        $this->actingAs($admin);
        Livewire::test(ListingMetadataStatus::class)
            ->set('productId', (string) $product->id)
            ->call('retryAllFailed')
            ->assertSet('retryMessage', 'Da dua 1 item loi ve hang doi. Worker se tu xu ly.');

        $this->assertSame('waiting', $matching->refresh()->marketplace_listing_status);
        $this->assertSame('failed', $other->refresh()->marketplace_listing_status);
    }

    public function test_selected_user_only_sees_pages_assigned_to_that_user(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $owner = User::factory()->create();
        $allowed = Product::where('slug', 'ornament-amazon-2')->firstOrFail();
        $notAllowed = Product::where('slug', 'ceramic')->firstOrFail();
        $owner->products()->sync([$allowed->id]);

        $this->actingAs($admin);
        Livewire::test(ListingMetadataStatus::class)
            ->set('userId', (string) $owner->id)
            ->assertViewHas('retryProducts', fn ($products) => $products->modelKeys() === [$allowed->id])
            ->set('productId', (string) $notAllowed->id)
            ->assertSet('productId', '');
    }
    public function test_support_products_are_excluded_from_page_filter_and_retry(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $owner = User::factory()->create();
        $page = Product::where('slug', 'ornament-amazon-2')->firstOrFail();
        $support = Product::create(['name' => 'Support Page', 'slug' => 'support-test', 'is_active' => true]);
        $pageAsset = $this->failedAsset($owner, $page, 21);
        $supportAsset = $this->failedAsset($owner, $support, 22);

        $this->actingAs($admin);
        Livewire::test(ListingMetadataStatus::class)
            ->assertViewHas('retryProducts', fn ($products) => $products->pluck('id')->contains((int) $page->id)
                && ! $products->pluck('id')->contains((int) $support->id))
            ->set('productId', (string) $support->id)
            ->assertSet('productId', '')
            ->call('retryAllFailed')
            ->assertSet('retryMessage', 'Da dua 1 item loi ve hang doi. Worker se tu xu ly.');

        $this->assertSame('waiting', $pageAsset->refresh()->marketplace_listing_status);
        $this->assertSame('failed', $supportAsset->refresh()->marketplace_listing_status);
    }

    public function test_user_can_retry_only_failed_assets_on_assigned_pages(): void
    {
        $owner = User::factory()->create();
        $allowedPage = Product::where('slug', 'ornament-amazon-2')->firstOrFail();
        $otherPage = Product::where('slug', 'ceramic')->firstOrFail();
        $owner->products()->sync([$allowedPage->id]);
        $selectedAsset = $this->failedAsset($owner, $allowedPage, 31);
        $unassignedAsset = $this->failedAsset($owner, $otherPage, 32);

        $this->actingAs($owner);
        Livewire::test(ListingMetadataStatus::class)
            ->assertViewHas('retryProducts', fn ($products) => $products->pluck('id')->all() === [$allowedPage->id])
            ->assertSee('Chọn Page được phân quyền')
            ->assertSee('Chạy lại tất cả lỗi')
            ->assertSee('Retry mục đã chọn')
            ->set('productId', (string) $otherPage->id)
            ->assertSet('productId', '')
            ->set('productId', (string) $allowedPage->id)
            ->set('selectedAssets', [$selectedAsset->id => true])
            ->call('retrySelected')
            ->assertSet('retryMessage', 'Da dua 1 item ve hang doi. Worker se tu xu ly.');

        $this->assertSame('waiting', $selectedAsset->refresh()->marketplace_listing_status);
        $this->assertSame('failed', $unassignedAsset->refresh()->marketplace_listing_status);

        $again = $this->failedAsset($owner, $allowedPage, 33);
        Livewire::test(ListingMetadataStatus::class)
            ->set('productId', (string) $allowedPage->id)
            ->call('retryAllFailed')
            ->assertSet('retryMessage', 'Da dua 1 item loi ve hang doi. Worker se tu xu ly.');

        $this->assertSame('waiting', $again->refresh()->marketplace_listing_status);
        $this->assertSame('failed', $unassignedAsset->refresh()->marketplace_listing_status);
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

