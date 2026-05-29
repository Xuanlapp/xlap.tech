<?php

namespace App\Repositories\Product;

use App\Models\ProductDesignAsset;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ProductDesignAssetRepository
{
    /**
     * @return Collection<int, ProductDesignAsset>
     */
    public function forUserAndProduct(int $userId, int $productId): Collection
    {
        return ProductDesignAsset::query()
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->orderBy('item_number')
            ->get();
    }

    public function createDraft(int $userId, int $productId, string $keyword): ProductDesignAsset
    {
        return DB::transaction(function () use ($userId, $productId, $keyword): ProductDesignAsset {
            $lastNumber = ProductDesignAsset::query()
                ->where('user_id', $userId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->max('item_number');

            return ProductDesignAsset::create([
                'user_id' => $userId,
                'product_id' => $productId,
                'item_number' => ((int) $lastNumber) + 1,
                'keyword' => $keyword,
            ]);
        });
    }

    public function createWithSource(int $userId, int $productId, string $keyword, string $imageLink): ProductDesignAsset
    {
        return DB::transaction(function () use ($userId, $productId, $keyword, $imageLink): ProductDesignAsset {
            $lastNumber = ProductDesignAsset::query()
                ->where('user_id', $userId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->max('item_number');

            return ProductDesignAsset::create([
                'user_id' => $userId,
                'product_id' => $productId,
                'item_number' => ((int) $lastNumber) + 1,
                'keyword' => $keyword,
                'image_link' => $imageLink,
            ]);
        });
    }

    public function latestWithoutImageLink(int $userId, int $productId): ?ProductDesignAsset
    {
        return ProductDesignAsset::query()
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->whereNull('image_link')
            ->latest('item_number')
            ->first();
    }

    public function findForUserAndProduct(int $assetId, int $userId, int $productId): ProductDesignAsset
    {
        return ProductDesignAsset::query()
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->findOrFail($assetId);
    }

    public function updateSourceDetails(ProductDesignAsset $asset, string $keyword, string $imageLink): ProductDesignAsset
    {
        $asset->update([
            'keyword' => $keyword,
            'image_link' => $imageLink,
        ]);

        return $asset->refresh();
    }

    public function updateRedesign(ProductDesignAsset $asset, string $redesign): ProductDesignAsset
    {
        $asset->update(['redesign' => $redesign]);

        return $asset->refresh();
    }

    public function updateFinalImages(ProductDesignAsset $asset, string $mockup1, string $mockup2): ProductDesignAsset
    {
        $asset->update([
            'mockup1' => $mockup1,
            'mockup2' => $mockup2,
        ]);

        return $asset->refresh();
    }

    /**
     * Update custom PSD mockup output slots starting at mockup2.
     *
     * @param array<int, string> $mockups
     */
    public function updatePsdMockups(ProductDesignAsset $asset, array $mockups): ProductDesignAsset
    {
        $updates = [];

        foreach (array_values($mockups) as $index => $mockup) {
            $slot = $index + 2;

            if ($slot > 11) {
                break;
            }

            $updates["mockup{$slot}"] = $mockup;
        }

        if ($updates !== []) {
            $asset->update($updates);
        }

        return $asset->refresh();
    }
}
