<?php

namespace App\Repositories\Product;

use App\Models\ProductDesignAsset;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
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

    /**
     * @return LengthAwarePaginator<ProductDesignAsset>
     */
    public function paginateForUserAndProduct(
        int $userId,
        int $productId,
        int $perPage,
        string $status = 'all',
        string $pageName = 'page',
    ): LengthAwarePaginator
    {
        return ProductDesignAsset::query()
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->when($status === 'unapproved', fn ($query) => $query->where('is_approved', false))
            ->when($status === 'approved', fn ($query) => $query->where('is_approved', true))
            ->orderBy('item_number')
            ->paginate($perPage, ['*'], $pageName);
    }

    /**
     * @return array{all: int, unapproved: int, approved: int}
     */
    public function statusCountsForUserAndProduct(int $userId, int $productId): array
    {
        $counts = ProductDesignAsset::query()
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->selectRaw('COUNT(*) as all_count')
            ->selectRaw('SUM(CASE WHEN is_approved = 0 THEN 1 ELSE 0 END) as unapproved_count')
            ->selectRaw('SUM(CASE WHEN is_approved = 1 THEN 1 ELSE 0 END) as approved_count')
            ->first();

        return [
            'all' => (int) $counts->all_count,
            'unapproved' => (int) $counts->unapproved_count,
            'approved' => (int) $counts->approved_count,
        ];
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
        $candidates = collect($asset->redesign_candidates ?: [])
            ->push($asset->redesign)
            ->push($redesign)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $asset->update([
            'redesign' => $redesign,
            'redesign_candidates' => $candidates,
        ]);

        return $asset->refresh();
    }

    public function selectRedesign(ProductDesignAsset $asset, string $redesign): ProductDesignAsset
    {
        $candidates = collect($asset->redesign_candidates ?: [])
            ->push($asset->redesign)
            ->push($redesign)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $asset->update([
            'redesign' => $redesign,
            'redesign_candidates' => $candidates,
        ]);

        return $asset->refresh();
    }

    public function removeRedesignCandidate(ProductDesignAsset $asset, string $redesign): ProductDesignAsset
    {
        $candidates = collect($asset->redesign_candidates ?: [])
            ->reject(fn (string $candidate): bool => $candidate === $redesign)
            ->values()
            ->all();

        $asset->update(['redesign_candidates' => $candidates]);

        return $asset->refresh();
    }

    public function updateLifestyleImages(ProductDesignAsset $asset, string $lifestyle1, string $lifestyle2, string $lifestyle3): ProductDesignAsset
    {
        $asset->update([
            'lifestyle1' => $lifestyle1,
            'lifestyle2' => $lifestyle2,
            'lifestyle3' => $lifestyle3,
        ]);

        return $asset->refresh();
    }

    /**
     * Append custom PSD mockup output slots to the next available mockup columns.
     *
     * @param array<int, string> $mockups
     */
    public function updatePsdMockups(ProductDesignAsset $asset, array $mockups): ProductDesignAsset
    {
        return $this->appendMockups($asset, $mockups);
    }

    /**
     * Append mockup output URLs to the first empty mockup slots in creation order.
     *
     * @param array<int, string> $mockups
     */
    public function appendMockups(ProductDesignAsset $asset, array $mockups): ProductDesignAsset
    {
        $asset = $asset->refresh();
        $updates = [];
        $nextSlot = 1;

        foreach (array_values($mockups) as $mockup) {
            while ($nextSlot <= 11 && filled($asset->getAttribute("mockup{$nextSlot}"))) {
                $nextSlot++;
            }

            if ($nextSlot > 11) {
                break;
            }

            $updates["mockup{$nextSlot}"] = $mockup;
            $asset->setAttribute("mockup{$nextSlot}", $mockup);
            $nextSlot++;
        }

        if ($updates === []) {
            return $asset;
        }

        $asset->update($updates);

        return $asset->refresh();
    }

    public function setApproval(ProductDesignAsset $asset, bool $approved): ProductDesignAsset
    {
        $asset->update([
            'is_approved' => $approved,
            'approved_at' => $approved ? now() : null,
        ]);

        return $asset->refresh();
    }

    /**
     * Replace custom PSD mockups from mockup1 onward.
     *
     * @param array<int, string> $mockups
     */
    public function replacePsdMockups(ProductDesignAsset $asset, array $mockups): ProductDesignAsset
    {
        $updates = collect(range(1, 11))
            ->mapWithKeys(fn (int $slot): array => ["mockup{$slot}" => null])
            ->all();

        foreach (array_values($mockups) as $index => $mockup) {
            $slot = $index + 1;

            if ($slot > 11) {
                break;
            }

            $updates["mockup{$slot}"] = $mockup;
        }

        $asset->update($updates);

        return $asset->refresh();
    }
}
