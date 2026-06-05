<?php

namespace App\Console\Commands;

use App\Services\Marketplace\MarketplaceListingMetadataService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class GenerateMarketplaceListingMetadata extends Command
{
    protected $signature = 'offorest:generate-listing-metadata
        {--limit= : Maximum approved assets to process in this run. Use 0 to drain all waiting assets}
        {--delay= : Seconds to wait between Vertex calls}';

    protected $description = 'Generate Amazon/Etsy listing metadata for approved assets without title.';

    public function handle(MarketplaceListingMetadataService $metadata): int
    {
        $lock = Cache::lock(
            'marketplace-listing-metadata:generate',
            (int) config('services.marketplace_listing.lock_seconds', 21600),
        );

        if (! $lock->get()) {
            $this->info('Listing metadata generator is already running.');

            return self::SUCCESS;
        }

        try {
            $limit = $this->option('limit');
            $delay = $this->option('delay');
            $processed = $metadata->generatePendingApprovedAssets(
                is_numeric($limit) ? (int) $limit : (int) config('services.marketplace_listing.batch_size', 0),
                is_numeric($delay) ? (int) $delay : (int) config('services.marketplace_listing.delay_seconds', 30),
            );

            $this->info("Generated listing metadata for {$processed} approved asset(s).");

            return self::SUCCESS;
        } finally {
            $lock->release();
        }
    }
}
