<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserApiCredential;
use App\Services\Ai\ApiKeyImageGenerator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use RuntimeException;
use Tests\TestCase;

class ScriptProviderErrorTest extends TestCase
{
    public function test_text_timeout_uses_chat_endpoint_and_never_reports_image_generation(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        Schema::create('user_api_credentials', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('provider_key');
            $table->string('function_key');
            $table->text('key_api');
            $table->boolean('is_active');
            $table->timestamps();
        });
        UserApiCredential::create(['user_id' => 7, 'provider_key' => 'cheapkeyai', 'function_key' => 'sticker', 'key_api' => 'test-only-key', 'is_active' => true]);
        $user = new User;
        $user->id = 7;
        config(['services.api_key_providers.cheapkeyai.text_endpoint' => 'https://provider.test/v1/chat/completions']);
        Http::preventStrayRequests();
        Http::fake(['https://provider.test/v1/chat/completions' => Http::response('upstream timeout', 524)]);

        try {
            app(ApiKeyImageGenerator::class)->generateText($user, 'cheapkeyai', 'Generate B1 script', 'text-model', false);
            $this->fail('Expected a provider timeout.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('tao script/text', $exception->getMessage());
            $this->assertStringContainsString('HTTP 524', $exception->getMessage());
            $this->assertStringNotContainsString('tao anh', $exception->getMessage());
        }

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => $request->url() === 'https://provider.test/v1/chat/completions'
            && $request['model'] === 'text-model'
            && $request['messages'][0]['content'] === 'Generate B1 script'
            && ! isset($request['image']));
        $this->assertFalse(Cache::has('provider-pause:cheapkeyai:user:7'));
    }

    public function test_image_errors_keep_the_image_label(): void
    {
        $response = new \Illuminate\Http\Client\Response(new \GuzzleHttp\Psr7\Response(524));
        $method = new ReflectionMethod(ApiKeyImageGenerator::class, 'errorMessage');
        $message = $method->invoke(app(ApiKeyImageGenerator::class), 'cheapkeyai', $response);
        $this->assertStringContainsString('tao anh', $message);
        $this->assertStringContainsString('HTTP 524', $message);
    }
}
