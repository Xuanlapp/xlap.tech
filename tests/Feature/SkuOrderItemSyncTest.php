<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductDesignAsset;
use App\Models\SkuOrderItem;
use App\Models\User;
use App\Services\Order\SkuOrderItemService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SkuOrderItemSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_manual_sku_is_attached_to_approved_asset(): void
    {
        $user = User::factory()->create();
        $product = Product::where('slug', 'glass')->firstOrFail();
        $item = SkuOrderItem::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'sku' => 'RGF1-5',
            'image_link' => 'https://drive.google.com/file/d/old/view',
            'source' => 'manual_import',
        ]);

        $asset = $this->createApprovedAsset($user, $product, 1, 'RGF1-5');

        $this->assertSame(1, SkuOrderItem::where('user_id', $user->id)->where('sku', 'RGF1-5')->count());
        $this->assertSame($asset->id, $item->refresh()->product_design_asset_id);
        $this->assertSame($asset->redesign, $item->image_link);
    }

    public function test_resync_of_same_asset_does_not_create_duplicate_order_item(): void
    {
        $user = User::factory()->create();
        $product = Product::where('slug', 'glass')->firstOrFail();
        $first = $this->createApprovedAsset($user, $product, 1, 'RGF1-5');
        $first->update(['redesign' => 'https://drive.google.com/file/d/new-master/view']);

        $item = SkuOrderItem::where('user_id', $user->id)->where('sku', 'RGF1-5')->sole();
        $this->assertSame($first->id, $item->product_design_asset_id);
        $this->assertSame($first->redesign, $item->image_link);
    }

    public function test_conflicting_order_sku_keeps_existing_asset_and_image(): void
    {
        $user = User::factory()->create();
        $product = Product::where('slug', 'glass')->firstOrFail();
        $first = $this->createApprovedAsset($user, $product, 1, 'RGF1-5');
        $second = $this->createApprovedAsset($user, $product, 2, 'OTHER-5');
        SkuOrderItem::where('product_design_asset_id', $first->id)->delete();
        $existing = SkuOrderItem::where('product_design_asset_id', $second->id)->firstOrFail();
        $existing->update(['sku' => 'RGF1-5']);

        $this->assertNull(app(SkuOrderItemService::class)->syncForAsset($first));
        $this->assertSame($second->id, $existing->refresh()->product_design_asset_id);
        $this->assertSame($second->redesign, $existing->image_link);
        $this->assertSame(1, SkuOrderItem::where('user_id', $user->id)->count());
    }

    public function test_changed_asset_sku_reuses_manual_row_without_duplicate(): void
    {
        $user = User::factory()->create();
        $product = Product::where('slug', 'glass')->firstOrFail();
        $asset = $this->createApprovedAsset($user, $product, 1, 'OLD-5');
        $manual = SkuOrderItem::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'sku' => 'NEW-5',
            'image_link' => 'https://drive.google.com/file/d/old/view',
            'source' => 'manual_import',
        ]);

        $asset->update(['sku' => 'NEW-5']);

        $this->assertSame(1, SkuOrderItem::where('user_id', $user->id)->count());
        $this->assertSame($asset->id, $manual->refresh()->product_design_asset_id);
        $this->assertSame($asset->redesign, $manual->image_link);
    }

    public function test_manual_import_updates_existing_sku_without_duplicate(): void
    {
        $user = User::factory()->create();
        $product = Product::where('slug', 'glass')->firstOrFail();
        $existing = SkuOrderItem::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'sku' => 'MANUAL-1',
            'image_link' => 'https://drive.google.com/file/d/old/view',
            'source' => 'manual_import',
        ]);

        app(SkuOrderItemService::class)->importManualItem($user->id, $product->id, 'MANUAL-1', 'https://drive.google.com/file/d/new/view');

        $this->assertSame(1, SkuOrderItem::where('user_id', $user->id)->where('sku', 'MANUAL-1')->count());
        $this->assertSame('https://drive.google.com/file/d/new/view', $existing->refresh()->image_link);
        $this->assertNull($existing->product_design_asset_id);
    }
    private function createApprovedAsset(User $user, Product $product, int $itemNumber, string $sku): ProductDesignAsset
    {
        return ProductDesignAsset::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'item_number' => $itemNumber,
            'sku' => $sku,
            'keyword' => 'Test item '.$itemNumber,
            'redesign' => 'https://drive.google.com/file/d/master-'.$itemNumber.'/view',
            'is_approved' => true,
        ]);
    }
}
