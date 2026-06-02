<?php

namespace App\Services\Vertex;

use App\Models\User;
use App\Models\VertexApiCredential;
use App\Services\Image\BackgroundRemovalService;
use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class VertexImageGenerator
{
    private const MAX_INPUT_IMAGE_BYTES = 20_971_520;
    private const OUTPUT_PPI = 300;
    private const PNG_SIGNATURE = "\x89PNG\r\n\x1a\n";

    public function __construct(
        private readonly ?BackgroundRemovalService $backgroundRemoval = null,
    ) {}

    /**
     * Generate an image from an input image URI and prompt, then persist it on the public disk.
     */
    public function generate(
        User $user,
        string $imageUri,
        string $prompt,
        string $folder = 'generated',
        bool $removeBackground = false,
    ): string
    {
        $credential = $user->vertexApiCredential()
            ->where('is_active', true)
            ->first();

        if (! $credential) {
            throw new RuntimeException('Chưa cấu hình Vertex API cho user này.');
        }

        $credentials = $this->credentialsFor($credential);
        $projectId = $credential->project_id ?: ($credentials['project_id'] ?? null);
        $location = $credential->location ?: 'global';
        $model = config('services.vertex.model', 'gemini-2.5-flash-image');

        if (! $projectId) {
            throw new RuntimeException('Vertex API thiếu project_id.');
        }

        $response = $this->withCredentialLock(
            $credential,
            function () use ($credential, $credentials, $projectId, $location, $model, $prompt, $imageUri): Response {
                $this->ensureCredentialIsNotCoolingDown($credential);
                $imagePart = $this->sourceImagePart($imageUri);

                return Http::withToken($this->accessToken($credentials))
                    ->timeout(120)
                    ->post(
                        "https://aiplatform.googleapis.com/v1/projects/{$projectId}/locations/{$location}/publishers/google/models/{$model}:generateContent",
                        [
                            'contents' => [
                                [
                                    'role' => 'user',
                                    'parts' => [
                                        ['text' => $prompt],
                                        $imagePart,
                                    ],
                                ],
                            ],
                            'generationConfig' => [
                                'responseModalities' => ['TEXT', 'IMAGE'],
                            ],
                        ],
                    );
            },
        );

        if ($response->failed()) {
            $this->logExternalApiFailure('Vertex generateContent failed.', $response->status(), $response->body());

            if ($this->isQuotaExceeded($response)) {
                $this->cooldownCredential($credential, $response);
                $seconds = $this->cooldownSeconds($response);

                throw new RuntimeException("Vertex API dang het quota hoac bi gioi han toc do. Key nay se nghi {$seconds}s roi hay thu lai.");
            }

            throw new RuntimeException('Vertex API loi. Hay kiem tra quota, credential hoac cau hinh model.');
        }

        $imageBase64 = $this->extractImageData($response->json());

        if (! $imageBase64) {
            throw new RuntimeException('Vertex API không trả về ảnh.');
        }

        return $this->storeGeneratedImage($imageBase64, $folder, $removeBackground);
    }

    /**
     * Run one Vertex request per credential at a time to avoid burst 429 errors.
     */
    private function withCredentialLock(VertexApiCredential $credential, Closure $callback): Response
    {
        $lock = Cache::lock(
            $this->credentialLockKey($credential),
            (int) config('services.vertex.lock_seconds', 180),
        );

        try {
            return $lock->block(
                (int) config('services.vertex.lock_wait_seconds', 1),
                $callback,
            );
        } catch (LockTimeoutException) {
            throw new RuntimeException('Hang doi Vertex dang qua lau. Hay doi cac anh dang tao xong roi thu lai.');
        }
    }

    private function ensureCredentialIsNotCoolingDown(VertexApiCredential $credential): void
    {
        $cooldownUntil = Cache::get($this->credentialCooldownKey($credential));

        if (! is_numeric($cooldownUntil)) {
            return;
        }

        $remainingSeconds = ((int) $cooldownUntil) - time();

        if ($remainingSeconds > 0) {
            throw new RuntimeException("Vertex key dang nghi do quota/rate limit. Hay thu lai sau {$remainingSeconds}s.");
        }

        Cache::forget($this->credentialCooldownKey($credential));
    }

    private function cooldownCredential(VertexApiCredential $credential, Response $response): void
    {
        $seconds = $this->cooldownSeconds($response);

        Cache::put($this->credentialCooldownKey($credential), time() + $seconds, $seconds);
    }

    private function cooldownSeconds(Response $response): int
    {
        $retryAfter = $response->header('Retry-After');

        if (is_numeric($retryAfter)) {
            return max(1, (int) $retryAfter);
        }

        return max(1, (int) config('services.vertex.cooldown_seconds', 90));
    }

    private function isQuotaExceeded(Response $response): bool
    {
        return $response->status() === 429
            || str_contains($response->body(), 'RESOURCE_EXHAUSTED');
    }

    private function credentialLockKey(VertexApiCredential $credential): string
    {
        return "vertex:credential:{$credential->id}:generate-lock";
    }

    private function credentialCooldownKey(VertexApiCredential $credential): string
    {
        return "vertex:credential:{$credential->id}:cooldown-until";
    }

    /**
     * Build an inline image payload so Vertex does not need to crawl the source URL.
     *
     * @return array{inlineData: array{mimeType: string, data: string}}
     */
    private function sourceImagePart(string $imageUri): array
    {
        $imageUri = trim($imageUri);

        if (str_starts_with($imageUri, '/storage/')) {
            return $this->localStorageImagePart($imageUri);
        }

        if (preg_match('#^https?://#i', $imageUri) === 1) {
            return $this->remoteImagePart($this->normalizedSourceUrl($imageUri));
        }

        throw new RuntimeException('Link anh nguon khong hop le.');
    }

    /**
     * @return array{inlineData: array{mimeType: string, data: string}}
     */
    private function localStorageImagePart(string $imageUri): array
    {
        $path = ltrim(substr($imageUri, strlen('/storage/')), '/');

        if (! Storage::disk('public')->exists($path)) {
            throw new RuntimeException('Khong tim thay file anh nguon trong storage.');
        }

        $bytes = Storage::disk('public')->get($path);
        $this->assertInputImageSize($bytes);

        return $this->inlineImagePart($bytes, Storage::disk('public')->mimeType($path) ?: $this->mimeTypeFromUri($imageUri));
    }

    /**
     * @return array{inlineData: array{mimeType: string, data: string}}
     */
    private function remoteImagePart(string $imageUri): array
    {
        $host = parse_url($imageUri, PHP_URL_HOST);

        if (! is_string($host) || $this->isBlockedHost($host)) {
            throw new RuntimeException('Link anh nguon khong duoc phep hoac khong hop le.');
        }

        $response = Http::withHeaders([
            'Accept' => 'image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8',
            'User-Agent' => 'Mozilla/5.0 Offorest Image Fetcher',
        ])
            ->timeout(30)
            ->retry(2, 500)
            ->get($imageUri);

        if ($response->failed()) {
            throw new RuntimeException('Khong tai duoc anh nguon. HTTP '.$response->status().'. Hay dung link anh public/direct image.');
        }

        $contentType = strtolower(trim(explode(';', $response->header('Content-Type', ''))[0]));
        $mimeType = str_starts_with($contentType, 'image/')
            ? $contentType
            : $this->mimeTypeFromImageExtension($imageUri);

        if (! $mimeType) {
            throw new RuntimeException('Link nguon khong tra ve file anh.');
        }

        $bytes = $response->body();
        $this->assertInputImageSize($bytes);

        return $this->inlineImagePart($bytes, $mimeType);
    }

    /**
     * @return array{inlineData: array{mimeType: string, data: string}}
     */
    private function inlineImagePart(string $bytes, string $mimeType): array
    {
        return [
            'inlineData' => [
                'mimeType' => $mimeType,
                'data' => base64_encode($bytes),
            ],
        ];
    }

    private function assertInputImageSize(string $bytes): void
    {
        if (strlen($bytes) > self::MAX_INPUT_IMAGE_BYTES) {
            throw new RuntimeException('Anh nguon qua lon. Hay dung anh nho hon 20MB.');
        }
    }

    private function normalizedSourceUrl(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($host)) {
            return $url;
        }

        $host = strtolower($host);

        if (str_contains($host, 'drive.google.com')) {
            return $this->googleDrivePreviewUrl($url) ?? $url;
        }

        if (str_contains($host, 'dropbox.com')) {
            return $this->dropboxRawUrl($url);
        }

        return $url;
    }

    private function googleDrivePreviewUrl(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        $query = parse_url($url, PHP_URL_QUERY) ?: '';

        if (preg_match('#/file/d/([^/]+)#', $path, $matches) === 1) {
            return $this->googleDriveThumbnailUrl($matches[1]);
        }

        parse_str($query, $params);

        if (! empty($params['id']) && is_string($params['id'])) {
            return $this->googleDriveThumbnailUrl($params['id']);
        }

        return null;
    }

    private function googleDriveThumbnailUrl(string $fileId): string
    {
        return 'https://drive.google.com/thumbnail?id='.rawurlencode($fileId).'&sz=w2000';
    }

    private function dropboxRawUrl(string $url): string
    {
        $parts = parse_url($url);

        if (! isset($parts['scheme'], $parts['host'])) {
            return $url;
        }

        $baseUrl = $parts['scheme'].'://'.$parts['host'].($parts['path'] ?? '');
        parse_str($parts['query'] ?? '', $params);
        unset($params['dl']);
        $params['raw'] = '1';

        return $baseUrl.'?'.http_build_query($params);
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

    /**
     * Read credentials saved by Laravel encryption or entered directly as JSON/plain text.
     *
     * @return array<string, mixed>
     */
    private function credentialsFor(VertexApiCredential $credential): array
    {
        $credentials = $this->decodedJsonCredential($credential) ?? [];

        if (array_is_list($credentials) && isset($credentials[0]) && is_array($credentials[0])) {
            $credentials = $credentials[0];
        }

        $credentials['client_email'] ??= $this->plainOrEncryptedValue($credential->getRawOriginal('client_email'));
        $credentials['private_key'] ??= $this->plainOrEncryptedValue($credential->getRawOriginal('private_key'));

        if (isset($credentials['private_key']) && is_string($credentials['private_key'])) {
            $credentials['private_key'] = str_replace('\\n', "\n", $credentials['private_key']);
        }

        return $credentials;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodedJsonCredential(VertexApiCredential $credential): ?array
    {
        $rawJson = $credential->getRawOriginal('credentials_json');

        if (! is_string($rawJson) || trim($rawJson) === '') {
            return null;
        }

        $json = $this->plainOrEncryptedValue($rawJson);

        if (! is_string($json) || trim($json) === '') {
            return null;
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function plainOrEncryptedValue(mixed $value): mixed
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return $value;
        }
    }

    /**
     * @param  array<string, mixed>|null  $credentials
     */
    private function accessToken(?array $credentials): string
    {
        $clientEmail = $credentials['client_email'] ?? null;
        $privateKey = $credentials['private_key'] ?? null;

        if (! $clientEmail || ! $privateKey) {
            throw new RuntimeException('Vertex API thiếu client_email hoặc private_key.');
        }

        $now = time();
        $assertion = $this->base64UrlEncode(json_encode([
            'alg' => 'RS256',
            'typ' => 'JWT',
        ], JSON_THROW_ON_ERROR)).'.'.$this->base64UrlEncode(json_encode([
            'iss' => $clientEmail,
            'scope' => 'https://www.googleapis.com/auth/cloud-platform',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ], JSON_THROW_ON_ERROR));

        $signed = openssl_sign($assertion, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        if (! $signed) {
            throw new RuntimeException('Khong ky duoc Google service account JWT.');
        }

        $tokenResponse = Http::asForm()
            ->timeout(30)
            ->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $assertion.'.'.$this->base64UrlEncode($signature),
            ]);

        if ($tokenResponse->failed()) {
            $this->logExternalApiFailure('Google service account token failed.', $tokenResponse->status(), $tokenResponse->body());

            throw new RuntimeException('Khong lay duoc Google access token cho Vertex API.');
        }

        return $tokenResponse->json('access_token');
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function mimeTypeFromUri(string $uri): string
    {
        return $this->mimeTypeFromImageExtension($uri) ?? 'image/png';
    }

    private function mimeTypeFromImageExtension(string $uri): ?string
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '';
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'avif' => 'image/avif',
            'webp' => 'image/webp',
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function extractImageData(array $response): ?string
    {
        foreach ($response['candidates'] ?? [] as $candidate) {
            foreach ($candidate['content']['parts'] ?? [] as $part) {
                $inlineData = $part['inlineData'] ?? $part['inline_data'] ?? null;

                if (is_array($inlineData) && isset($inlineData['data'])) {
                    return $inlineData['data'];
                }
            }
        }

        return null;
    }

    private function storeGeneratedImage(string $imageBase64, string $folder, bool $removeBackground): string
    {
        $path = trim($folder, '/').'/'.uniqid('vertex_', true).'.png';

        $imageBytes = base64_decode($imageBase64, true);

        if ($imageBytes === false) {
            throw new RuntimeException('Vertex API tra ve du lieu anh khong hop le.');
        }

        if ($removeBackground) {
            $imageBytes = $this->backgroundRemoval()->remove($imageBytes);
        }

        Storage::disk('public')->put($path, $this->withPrintResolution($imageBytes, self::OUTPUT_PPI));

        return '/storage/'.$path;
    }

    private function backgroundRemoval(): BackgroundRemovalService
    {
        return $this->backgroundRemoval ?? app(BackgroundRemovalService::class);
    }

    /**
     * Ensure exported images carry print-resolution metadata.
     */
    private function withPrintResolution(string $imageBytes, int $ppi): string
    {
        if (! str_starts_with($imageBytes, self::PNG_SIGNATURE)) {
            $imageBytes = $this->encodePng($imageBytes);
        }

        return $this->withPngPhysicalPixelDensity($imageBytes, $ppi);
    }

    /**
     * Re-encode non-PNG image bytes to PNG before adding print metadata.
     */
    private function encodePng(string $imageBytes): string
    {
        $image = imagecreatefromstring($imageBytes);

        if ($image === false) {
            throw new RuntimeException('Vertex API tra ve dinh dang anh khong ho tro.');
        }

        imagesavealpha($image, true);

        ob_start();
        $encoded = imagepng($image);
        $pngBytes = ob_get_clean();
        imagedestroy($image);

        if (! $encoded || ! is_string($pngBytes)) {
            throw new RuntimeException('Khong the chuan hoa anh Vertex sang PNG.');
        }

        return $pngBytes;
    }

    /**
     * Add or replace PNG pHYs metadata so design tools read the file as the requested PPI.
     */
    private function withPngPhysicalPixelDensity(string $pngBytes, int $ppi): string
    {
        if (! str_starts_with($pngBytes, self::PNG_SIGNATURE)) {
            throw new RuntimeException('Khong the gan PPI cho file khong phai PNG.');
        }

        $minimumPixelsPerMeter = (int) round($ppi / 0.0254);
        $existingDensity = $this->pngPhysicalPixelDensity($pngBytes);
        $pixelsPerMeter = max(
            $minimumPixelsPerMeter,
            $existingDensity['x'] ?? 0,
            $existingDensity['y'] ?? 0,
        );
        $physChunk = $this->pngChunk('pHYs', pack('NNC', $pixelsPerMeter, $pixelsPerMeter, 1));
        $offset = strlen(self::PNG_SIGNATURE);
        $output = self::PNG_SIGNATURE;
        $inserted = false;

        while ($offset + 8 <= strlen($pngBytes)) {
            $length = unpack('N', substr($pngBytes, $offset, 4))[1];
            $type = substr($pngBytes, $offset + 4, 4);
            $chunkSize = 12 + $length;
            $chunk = substr($pngBytes, $offset, $chunkSize);

            if ($type === 'pHYs') {
                if (! $inserted) {
                    $output .= $physChunk;
                    $inserted = true;
                }
            } else {
                $output .= $chunk;

                if ($type === 'IHDR' && ! $inserted) {
                    $output .= $physChunk;
                    $inserted = true;
                }
            }

            $offset += $chunkSize;
        }

        return $output;
    }

    /**
     * @return array{x: int, y: int, unit: int}|null
     */
    private function pngPhysicalPixelDensity(string $pngBytes): ?array
    {
        $offset = strlen(self::PNG_SIGNATURE);

        while ($offset + 8 <= strlen($pngBytes)) {
            $length = unpack('N', substr($pngBytes, $offset, 4))[1];
            $type = substr($pngBytes, $offset + 4, 4);

            if ($type === 'pHYs') {
                $values = unpack('Nx/Ny/Cunit', substr($pngBytes, $offset + 8, $length));

                return [
                    'x' => $values['x'],
                    'y' => $values['y'],
                    'unit' => $values['unit'],
                ];
            }

            $offset += 12 + $length;
        }

        return null;
    }

    private function pngChunk(string $type, string $data): string
    {
        return pack('N', strlen($data))
            .$type
            .$data
            .pack('N', crc32($type.$data));
    }

    private function logExternalApiFailure(string $message, int $status, string $body): void
    {
        Log::warning($message, [
            'status' => $status,
            'body_preview' => mb_substr($this->redactedBody($body), 0, 1000),
        ]);
    }

    private function redactedBody(string $body): string
    {
        return preg_replace(
            [
                '/"access_token"\s*:\s*"[^"]+"/i',
                '/"refresh_token"\s*:\s*"[^"]+"/i',
                '/"id_token"\s*:\s*"[^"]+"/i',
                '/"private_key"\s*:\s*"[^"]+"/i',
                '/"client_secret"\s*:\s*"[^"]+"/i',
            ],
            [
                '"access_token":"[redacted]"',
                '"refresh_token":"[redacted]"',
                '"id_token":"[redacted]"',
                '"private_key":"[redacted]"',
                '"client_secret":"[redacted]"',
            ],
            $body,
        ) ?? '[unavailable]';
    }
}
