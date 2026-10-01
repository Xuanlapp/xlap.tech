<?php

namespace App\Services\Image;

use Illuminate\Support\Facades\Storage;

class ImageThumbnailService
{
    public function ensureForUrl(?string $url, int $width = 640): bool
    {
        $path = $this->storagePath($url);
        if ($path === null || ! extension_loaded('imagick')) return false;
        $disk = Storage::disk('public');
        $source = $disk->path($path);
        $thumbnailPath = $this->thumbnailPath($path);
        $thumbnail = $disk->path($thumbnailPath);
        if (! is_file($source) || (is_file($thumbnail) && filemtime($thumbnail) >= filemtime($source))) return false;
        $image = null;
        $temporaryThumbnail = $thumbnail.'.'.getmypid().'.tmp';
        try {
            $image = new \Imagick($source);
            $image->setIteratorIndex(0);
            $image->thumbnailImage($width, $width, true, true);
            $image->setImageFormat('webp');
            $image->setImageCompressionQuality(82);
            $disk->makeDirectory(dirname($thumbnailPath));
            $image->writeImage($temporaryThumbnail);
            rename($temporaryThumbnail, $thumbnail);
            return true;
        } catch (\Throwable) {
            return false;
        } finally {
            if ($image instanceof \Imagick) { $image->clear(); $image->destroy(); }
            if (is_file($temporaryThumbnail)) @unlink($temporaryThumbnail);
        }
    }

    public function thumbnailUrlForPath(string $url): ?string
    {
        $path = ltrim(parse_url($url, PHP_URL_PATH) ?: $url, '/');
        if (! str_starts_with($path, 'storage/')) return null;
        $thumbnailPath = $this->thumbnailPath(substr($path, 8));
         $disk = Storage::disk('public');
        if (! $disk->exists($thumbnailPath) || ! $disk->exists(substr($path, 8))) return null;
        return $disk->lastModified($thumbnailPath) >= $disk->lastModified(substr($path, 8))
            ? '/storage/'.$thumbnailPath
            : null;
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