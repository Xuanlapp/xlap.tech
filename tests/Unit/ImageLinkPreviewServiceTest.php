<?php

namespace Tests\Unit;

use App\Services\ImageLinkPreviewService;
use Tests\TestCase;

class ImageLinkPreviewServiceTest extends TestCase
{
    public function test_it_keeps_direct_image_urls(): void
    {
        $service = new ImageLinkPreviewService;

        $url = 'https://i.etsystatic.com/example/listing.jpg';

        $this->assertSame($url, $this->targetUrl($service->previewUrl($url)));
    }

    public function test_it_converts_google_drive_file_urls_to_thumbnail_urls(): void
    {
        $service = new ImageLinkPreviewService;

        $this->assertSame(
            'https://drive.google.com/thumbnail?id=abc123&sz=w1200',
            $this->targetUrl($service->previewUrl('https://drive.google.com/file/d/abc123/view?usp=sharing'))
        );
    }

    public function test_it_converts_google_drive_open_urls_to_thumbnail_urls(): void
    {
        $service = new ImageLinkPreviewService;

        $this->assertSame(
            'https://drive.google.com/thumbnail?id=abc123&sz=w1200',
            $this->targetUrl($service->previewUrl('https://drive.google.com/open?id=abc123'))
        );
    }

    public function test_it_converts_dropbox_urls_to_raw_urls(): void
    {
        $service = new ImageLinkPreviewService;

        $this->assertSame(
            'https://www.dropbox.com/s/example/file.png?raw=1',
            $this->targetUrl($service->previewUrl('https://www.dropbox.com/s/example/file.png?dl=0'))
        );
    }

    public function test_it_detects_image_urls(): void
    {
        $service = new ImageLinkPreviewService;

        $this->assertTrue($service->looksLikeImageUrl('https://i.etsystatic.com/example/listing.webp'));
        $this->assertTrue($service->looksLikeImageUrl('https://drive.google.com/file/d/abc123/view'));
        $this->assertFalse($service->looksLikeImageUrl('https://www.etsy.com/listing/123/example-product'));
    }

    private function targetUrl(?string $signedPreviewUrl): ?string
    {
        if (! $signedPreviewUrl) {
            return null;
        }

        parse_str(parse_url($signedPreviewUrl, PHP_URL_QUERY) ?: '', $query);

        return $query['url'] ?? null;
    }
}
