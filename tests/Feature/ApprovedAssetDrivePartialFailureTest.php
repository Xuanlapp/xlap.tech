<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductDesignAsset;
use App\Models\ProductDriveUpload;
use App\Models\User;
use App\Services\Google\DriveSourceNotFoundException;
use App\Services\Google\GoogleDriveService;
use App\Services\Product\ApprovedAssetDriveExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Mockery\MockInterface;
use Tests\TestCase;

class ApprovedAssetDrivePartialFailureTest extends TestCase
{
    use RefreshDatabase;

    public function test_drive_source_failure_does_not_block_local_images_or_duplicate_them_on_retry(): void
    {
        $user = User::factory()->create(['name' => 'Huy']);
        $product = Product::where('slug', 'ceramic')->firstOrFail();
        $relativePath = 'generated/test-drive-partial/mockup.png';
        $absolutePath = public_path('storage/'.$relativePath);
        File::ensureDirectoryExists(dirname($absolutePath));
        File::put($absolutePath, 'fake image bytes');

        $asset = ProductDesignAsset::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'item_number' => 3,
            'keyword' => 'partial drive export',
            'redesign' => 'https://drive.google.com/uc?id=source-file-id',
            'mockup1' => '/storage/'.$relativePath,
            'is_approved' => true,
            'approved_at' => now(),
        ]);

        $drive = $this->mock(GoogleDriveService::class, function (MockInterface $mock) use ($absolutePath, $asset): void {
            $mock->shouldReceive('createUploadFolder')->once()->andReturn([
                'id' => 'folder-id',
                'link' => 'https://drive.google.com/drive/folders/folder-id',
            ]);
            $mock->shouldReceive('copyFileToFolder')
                ->once()
                ->with('source-file-id', $asset->id.'_item1_huy.png', 'folder-id')
                ->andThrow(new \RuntimeException('Source denied'));
            $mock->shouldReceive('uploadLocalFile')
                ->once()
                ->with($absolutePath, $asset->id.'_item2_huy.png', \Mockery::type('string'), 'folder-id')
                ->andReturn('https://drive.google.com/file/d/uploaded-mockup/view');
        });

        $service = app(ApprovedAssetDriveExportService::class);
        $this->assertSame(['assets' => 1, 'images' => 1], $service->exportApprovedImages());
        $upload = ProductDriveUpload::where('product_design_asset_id', $asset->id)->firstOrFail();
        $this->assertSame('failed', $upload->status);
        $this->assertSame('mockup1', $upload->drive_files[0]['field']);
        $this->assertStringContainsString('redesign:', $upload->error);
        $this->assertFileDoesNotExist($absolutePath);

        $this->assertSame('https://drive.google.com/file/d/uploaded-mockup/view', $asset->refresh()->mockup1);
    }


    public function test_missing_redesign_source_is_skipped_with_warning_when_local_images_upload(): void
    {
        $user = User::factory()->create(['name' => 'Huy']);
        $product = Product::where('slug', 'ceramic')->firstOrFail();
        $relativePath = 'generated/test-drive-missing-redesign/mockup.png';
        $absolutePath = public_path('storage/'.$relativePath);
        File::ensureDirectoryExists(dirname($absolutePath));
        File::put($absolutePath, 'fake image bytes');

        $asset = ProductDesignAsset::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'item_number' => 4,
            'keyword' => 'missing redesign source',
            'redesign' => 'https://drive.google.com/uc?id=missing-source-id',
            'mockup1' => '/storage/'.$relativePath,
            'is_approved' => true,
            'approved_at' => now(),
        ]);

        $this->mock(GoogleDriveService::class, function (MockInterface $mock) use ($absolutePath, $asset): void {
            $mock->shouldReceive('createUploadFolder')->once()->andReturn(['id' => 'folder-id', 'link' => 'https://drive.google.com/drive/folders/folder-id']);
            $mock->shouldReceive('copyFileToFolder')->once()->andThrow(new DriveSourceNotFoundException('Drive returned 404'));
            $mock->shouldReceive('uploadLocalFile')->once()->with($absolutePath, $asset->id.'_item2_huy.png', \Mockery::type('string'), 'folder-id')->andReturn('https://drive.google.com/file/d/uploaded-mockup/view');
        });

        $this->assertSame(['assets' => 1, 'images' => 1], app(ApprovedAssetDriveExportService::class)->exportApprovedImages());
        $upload = ProductDriveUpload::where('product_design_asset_id', $asset->id)->firstOrFail();
        $this->assertSame('completed', $upload->status);
        $this->assertStringContainsString('redesign: bo qua', $upload->error);
        $this->assertSame('https://drive.google.com/uc?id=missing-source-id', $asset->refresh()->redesign);
        $this->assertSame('mockup1', $upload->drive_files[0]['field']);
    }

}



