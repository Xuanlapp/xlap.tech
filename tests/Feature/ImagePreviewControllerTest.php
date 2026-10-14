<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ImagePreviewControllerTest extends TestCase
{
    public function test_drive_preview_reuses_cached_image_without_a_second_google_request(): void
    {
        Storage::fake('local');
        Http::fake(['drive.google.com/*' => Http::response('image-bytes', 200, ['Content-Type' => 'image/png'])]);

        $url = URL::temporarySignedRoute('image-preview.show', now()->addMinutes(5), [
            'url' => 'https://drive.google.com/thumbnail?id=preview-cache-test&sz=w800',
        ], absolute: false);

        $this->get($url)->assertOk()->assertContent('image-bytes');
        $this->get($url)->assertOk()->assertContent('image-bytes');
        Http::assertSentCount(1);
    }
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
