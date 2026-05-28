<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TurnstileVerifier
{
    public function enabled(): bool
    {
        return (bool) config('services.turnstile.enabled')
            && filled(config('services.turnstile.site_key'))
            && filled(config('services.turnstile.secret_key'));
    }

    public function siteKey(): ?string
    {
        return config('services.turnstile.site_key');
    }

    public function verify(?string $token): bool
    {
        if (! $this->enabled()) {
            return true;
        }

        if (blank($token)) {
            return false;
        }

        try {
            $response = Http::asForm()
                ->timeout(5)
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret' => config('services.turnstile.secret_key'),
                    'response' => $token,
                    'remoteip' => request()->ip(),
                ]);
        } catch (\Throwable $exception) {
            Log::warning('Turnstile verification request failed.', [
                'ip' => request()->ip(),
                'error' => Str::limit($exception->getMessage(), 255),
            ]);

            return false;
        }

        if ($response->json('success') === true) {
            return true;
        }

        Log::warning('Turnstile verification rejected login attempt.', [
            'ip' => request()->ip(),
            'error_codes' => $response->json('error-codes', []),
            'user_agent' => Str::limit((string) request()->userAgent(), 255),
        ]);

        return false;
    }
}
