<?php

namespace App\Services\Product;

use App\Models\ProductDesignAsset;
use App\Models\ProductDriveUpload;

class ProductDriveUploadQueueService
{
    public const IMAGE_FIELDS = [
        'redesign',
        'lifestyle1',
        'lifestyle2',
        'lifestyle3',
        'mockup1',
        'mockup2',
        'mockup3',
        'mockup4',
        'mockup5',
        'mockup6',
        'mockup7',
        'mockup8',
        'mockup9',
        'mockup10',
        'mockup11',
    ];

    public function syncForAsset(ProductDesignAsset $asset): void
    {
        if (! $asset->is_approved) {
            ProductDriveUpload::query()
                ->where('product_design_asset_id', $asset->id)
                ->where('status', 'waiting')
                ->delete();

            return;
        }

        if (! $this->hasLocalExportableImages($asset)) {
            return;
        }

        ProductDriveUpload::query()->updateOrCreate(
            ['product_design_asset_id' => $asset->id],
            [
                'user_id' => $asset->user_id,
                'product_id' => $asset->product_id,
                'status' => 'waiting',
                'error' => null,
                'started_at' => null,
                'completed_at' => null,
            ],
        );
    }

    public function hasLocalExportableImages(ProductDesignAsset $asset): bool
    {
        foreach (self::IMAGE_FIELDS as $field) {
            $url = $asset->getAttribute($field);

            if (is_string($url) && str_starts_with($url, '/storage/')) {
                return true;
            }
        }

        foreach (($asset->redesign_candidates ?: []) as $url) {
            if (is_string($url) && str_starts_with($url, '/storage/')) {
                return true;
            }
        }

        return false;
    }
}
