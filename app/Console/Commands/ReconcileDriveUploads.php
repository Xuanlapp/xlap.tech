<?php

namespace App\Console\Commands;

use App\Models\ProductDriveUpload;
use Illuminate\Console\Command;

class ReconcileDriveUploads extends Command
{
    protected $signature = 'offorest:reconcile-drive-uploads';

    protected $description = 'Mark Drive upload records with persisted Drive files as completed.';

    public function handle(): int
    {
        $count = 0;

        ProductDriveUpload::query()
            ->where('status', 'failed')
            ->whereNotNull('drive_files')
            ->chunkById(100, function ($uploads) use (&$count): void {
                foreach ($uploads as $upload) {
                    $files = is_array($upload->drive_files) ? $upload->drive_files : [];

                    if ($files === []) {
                        continue;
                    }

                    $upload->update([
                        'status' => 'completed',
                        'error' => null,
                        'completed_at' => $upload->completed_at ?: now(),
                    ]);
                    $count++;
                }
            });

        $this->info("Reconciled {$count} Drive upload records.");

        return self::SUCCESS;
    }
}
