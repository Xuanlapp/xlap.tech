<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\VertexApiCredential;
use App\Services\Vertex\VertexImageGenerator;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use ReflectionMethod;
use RuntimeException;
use Tests\TestCase;

class VertexImageGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_adds_300_ppi_metadata_to_png_outputs(): void
    {
        $service = new VertexImageGenerator;
        $method = new ReflectionMethod($service, 'withPrintResolution');

        $pngBytes = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
            true
        );

        $this->assertIsString($pngBytes);

        $output = $method->invoke($service, $pngBytes, 300);
        $physicalPixelDensity = $this->pngPhysicalPixelDensity($output);

        $this->assertSame([
            'x' => 11811,
            'y' => 11811,
            'unit' => 1,
        ], $physicalPixelDensity);
    }

    public function test_it_cools_down_a_vertex_credential_after_quota_exhaustion(): void
    {
        $user = User::factory()->create();
        $credential = VertexApiCredential::create([
            'user_id' => $user->id,
            'project_id' => 'vertex-project',
            'location' => 'global',
            'client_email' => 'vertex@example.iam.gserviceaccount.com',
            'private_key' => 'test-key',
            'credentials_json' => null,
            'is_active' => true,
        ]);

        config(['services.vertex.cooldown_seconds' => 7]);

        $service = app(VertexImageGenerator::class);
        $cooldown = new ReflectionMethod($service, 'cooldownCredential');
        $ensureNotCoolingDown = new ReflectionMethod($service, 'ensureCredentialIsNotCoolingDown');
        $response = new Response(new Psr7Response(
            429,
            [],
            json_encode([
                'error' => [
                    'code' => 429,
                    'status' => 'RESOURCE_EXHAUSTED',
                ],
            ], JSON_THROW_ON_ERROR),
        ));

        $cooldown->invoke($service, $credential, $response);

        $this->assertGreaterThan(time(), Cache::get("vertex:credential:{$credential->id}:cooldown-until"));

        try {
            $ensureNotCoolingDown->invoke($service, $credential);
            $this->fail('Expected cooldown exception.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Vertex key dang nghi do quota/rate limit.', $exception->getMessage());
        }
    }

    public function test_vertex_lock_defaults_to_wait_for_the_next_turn(): void
    {
        $this->assertSame(600, (int) config('services.vertex.lock_seconds'));
        $this->assertSame(600, (int) config('services.vertex.lock_wait_seconds'));
    }

    public function test_it_preserves_png_outputs_above_300_ppi(): void
    {
        $service = new VertexImageGenerator;
        $method = new ReflectionMethod($service, 'withPrintResolution');

        $pngBytes = $this->pngWithPhysicalPixelDensity(23622);

        $output = $method->invoke($service, $pngBytes, 300);
        $physicalPixelDensity = $this->pngPhysicalPixelDensity($output);

        $this->assertSame([
            'x' => 23622,
            'y' => 23622,
            'unit' => 1,
        ], $physicalPixelDensity);
    }

    public function test_it_applies_configured_vertex_http_proxy(): void
    {
        config(['services.vertex.http_proxy' => 'http://127.0.0.1:8888']);

        $service = new VertexImageGenerator;
        $method = new ReflectionMethod($service, 'vertexHttpOptions');
        $options = $method->invoke($service);

        $this->assertSame('http://127.0.0.1:8888', $options['proxy'] ?? null);
        $this->assertFalse($options['expect']);
    }

    public function test_it_optimizes_large_input_images_before_inlining(): void
    {
        config([
            'services.vertex.max_input_dimension' => 100,
            'services.vertex.max_inline_image_bytes' => 1024,
        ]);

        $image = imagecreatetruecolor(400, 200);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));

        ob_start();
        imagepng($image);
        $bytes = ob_get_clean();
        imagedestroy($image);

        $this->assertIsString($bytes);

        $service = new VertexImageGenerator;
        $method = new ReflectionMethod($service, 'optimizedInputImage');
        [$optimizedBytes, $mimeType] = $method->invoke($service, $bytes, 'image/jpeg');
        $size = getimagesizefromstring($optimizedBytes);

        $this->assertSame('image/jpeg', $mimeType);
        $this->assertIsArray($size);
        $this->assertLessThanOrEqual(100, max($size[0], $size[1]));
    }

    public function test_it_uses_global_or_regional_vertex_endpoint_from_location(): void
    {
        $service = new VertexImageGenerator;
        $method = new ReflectionMethod($service, 'generateContentEndpoint');

        $this->assertSame(
            'https://aiplatform.googleapis.com/v1/projects/project-id/locations/global/publishers/google/models/gemini-2.5-flash-image:generateContent',
            $method->invoke($service, 'project-id', 'global', 'gemini-2.5-flash-image'),
        );

        $this->assertSame(
            'https://us-central1-aiplatform.googleapis.com/v1/projects/project-id/locations/us-central1/publishers/google/models/gemini-2.5-flash-image:generateContent',
            $method->invoke($service, 'project-id', 'us-central1', 'gemini-2.5-flash-image'),
        );
    }

    public function test_explicit_vertex_credential_columns_override_stale_json_credentials(): void
    {
        $user = User::factory()->create();
        $credential = VertexApiCredential::create([
            'user_id' => $user->id,
            'project_id' => 'current-project',
            'location' => 'global',
            'client_email' => 'current@example.iam.gserviceaccount.com',
            'private_key' => "-----BEGIN PRIVATE KEY-----\ncurrent\n-----END PRIVATE KEY-----\n",
            'credentials_json' => [
                'project_id' => 'old-project',
                'client_email' => 'old@example.iam.gserviceaccount.com',
                'private_key' => "-----BEGIN PRIVATE KEY-----\nold\n-----END PRIVATE KEY-----\n",
            ],
            'is_active' => true,
        ]);

        $service = new VertexImageGenerator;
        $method = new ReflectionMethod($service, 'credentialsFor');
        $credentials = $method->invoke($service, $credential);

        $this->assertSame('current@example.iam.gserviceaccount.com', $credentials['client_email']);
        $this->assertSame("-----BEGIN PRIVATE KEY-----\ncurrent\n-----END PRIVATE KEY-----\n", $credentials['private_key']);
    }

    /**
     * @return array{x: int, y: int, unit: int}|null
     */
    private function pngPhysicalPixelDensity(string $pngBytes): ?array
    {
        $offset = 8;

        while ($offset + 8 <= strlen($pngBytes)) {
            $length = unpack('N', substr($pngBytes, $offset, 4))[1];
            $type = substr($pngBytes, $offset + 4, 4);
            $data = substr($pngBytes, $offset + 8, $length);

            if ($type === 'pHYs') {
                $values = unpack('Nx/Ny/Cunit', $data);

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

    private function pngWithPhysicalPixelDensity(int $pixelsPerMeter): string
    {
        $pngBytes = $this->onePixelPngBytes();

        $this->assertIsString($pngBytes);

        $signature = substr($pngBytes, 0, 8);
        $ihdr = substr($pngBytes, 8, 25);
        $remaining = substr($pngBytes, 33);
        $data = pack('NNC', $pixelsPerMeter, $pixelsPerMeter, 1);
        $chunk = pack('N', strlen($data)).'pHYs'.$data.pack('N', crc32('pHYs'.$data));

        return $signature.$ihdr.$chunk.$remaining;
    }

    private function onePixelPngBytes(): string
    {
        $pngBytes = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
            true
        );

        $this->assertIsString($pngBytes);

        return $pngBytes;
    }

}
