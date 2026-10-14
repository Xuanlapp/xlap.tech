<?php

namespace Tests\Feature;

use App\Jobs\QueueApprovedImagesForDrive;
use App\Livewire\Pages\Admin\ListUser;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ManualDriveUploadQueueTest extends TestCase
{
    public function test_manual_upload_enqueues_work_without_running_drive_export_in_the_request(): void
    {
        Queue::fake();
        $actor = new User();
        $actor->id = 24;
        $this->be($actor);

        $component = new ListUser();
        $component->uploadApprovedImagesToDrive();

        Queue::assertPushed(QueueApprovedImagesForDrive::class, function (QueueApprovedImagesForDrive $job): bool {
            return $job->actorId === 24 && $job->connection === 'database';
        });
        $this->assertStringContainsString('hang doi', $component->driveUploadStatus);
        $this->assertNull($component->driveUploadError);
    }
}
