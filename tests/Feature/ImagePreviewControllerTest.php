<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImagePreviewControllerTest extends TestCase
{
    public function test_signed_storage_path_preview_reads_the_configured_public_disk(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put(
            'generated/glass/uploads/bounds-source.png',
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true),
        );

        $url = \Illuminate\Support\Facades\URL::temporarySignedRoute('image-preview.show', now()->addMinutes(5), [
            'path' => '/storage/generated/glass/uploads/bounds-source.png',
        ]);

        $this->get($url)
            ->assertOk()
            ->assertHeader('content-type', 'image/png')
            ->assertHeader('access-control-allow-origin', '*');
    }
}
