<?php

namespace Tests\Unit;

use App\Services\Vertex\VertexImageGenerator;
use ReflectionMethod;
use Tests\TestCase;

class VertexImageGeneratorTest extends TestCase
{
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
        $pngBytes = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
            true
        );

        $this->assertIsString($pngBytes);

        $signature = substr($pngBytes, 0, 8);
        $ihdr = substr($pngBytes, 8, 25);
        $remaining = substr($pngBytes, 33);
        $data = pack('NNC', $pixelsPerMeter, $pixelsPerMeter, 1);
        $chunk = pack('N', strlen($data)).'pHYs'.$data.pack('N', crc32('pHYs'.$data));

        return $signature.$ihdr.$chunk.$remaining;
    }
}
