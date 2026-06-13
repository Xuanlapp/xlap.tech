<?php

namespace App\Services\Product;

use App\Models\ProductDesignAsset;
use Illuminate\Support\Facades\Storage;

class ProductDesignAssetFileCleanupService
{
    /**
     * Delete local files owned by one product design asset.
     *
     * Only URLs under /storage are removed. External URLs such as Drive,
     * Dropbox, or remote source links are left untouched.
     *
     * @return array{files: int, directories: int}
     */
    public function deleteLocalFiles(ProductDesignAsset $asset, string $productSlug): array
    {
        $deletedFiles = 0;

        foreach ($this->localStoragePaths($asset) as $path) {
            if (! Storage::disk('public')->exists($path)) {
                continue;
            }

            if (Storage::disk('public')->delete($path)) {
                $deletedFiles++;
            }
        }

        $deletedDirectories = $this->deleteGeneratedDirectories($asset, $productSlug);

        return [
            'files' => $deletedFiles,
            'directories' => $deletedDirectories,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function localStoragePaths(ProductDesignAsset $asset): array
    {
        $urls = [
            $asset->image_link,
            $asset->redesign,
            $asset->lifestyle1,
            $asset->lifestyle2,
            $asset->lifestyle3,
            ...($asset->redesign_candidates ?: []),
        ];

        for ($slot = 1; $slot <= 11; $slot++) {
            $urls[] = $asset->getAttribute("mockup{$slot}");
        }

        return collect($urls)
            ->filter(fn (mixed $url): bool => is_string($url) && str_starts_with($url, '/storage/'))
            ->map(fn (string $url): string => ltrim(substr($url, strlen('/storage/')), '/'))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function deleteGeneratedDirectories(ProductDesignAsset $asset, string $productSlug): int
    {
        $directories = [
            "generated/{$productSlug}/mockups/{$asset->id}",
        ];

        $deleted = 0;

        foreach ($directories as $directory) {
            if (! Storage::disk('public')->exists($directory)) {
                continue;
            }

            if (Storage::disk('public')->deleteDirectory($directory)) {
                $deleted++;
            }
        }

        return $deleted;
    }
}
