<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class VertexImageGenerator
{
    /**
     * Generate an image from an input image URI and prompt, then persist it on the public disk.
     */
    public function generate(User $user, string $imageUri, string $prompt, string $folder = 'generated'): string
    {
        $credential = $user->vertexApiCredential()
            ->where('is_active', true)
            ->first();

        if (! $credential) {
            throw new RuntimeException('Chưa cấu hình Vertex API cho user này.');
        }

        $projectId = $credential->project_id;
        $location = $credential->location ?: 'global';
        $model = config('services.vertex.model', 'gemini-2.5-flash-image');

        if (! $projectId) {
            throw new RuntimeException('Vertex API thiếu project_id.');
        }

        $response = Http::withToken($this->accessToken($credential->credentials_json ?? [
            'client_email' => $credential->client_email,
            'private_key' => $credential->private_key,
        ]))
            ->timeout(120)
            ->post(
                "https://aiplatform.googleapis.com/v1/projects/{$projectId}/locations/{$location}/publishers/google/models/{$model}:generateContent",
                [
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                ['text' => $prompt],
                                [
                                    'fileData' => [
                                        'mimeType' => $this->mimeTypeFromUri($imageUri),
                                        'fileUri' => $imageUri,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'responseModalities' => ['TEXT', 'IMAGE'],
                    ],
                ],
            );

        if ($response->failed()) {
            throw new RuntimeException('Vertex API lỗi: '.$response->body());
        }

        $imageBase64 = $this->extractImageData($response->json());

        if (! $imageBase64) {
            throw new RuntimeException('Vertex API không trả về ảnh.');
        }

        return $this->storeGeneratedImage($imageBase64, $folder);
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

        openssl_sign($assertion, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        $tokenResponse = Http::asForm()
            ->timeout(30)
            ->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $assertion.'.'.$this->base64UrlEncode($signature),
            ]);

        if ($tokenResponse->failed()) {
            throw new RuntimeException('Không lấy được Google access token: '.$tokenResponse->body());
        }

        return $tokenResponse->json('access_token');
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function mimeTypeFromUri(string $uri): string
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '';
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            default => 'image/png',
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

    private function storeGeneratedImage(string $imageBase64, string $folder): string
    {
        $path = trim($folder, '/').'/'.uniqid('vertex_', true).'.png';

        Storage::disk('public')->put($path, base64_decode($imageBase64, true));

        return Storage::url($path);
    }
}
