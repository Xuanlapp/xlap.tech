<?php

namespace App\Console\Commands;

use App\Models\CeramicLocalMockupJob;
use App\Services\Ceramic\CeramicService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class RunCeramicLocalMockupWorker extends Command
{
    protected $signature = 'ceramic:local-mockup-worker {--once : Process at most one waiting job} {--sleep=1 : Seconds to wait when there is no job}';

    protected $description = 'Render waiting Ceramic custom mockups on this synced local workstation.';

    public function handle(CeramicService $ceramic): int
    {
        do {
            $job = DB::transaction(function (): ?CeramicLocalMockupJob {
                $job = CeramicLocalMockupJob::query()
                    ->where('product_slug', 'ceramic')
                    ->where('status', 'waiting')
                    ->oldest('id')
                    ->lockForUpdate()
                    ->first();

                if (! $job) {
                    return null;
                }

                $job->update([
                    'status' => 'processing',
                    'executed_by' => 'local',
                    'attempts' => $job->attempts + 1,
                    'claimed_at' => now(),
                    'error_message' => null,
                ]);

                return $job->refresh();
            });

            if (! $job) {
                if ($this->option('once')) {
                    return self::SUCCESS;
                }

                sleep(max(1, (int) $this->option('sleep')));
                continue;
            }

            $this->line("Rendering Ceramic job #{$job->id} for item #{$job->product_design_asset_id}...");

            try {
                $ceramic->completeLocalMockupJob($job);
                $this->info("Completed Ceramic job #{$job->id}.");
            } catch (Throwable $exception) {
                $job->update([
                    'status' => 'failed',
                    'error_message' => mb_substr($exception->getMessage(), 0, 4000),
                    'completed_at' => now(),
                ]);
                $this->error("Ceramic job #{$job->id} failed: {$exception->getMessage()}");
            }
        } while (! $this->option('once'));

        return self::SUCCESS;
    }
}

