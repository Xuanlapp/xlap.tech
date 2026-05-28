<?php

namespace App\Repositories;

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
}
