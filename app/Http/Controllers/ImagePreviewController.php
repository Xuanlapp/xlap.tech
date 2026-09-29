<?php

namespace App\Http\Controllers;

use App\Services\Google\GoogleDriveService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class ImagePreviewController extends Controller
{
    private const MAX_IMAGE_BYTES = 52_428_800;

    public function __invoke(Request $request): Response
    {
        $validated = $request->validate([
            'url' => ['nullable', 'url', 'max:1000', 'required_without:path'],
            'path' => ['nullable', 'string', 'max:1000', 'required_without:url'],
        ]);

        // Local storage paths are already constrained below. Remote proxy URLs must
        // remain signed so this public image endpoint cannot become an open proxy.
        if (! empty($validated['url'])) {
            abort_unless($request->hasValidSignature(), 403);
        }

        if (! empty($validated['path'])) {
            $localPath = $this->storagePath((string) $validated['path']);
            abort_unless($localPath !== null && is_file($localPath), 404);

            return response()->file($localPath, [
                'Cache-Control' => 'private, max-age=1800',
                'Access-Control-Allow-Origin' => '*',
            ]);
        }

        $url = (string) $validated['url'];
        $host = parse_url($url, PHP_URL_HOST);

        abort_if(! is_string($host) || $this->isBlockedHost($host), 403);

        // Serve this app's own storage directly; fetching the public URL again can hit
        // a proxy/WAF rule and return 403 even though the local file is readable.
        $localPath = $this->localStoragePath($url);
        if ($localPath !== null && is_file($localPath)) {
            return response()->file($localPath, [
                'Cache-Control' => 'public, max-age=604800, stale-while-revalidate=86400',
                'Access-Control-Allow-Origin' => '*',
            ]);
        }

        $driveFileId = $this->googleDriveFileId($url);
        $response = $this->publicImageResponse($url);

        if ($response?->successful() && $this->isImageResponse($response)) {
            $body = $response->body();
            abort_if(strlen($body) > self::MAX_IMAGE_BYTES, 413);

            return $this->imageResponse($body, strtolower($response->header('Content-Type', '')));
        }

        if ($driveFileId) {
            try {
                $image = app(GoogleDriveService::class)->downloadImageFile($driveFileId);

                return $this->imageResponse($image['body'], $image['content_type']);
            } catch (RuntimeException) {
                abort(404);
            }
        }

        abort(404);
    }

    private function publicImageResponse(string $url): ?HttpResponse
    {
        try {
            return Http::withHeaders([
                'Accept' => 'image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8',
                'User-Agent' => 'Mozilla/5.0 Offorest Image Preview',
            ])
                ->timeout(12)
                ->retry(1, 200)
                ->get($url);
        } catch (ConnectionException) {
            return null;
        }
    }

    private function imageResponse(string $body, string $contentType): Response
    {
        abort_if(strlen($body) > self::MAX_IMAGE_BYTES, 413);

        return response($body, 200)
            ->header('Content-Type', $contentType)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Cache-Control', 'public, max-age=604800, stale-while-revalidate=86400');
    }

    private function isImageResponse(HttpResponse $response): bool
    {
        return str_starts_with(strtolower($response->header('Content-Type', '')), 'image/');
    }

    private function googleDriveFileId(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        $query = parse_url($url, PHP_URL_QUERY) ?: '';

        if (! is_string($host) || ! str_contains(strtolower($host), 'drive.google.com')) {
            return null;
        }

        if (preg_match('#/file/d/([^/]+)#', $path, $matches) === 1) {
            return $matches[1];
        }

        parse_str($query, $params);

        return ! empty($params['id']) && is_string($params['id']) ? $params['id'] : null;
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

    private function localStoragePath(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        if (! is_string($path) || ! str_starts_with($path, '/storage/')) {
            return null;
        }

        $requestHost = strtolower((string) request()->getHost());
        $urlHost = strtolower((string) parse_url($url, PHP_URL_HOST));
        $appHost = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
        if ($urlHost !== $requestHost && ($appHost === '' || $urlHost !== $appHost)) {
            return null;
        }

        return $this->storagePath($path);
    }

    private function storagePath(string $path): ?string
    {
        if (! str_starts_with($path, '/storage/') || str_contains($path, '..')) {
            return null;
        }

        return Storage::disk('public')->path(ltrim(substr($path, strlen('/storage/')), '/'));
    }
}
