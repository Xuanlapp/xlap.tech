<?php

namespace App\Services\Order;

use App\Models\ProductDesignAsset;
use App\Models\SkuOrderItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SkuOrderItemService
{
    /** Keep the order-ready image in sync after an approved master reaches Google Drive. */
    public function syncForAsset(ProductDesignAsset $asset): ?SkuOrderItem
    {
        if (! Schema::hasTable('sku_order_items')) {
            return null;
        }

        $sku = trim((string) $asset->sku);
        $driveMasterUrl = trim((string) $asset->redesign);

        if (! $asset->is_approved || $sku === '' || ! $this->isGoogleDriveUrl($driveMasterUrl)) {
            return DB::transaction(function () use ($asset): ?SkuOrderItem {
                User::query()->whereKey($asset->user_id)->lockForUpdate()->first();
                SkuOrderItem::query()->where('product_design_asset_id', $asset->id)->delete();

                return null;
            }, 5);
        }

        return DB::transaction(function () use ($asset, $sku, $driveMasterUrl): ?SkuOrderItem {
            User::query()->whereKey($asset->user_id)->lockForUpdate()->first();

            $byAsset = SkuOrderItem::query()->where('product_design_asset_id', $asset->id)->lockForUpdate()->first();
            $bySku = SkuOrderItem::query()->where('user_id', $asset->user_id)->where('sku', $sku)->lockForUpdate()->first();

            if ($bySku && $bySku->product_design_asset_id && $bySku->product_design_asset_id !== $asset->id) {
                Log::warning('Order SKU already belongs to another asset.', [
                    'user_id' => $asset->user_id,
                    'sku' => $sku,
                    'asset_id' => $asset->id,
                    'existing_asset_id' => $bySku->product_design_asset_id,
                ]);

                return null;
            }

            if ($bySku && $byAsset && $bySku->id !== $byAsset->id) {
                $byAsset->delete();
            }

            $item = $bySku ?: $byAsset ?: new SkuOrderItem();
            $item->fill([
                'product_design_asset_id' => $asset->id,
                'user_id' => $asset->user_id,
                'product_id' => $asset->product_id,
                'sku' => $sku,
                'image_link' => $driveMasterUrl,
                'source' => 'asset_sync',
            ]);
            $item->save();

            return $item->refresh();
        }, 5);
    }
    public function importManualItem(int $userId, int $productId, string $sku, string $imageLink): void
    {
        DB::transaction(function () use ($userId, $productId, $sku, $imageLink): void {
            User::query()->whereKey($userId)->lockForUpdate()->firstOrFail();
            $item = SkuOrderItem::query()->where('user_id', $userId)->where('sku', $sku)->lockForUpdate()->first();
            $item ??= new SkuOrderItem();
            $item->fill([
                'user_id' => $userId,
                'product_id' => $productId,
                'sku' => $sku,
                'image_link' => $imageLink,
                'source' => 'manual_import',
                'product_design_asset_id' => null,
            ]);
            $item->save();
        }, 5);
    }
    private function isGoogleDriveUrl(string $url): bool
    {
        return str_contains(strtolower((string) parse_url($url, PHP_URL_HOST)), 'drive.google.com');
    }
}
