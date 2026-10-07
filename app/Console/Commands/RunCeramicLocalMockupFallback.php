<?php

namespace App\Console\Commands;

use App\Models\CeramicLocalMockupJob;
use App\Services\Ceramic\CeramicService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class RunCeramicLocalMockupFallback extends Command
{
    protected $signature = 'ceramic:local-mockup-fallback {--limit=1 : Maximum expired jobs to render on the VPS}';

    protected $description = 'Render waiting Ceramic mockups on VPS after Ceramic generation has been idle long enough.';

    public function handle(CeramicService $ceramic): int
    {
        $idleSeconds = max(1, (int) config('services.ceramic.local_mockup_fallback_seconds', 120));
        $limit = max(1, (int) $this->option('limit'));

        for ($count = 0; $count < $limit; $count++) {
            $job = DB::transaction(function () use ($idleSeconds): ?CeramicLocalMockupJob {
                // A new Generate restarts the local-worker priority window for the whole Ceramic batch.
                $latestGenerateJob = CeramicLocalMockupJob::query()
                    ->where('product_slug', 'ceramic')
                    ->latest('created_at')
                    ->first(['created_at']);

                if (! $latestGenerateJob || $latestGenerateJob->created_at->gt(now()->subSeconds($idleSeconds))) {
                    return null;
                }

                $job = CeramicLocalMockupJob::query()
                    ->where('product_slug', 'ceramic')
                    ->where('status', 'waiting')
                    ->oldest('id')
                    ->lockForUpdate()
                    ->first();

                if (! $job) {
                    return null;
                }

                // Claim before rendering so the local worker cannot process the same job.
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

            $this->line("Local wait expired; rendering Ceramic job #{$job->id} on the VPS...");

            try {
                $ceramic->completeLocalMockupJob($job);
                $this->info("Completed Ceramic fallback job #{$job->id}.");
            } catch (Throwable $exception) {
                $job->update([
                    'status' => 'failed',
                    'error_message' => mb_substr($exception->getMessage(), 0, 4000),
                    'completed_at' => now(),
                ]);
                $this->error("Ceramic fallback job #{$job->id} failed: {$exception->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}

