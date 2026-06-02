<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class ImagePreviewController extends Controller
{
    private const MAX_IMAGE_BYTES = 15_728_640;

    public function __invoke(Request $request): Response
    {
        $validated = $request->validate([
            'url' => ['required', 'url', 'max:1000'],
        ]);

        $url = $validated['url'];
        $host = parse_url($url, PHP_URL_HOST);

        abort_if(! is_string($host) || $this->isBlockedHost($host), 403);

        try {
            $response = Http::withHeaders([
                'Accept' => 'image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8',
                'User-Agent' => 'Mozilla/5.0 Offorest Image Preview',
            ])
                ->timeout(12)
                ->retry(1, 200)
                ->get($url);
        } catch (ConnectionException) {
            abort(404);
        }

        abort_unless($response->successful(), 404);

        $contentType = strtolower($response->header('Content-Type', ''));
        abort_unless(str_starts_with($contentType, 'image/'), 415);

        $body = $response->body();
        abort_if(strlen($body) > self::MAX_IMAGE_BYTES, 413);

        return response($body, 200)
            ->header('Content-Type', $contentType)
            ->header('Cache-Control', 'public, max-age=604800, stale-while-revalidate=86400');
    }

    private function isBlockedHost(string $host): bool
    {
        if (in_array(strtolower($host), ['localhost', 'localhost.localdomain'], true)) {
            return true;
        }

        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : gethostbynamel($host);

        if (! $ips) {
            return false;
        }

        foreach ($ips as $ip) {
            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return true;
            }
        }

        return false;
    }
}
