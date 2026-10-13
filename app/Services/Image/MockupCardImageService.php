<?php

namespace App\Services\Image;

use App\Models\ProductDesignAsset;
use Illuminate\Support\Facades\Storage;

class MockupCardImageService
{
    public function url(ProductDesignAsset $asset, int $slot, ?string $fallbackUrl, string $productSlug): ?string
    {
        $original = $asset->getAttribute("mockup{$slot}");
        if (! is_string($original) || ! str_starts_with($original, "/storage/generated/{$productSlug}/mockups/")) {
            return $fallbackUrl;
        }

        $relative = substr(parse_url($original, PHP_URL_PATH) ?: '', strlen('/storage/'));
        if (! preg_match("~^generated/{$productSlug}/mockups/[0-9]+/MOCKUP [0-9]+\\.png$~i", $relative)) {
            return $fallbackUrl;
        }

        $thumbnail = 'thumbnails/'.preg_replace('/\.png$/i', '.webp', $relative);
        $disk = Storage::disk('public');
        if (! $disk->exists($thumbnail) || $disk->size($thumbnail) === 0) {
            return $fallbackUrl;
        }

        if ($disk->exists($relative) && $disk->lastModified($thumbnail) < $disk->lastModified($relative)) {
            return $fallbackUrl;
        }

        return '/storage/'.$thumbnail.'?v='.$disk->lastModified($thumbnail);
    }
}
