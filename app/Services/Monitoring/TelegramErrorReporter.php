<?php

namespace App\Services\Monitoring;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramErrorReporter
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function report(Throwable $exception, array $context = []): void
    {
        if (! $this->enabled()) {
            return;
        }

        try {
            $response = Http::asForm()
                ->timeout((int) config('services.telegram_error_log.timeout', 5))
                ->post($this->endpoint(), [
                    'chat_id' => config('services.telegram_error_log.chat_id'),
                    'text' => $this->message($exception, $context),
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => true,
                ]);

            if ($response->failed()) {
                Log::warning('Telegram error report failed.', [
                    'status' => $response->status(),
                    'body_preview' => mb_substr($response->body(), 0, 500),
                ]);
            }
        } catch (ConnectionException $telegramException) {
            Log::warning('Telegram error report connection failed.', [
                'message' => $telegramException->getMessage(),
            ]);
        } catch (Throwable $telegramException) {
            Log::warning('Telegram error report failed unexpectedly.', [
                'message' => $telegramException->getMessage(),
            ]);
        }
    }

    private function enabled(): bool
    {
        return (bool) config('services.telegram_error_log.enabled', false)
            && filled(config('services.telegram_error_log.bot_token'))
            && filled(config('services.telegram_error_log.chat_id'));
    }

    private function endpoint(): string
    {
        return 'https://api.telegram.org/bot'.config('services.telegram_error_log.bot_token').'/sendMessage';
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function message(Throwable $exception, array $context): string
    {
        $user = auth()->user();
        $request = request();

        $details = [
            'context' => $context,
            'request_input' => $this->safeInput(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 250),
            'file' => $exception->getFile().':'.$exception->getLine(),
        ];

        $message = implode("\n", array_filter([
            '<b>Offorest Error Notify</b>',
            '<b>Action failed!</b>',
            '<b>Time:</b> '.$this->escape(now()->format('Y-m-d H:i:s')),
            '<b>Env:</b> '.$this->escape(app()->environment()),
            '',
            '<b>User:</b> '.$this->escape($this->userLine()),
            '<b>Action:</b> '.$this->escape((string) ($context['action'] ?? 'unknown')),
            '<b>Component:</b> '.$this->escape((string) ($context['component'] ?? 'unknown')),
            '<b>Route:</b> '.$this->escape((string) optional($request->route())->getName() ?: '-'),
            '<b>URL:</b> '.$this->escape($request->fullUrl()),
            '<b>IP:</b> '.$this->escape((string) $request->ip()),
            '',
            '<b>Error:</b> '.$this->escape($exception::class),
            '<b>Message:</b> '.$this->escape($exception->getMessage()),
            '',
            '<b>Details:</b>',
            '<pre>'.$this->escape($this->formatPayload($details)).'</pre>',
        ]));

        return mb_substr($message, 0, 3900);
    }

    private function userLine(): string
    {
        $user = auth()->user();

        if (! $user) {
            return 'Guest';
        }

        $role = (bool) $user->is_admin ? 'admin' : 'user';

        return "#{$user->id} {$user->name} <{$user->email}> ({$role})";
    }

    /**
     * @return array<string, mixed>
     */
    private function safeInput(): array
    {
        $input = request()->except([
            '_token',
            'password',
            'password_confirmation',
            'current_password',
            'token',
            'access_token',
            'refresh_token',
            'private_key',
            'credentials_json',
            'vertexJson',
            'marketplaceVertexJson',
        ]);

        return $this->truncateArray($input);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function formatPayload(array $payload): string
    {
        return json_encode(
            $this->truncateArray($payload),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR,
        ) ?: '{}';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function truncateArray(array $values): array
    {
        foreach ($values as $key => $value) {
            if (is_array($value)) {
                $values[$key] = $this->truncateArray($value);
                continue;
            }

            if (is_string($value)) {
                $values[$key] = mb_strlen($value) > 500 ? mb_substr($value, 0, 500).'...' : $value;
            }
        }

        return $values;
    }
}
