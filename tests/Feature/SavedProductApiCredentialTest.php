<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserApiCredential;
use App\Services\Ai\ApiKeyImageGenerator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class SavedProductApiCredentialTest extends TestCase
{
    public function test_saved_key_is_available_across_products_without_crossing_user_boundaries(): void
    {
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->assertSame('sqlite', config('database.default'));
        Schema::create('vertex_api_credentials', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->boolean('is_active');
        });
        Schema::create('user_api_credentials', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('provider_key');
            $table->string('function_key');
            $table->boolean('is_active');
            $table->timestamps();
        });
        $user = new User;
        $user->id = 7;
        $own = UserApiCredential::create(['user_id' => 7, 'provider_key' => 'cheapkeyai', 'function_key' => 'sticker', 'is_active' => true]);
        UserApiCredential::create(['user_id' => 8, 'provider_key' => 'v98store', 'function_key' => 'suncatcher-two', 'is_active' => true]);
        UserApiCredential::create(['user_id' => 8, 'provider_key' => 'cheapkeyai', 'function_key' => 'suncatcher-two', 'is_active' => true]);
        UserApiCredential::create(['user_id' => 7, 'provider_key' => 'v98store', 'function_key' => 'sticker', 'is_active' => false]);

        foreach (['Sticker', 'Decal', 'Glass', 'Ceramic', 'OrnamentEtsy', 'Suncatcher', 'SuncatcherTwo'] as $product) {
            $service = app("App\\Services\\{$product}\\{$product}Service");
            $options = $service->providerOptionsForUser($user);
            $this->assertArrayHasKey('cheapkeyai', $options, $product);
            $this->assertArrayNotHasKey('v98store', $options, $product);
        }

        $method = new ReflectionMethod(ApiKeyImageGenerator::class, 'credentialFor');
        $selected = $method->invoke(app(ApiKeyImageGenerator::class), $user, 'cheapkeyai', 'suncatcher-two');
        $this->assertSame($own->id, $selected->id);
    }
}
