<?php

namespace App\Console\Commands;

use App\Models\GlassLocalMockupJob;
use App\Services\Decal\DecalService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class RunDecalLocalMockupFallback extends Command
{
    protected $signature = 'decal:local-mockup-fallback {--limit=1 : Maximum idle Decal jobs to render on the VPS}';

    protected $description = 'Render waiting Decal mockups on VPS after Decal generation has been idle long enough.';

    public function handle(DecalService $decal): int
    {
        $idleSeconds = max(1, (int) config('services.decal.local_mockup_fallback_seconds', 120));
        $limit = max(1, (int) $this->option('limit'));

        for ($count = 0; $count < $limit; $count++) {
            $job = DB::transaction(function () use ($idleSeconds): ?GlassLocalMockupJob {
                // A new Decal Generate restarts local-worker priority for the whole Decal batch.
                $latestGenerateJob = GlassLocalMockupJob::query()
                    ->where('product_slug', 'decal')
                    ->latest('created_at')
                    ->first(['created_at']);

                if (! $latestGenerateJob || $latestGenerateJob->created_at->gt(now()->subSeconds($idleSeconds))) {
                    return null;
                }

                $job = GlassLocalMockupJob::query()
                    ->where('product_slug', 'decal')
                    ->where('status', 'waiting')
                    ->oldest('id')
                    ->lockForUpdate()
                    ->first();

                if (! $job) {
                    return null;
                }

                $job->update([
                    'status' => 'processing',
                    'executed_by' => 'server',
                    'attempts' => $job->attempts + 1,
                    'claimed_at' => now(),
                    'error_message' => null,
                ]);

                return $job->refresh();
            });

            if (! $job) {
                return self::SUCCESS;
            }

            $this->line("Local wait expired; rendering Decal job #{$job->id} on the VPS...");

            try {
                $decal->completeLocalMockupJob($job);
                $this->info("Completed Decal fallback job #{$job->id}.");
            } catch (Throwable $exception) {
                $job->update([
                    'status' => 'failed',
                    'error_message' => mb_substr($exception->getMessage(), 0, 4000),
                    'completed_at' => now(),
                ]);
                $this->error("Decal fallback job #{$job->id} failed: {$exception->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
