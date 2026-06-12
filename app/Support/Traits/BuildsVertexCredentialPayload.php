<?php

namespace App\Support\Traits;

use App\Models\VertexApiCredential;
use JsonException;
use Illuminate\Validation\ValidationException;

trait BuildsVertexCredentialPayload
{
    protected function normalizedLocation(string $location): string
    {
        $location = trim($location);

        return $location !== '' ? $location : 'global';
    }

    /**
     * @return array{project_id: string|null, location: string, client_email: string, private_key: string, credentials_json: array<string, mixed>}
     */
    protected function vertexCredentialPayloadFromJson(string $json, string $location, string $errorKey = 'vertexJson'): array
    {
        try {
            $credentials = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw ValidationException::withMessages([
                $errorKey => 'Vertex service account JSON khong hop le.',
            ]);
        }

        if (array_is_list($credentials) && isset($credentials[0]) && is_array($credentials[0])) {
            $credentials = $credentials[0];
        }

        if (! is_array($credentials)) {
            throw ValidationException::withMessages([
                $errorKey => 'Vertex service account JSON khong hop le.',
            ]);
        }

        $clientEmail = $credentials['client_email'] ?? null;
        $privateKey = $credentials['private_key'] ?? null;
        $projectId = $credentials['project_id'] ?? null;

        if (! is_string($clientEmail) || $clientEmail === '' || ! is_string($privateKey) || $privateKey === '') {
            throw ValidationException::withMessages([
                $errorKey => 'JSON phai co client_email va private_key.',
            ]);
        }

        return [
            'project_id' => is_string($projectId) && $projectId !== '' ? $projectId : null,
            'location' => $location,
            'client_email' => $clientEmail,
            'private_key' => str_replace('\\n', "\n", $privateKey),
            'credentials_json' => $credentials,
        ];
    }

    /**
     * @return array{function_key: string, project_id: string|null, location: string, client_email: string|null, private_key: string|null, credentials_json: array<string, mixed>|null, is_active: bool}
     */
    protected function copiedImageVertexCredentialPayload(?int $sourceUserId, string $fallbackLocation = 'global'): array
    {
        $source = VertexApiCredential::query()
            ->where('user_id', $sourceUserId)
            ->where('function_key', 'image_generation')
            ->where('is_active', true)
            ->first();

        if (! $source) {
            throw ValidationException::withMessages([
                'vertexCopyUserId' => 'User duoc chon chua co Vertex API active.',
            ]);
        }

        return [
            'function_key' => 'image_generation',
            'project_id' => $source->project_id,
            'location' => $source->location ?: $this->normalizedLocation($fallbackLocation),
            'client_email' => $source->client_email,
            'private_key' => $source->private_key,
            'credentials_json' => $source->credentials_json,
            'is_active' => true,
        ];
    }
}
