<?php

namespace App\Jobs;

use App\Models\ProductDesignAsset;
use App\Services\Product\ProductDriveUploadQueueService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class QueueApprovedImagesForDrive implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public int $tries = 1;

    public int $uniqueFor = 3600;

    public function __construct(public readonly int $actorId) {}

    public function uniqueId(): string
    {
        return 'approved-images-to-drive';
    }

    public function handle(ProductDriveUploadQueueService $service): void
    {
        ProductDesignAsset::query()
            ->where('is_approved', true)
            ->where(function ($query): void {
                foreach (ProductDriveUploadQueueService::IMAGE_FIELDS as $field) {
                    $query->orWhere($field, 'like', '/storage/%');
                }
                $query->orWhereNotNull('redesign_candidates');
            })
            ->orderBy('id')
            ->chunkById(100, function ($assets) use ($service): void {
                foreach ($assets as $asset) {
                    if ($service->hasLocalExportableImages($asset)) {
                        $service->syncForAsset($asset);
                    }
                }
            });
    }
}
