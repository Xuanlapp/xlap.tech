<?php

namespace App\Services\Order;

use App\Models\ProductDesignAsset;
use App\Models\SkuOrderItem;
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
            SkuOrderItem::query()->where('product_design_asset_id', $asset->id)->delete();

            return null;
        }

        return SkuOrderItem::query()->updateOrCreate(
            ['product_design_asset_id' => $asset->id],
            [
                'user_id' => $asset->user_id,
                'product_id' => $asset->product_id,
                'sku' => $sku,
                'image_link' => $driveMasterUrl,
                'source' => 'asset_sync',
            ],
        );
    }

    private function isGoogleDriveUrl(string $url): bool
    {
        return str_contains(strtolower((string) parse_url($url, PHP_URL_HOST)), 'drive.google.com');
    }
}
