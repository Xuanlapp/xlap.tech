<?php

namespace App\Services\Image;

use Illuminate\Support\Facades\Storage;

class ImageThumbnailService
{
    public function ensureForUrl(?string $url, int $width = 640): void
    {
        $path = $this->storagePath($url);
        if ($path === null || ! extension_loaded('imagick')) return;
        $disk = Storage::disk('public');
        $source = $disk->path($path);
        $thumbnailPath = $this->thumbnailPath($path);
        $thumbnail = $disk->path($thumbnailPath);
        if (! is_file($source) || (is_file($thumbnail) && filemtime($thumbnail) >= filemtime($source))) return;
        $image = new \Imagick($source);
        $image->setIteratorIndex(0);
        $image->thumbnailImage($width, $width, true, true);
        $image->setImageFormat('webp');
        $image->setImageCompressionQuality(82);
        $disk->makeDirectory(dirname($thumbnailPath));
        $image->writeImage($thumbnail);
        $image->clear();
        $image->destroy();
    }

    public function thumbnailUrlForPath(string $url): ?string
    {
        $path = ltrim(parse_url($url, PHP_URL_PATH) ?: $url, '/');
        if (! str_starts_with($path, 'storage/')) return null;
        $thumbnailPath = $this->thumbnailPath(substr($path, 8));
        return Storage::disk('public')->exists($thumbnailPath) ? '/storage/'.$thumbnailPath : null;
    }

    private function storagePath(?string $url): ?string
    {
        if (! $url) return null;
        $path = ltrim(parse_url(trim($url), PHP_URL_PATH) ?: '', '/');
        return str_starts_with($path, 'storage/') ? substr($path, 8) : null;
    }

    private function thumbnailPath(string $path): string
    {
        return 'thumbnails/'.ltrim($path, '/').'.webp';
    }
}