<?php

namespace App\Services\Google;

use App\Models\GoogleDriveConnection;
use App\Models\User;
use App\Services\Logging\ActivityLogService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class GoogleDriveOAuthService
{
    /**
     * Build the Google OAuth consent URL and store state in the session.
     */
    public function authorizationUrl(): string
    {
        $clientId = config('services.google_drive.client_id');

        if (! is_string($clientId) || trim($clientId) === '') {
            throw new RuntimeException('Chua cau hinh GOOGLE_DRIVE_CLIENT_ID.');
        }

        $state = Str::random(40);
        session(['google_drive_oauth_state' => $state]);

        return 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'scope' => config('services.google_drive.scopes', 'https://www.googleapis.com/auth/drive.file'),
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
            'include_granted_scopes' => 'true',
        ]);
    }

    /**
     * Exchange an OAuth code for tokens and persist them for Drive uploads.
     */
    public function connect(User $user, string $code): GoogleDriveConnection
    {
        $response = Http::asForm()
            ->timeout(30)
            ->post('https://oauth2.googleapis.com/token', [
                'client_id' => $this->clientId(),
                'client_secret' => $this->clientSecret(),
                'redirect_uri' => $this->redirectUri(),
                'grant_type' => 'authorization_code',
                'code' => $code,
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Khong connect duoc Google Drive OAuth: '.$response->body());
        }

        $payload = $response->json();
        $accessToken = $payload['access_token'] ?? null;

        if (! is_string($accessToken) || $accessToken === '') {
            throw new RuntimeException('Google OAuth khong tra ve access token.');
        }

        GoogleDriveConnection::query()
            ->where('user_id', $user->id)
            ->update(['is_active' => false]);

        $connection = GoogleDriveConnection::create([
            'user_id' => $user->id,
            'access_token' => $accessToken,
            'refresh_token' => is_string($payload['refresh_token'] ?? null) ? $payload['refresh_token'] : null,
            'expires_at' => now()->addSeconds((int) ($payload['expires_in'] ?? 3600)),
            'scope' => is_string($payload['scope'] ?? null) ? $payload['scope'] : null,
            'is_active' => true,
        ]);

        app(ActivityLogService::class)->record(
            event: 'google_drive.connected',
            description: 'Admin connected Google Drive OAuth.',
            properties: ['connection_id' => $connection->id],
            actor: $user,
            actorType: 'admin',
        );

        return $connection;
    }

    /**
     * Return a valid access token, refreshing it when needed.
     */
    public function accessToken(): ?string
    {
        $connection = GoogleDriveConnection::query()
            ->where('is_active', true)
            ->latest('id')
            ->first();

        if (! $connection) {
            return null;
        }

        if (! $connection->expires_at || $connection->expires_at->greaterThan(now()->addSeconds(120))) {
            return $connection->access_token;
        }

        return $this->refresh($connection);
    }

    /**
     * Get the current active OAuth connection.
     */
    public function activeConnection(): ?GoogleDriveConnection
    {
        return GoogleDriveConnection::query()
            ->with('user')
            ->where('is_active', true)
            ->latest('id')
            ->first();
    }

    private function refresh(GoogleDriveConnection $connection): string
    {
        if (! is_string($connection->refresh_token) || $connection->refresh_token === '') {
            throw new RuntimeException('Google Drive OAuth token da het han va khong co refresh token. Hay Connect Google Drive lai.');
        }

        $response = Http::asForm()
            ->timeout(30)
            ->post('https://oauth2.googleapis.com/token', [
                'client_id' => $this->clientId(),
                'client_secret' => $this->clientSecret(),
                'grant_type' => 'refresh_token',
                'refresh_token' => $connection->refresh_token,
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Khong refresh duoc Google Drive OAuth token: '.$response->body());
        }

        $payload = $response->json();
        $accessToken = $payload['access_token'] ?? null;

        if (! is_string($accessToken) || $accessToken === '') {
            throw new RuntimeException('Google OAuth refresh khong tra ve access token.');
        }

        $connection->update([
            'access_token' => $accessToken,
            'expires_at' => now()->addSeconds((int) ($payload['expires_in'] ?? 3600)),
            'scope' => is_string($payload['scope'] ?? null) ? $payload['scope'] : $connection->scope,
        ]);

        return $accessToken;
    }

    private function clientId(): string
    {
        $clientId = config('services.google_drive.client_id');

        if (! is_string($clientId) || trim($clientId) === '') {
            throw new RuntimeException('Chua cau hinh GOOGLE_DRIVE_CLIENT_ID.');
        }

        return trim($clientId);
    }

    private function clientSecret(): string
    {
        $clientSecret = config('services.google_drive.client_secret');

        if (! is_string($clientSecret) || trim($clientSecret) === '') {
            throw new RuntimeException('Chua cau hinh GOOGLE_DRIVE_CLIENT_SECRET.');
        }

        return trim($clientSecret);
    }

    private function redirectUri(): string
    {
        if (! app()->runningInConsole()) {
            return request()->getSchemeAndHttpHost().route('offorest.admin.google-drive.callback', [], false);
        }

        $redirectUri = config('services.google_drive.redirect_uri');

        if (is_string($redirectUri) && filter_var(trim($redirectUri), FILTER_VALIDATE_URL)) {
            return trim($redirectUri);
        }

        return route('offorest.admin.google-drive.callback');
    }
}
