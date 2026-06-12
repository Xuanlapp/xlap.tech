<?php

namespace Tests\Unit;

use App\Services\Monitoring\TelegramErrorReporter;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class TelegramErrorReporterTest extends TestCase
{
    public function test_it_sends_enabled_error_reports_to_telegram(): void
    {
        config([
            'services.telegram_error_log.enabled' => true,
            'services.telegram_error_log.bot_token' => 'test-token',
            'services.telegram_error_log.chat_id' => '123456',
            'services.telegram_error_log.timeout' => 5,
        ]);

        Http::fake([
            '*' => Http::response(['ok' => true]),
        ]);

        app(TelegramErrorReporter::class)->report(new RuntimeException('Vertex failed'), [
            'action' => 'sticker.generate_redesign',
            'asset_id' => 99,
        ]);

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://api.telegram.org/bottest-token/sendMessage'
                && $request['chat_id'] === '123456'
                && $request['parse_mode'] === 'HTML'
                && str_contains((string) $request['text'], 'Vertex failed')
                && str_contains((string) $request['text'], '<b>Offorest Error Notify</b>')
                && str_contains((string) $request['text'], '<b>Action:</b> sticker.generate_redesign')
                && str_contains((string) $request['text'], 'sticker.generate_redesign')
                && str_contains((string) $request['text'], '&quot;asset_id&quot;: 99');
        });
    }

    public function test_it_does_not_send_when_disabled(): void
    {
        config([
            'services.telegram_error_log.enabled' => false,
            'services.telegram_error_log.bot_token' => 'test-token',
            'services.telegram_error_log.chat_id' => '123456',
        ]);

        Http::fake();

        app(TelegramErrorReporter::class)->report(new RuntimeException('Do not send'));

        Http::assertNothingSent();
    }
}
