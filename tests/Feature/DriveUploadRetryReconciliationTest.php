<?php

namespace Tests\Feature;

use App\Livewire\Pages\Drive\DriveUploads;
use App\Models\Product;
use App\Models\ProductDesignAsset;
use App\Models\ProductDriveUpload;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DriveUploadRetryReconciliationTest extends TestCase
{
    use RefreshDatabase;

    public function test_retry_marks_previously_uploaded_drive_files_as_completed_without_copying_again(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $product = Product::where('slug', 'ornament-amazon-2')->firstOrFail();
        $fields = ['redesign', 'mockup1', 'mockup2', 'mockup3', 'mockup4', 'mockup5'];
        $assetValues = [];
        $driveFiles = [];
        foreach ($fields as $index => $field) {
            $url = "https://drive.google.com/file/d/drive-file-{$index}/view";
            $assetValues[$field] = $url;
            $driveFiles[] = ['field' => $field, 'drive_url' => $url];
        }
        $asset = ProductDesignAsset::create([
            'user_id' => $admin->id,
            'product_id' => $product->id,
            'item_number' => 1,
            'keyword' => 'retry reconciliation',
            'is_approved' => true,
            'approved_at' => now(),
            ...$assetValues,
        ]);
        $upload = ProductDriveUpload::create([
            'product_design_asset_id' => $asset->id,
            'user_id' => $admin->id,
            'product_id' => $product->id,
            'status' => 'failed',
            'drive_files' => $driveFiles,
            'error' => 'previous retry failed',
        ]);

        $this->actingAs($admin);
        Livewire::test(DriveUploads::class)
            ->call('retryUpload', $upload->id)
            ->assertSet('retryMessage', 'Item da co du file Drive; da cap nhat trang thai completed.');

        $this->assertDatabaseHas('product_drive_uploads', [
            'id' => $upload->id,
            'status' => 'completed',
            'error' => null,
        ]);
    }
}
