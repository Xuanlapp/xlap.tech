<?php

namespace App\Console\Commands;

use App\Models\ProductDesignAsset;
use App\Services\Image\ImageThumbnailService;
use Illuminate\Console\Command;

class BackfillImageThumbnails extends Command
{
    protected $signature = 'images:backfill-thumbnails {--chunk=50 : Number of assets per batch}';
    protected $description = 'Create WebP thumbnails for existing local asset images';

    public function handle(ImageThumbnailService $thumbnails): int
    {
        if (! extension_loaded('imagick')) {
            $this->error('Imagick PHP extension is not available.');
            return self::FAILURE;
        }

        $columns = ['image_link', 'redesign'];
        for ($slot = 1; $slot <= 11; $slot++) $columns[] = 'mockup'.$slot;
        $count = 0;
        $created = 0;
        $skipped = 0;
        ProductDesignAsset::query()->select(array_merge(['id'], $columns))->chunkById(max(1, (int) $this->option('chunk')), function ($assets) use ($thumbnails, $columns, &$count, &$created, &$skipped): void {
            foreach ($assets as $asset) {
                foreach ($columns as $column) {
                    $thumbnails->ensureForUrl($asset->{$column}) ? $created++ : $skipped++;
                }
                $count++;
            }
            $this->line("Processed {$count} assets...");
        });
        $this->info("Finished {$count} assets. Created: {$created}. Skipped or invalid: {$skipped}.");
        return self::SUCCESS;
    }
}