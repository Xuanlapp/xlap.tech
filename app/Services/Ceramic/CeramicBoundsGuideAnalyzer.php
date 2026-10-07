<?php

namespace App\Services\Ceramic;

use Illuminate\Contracts\Filesystem\Filesystem;
use InvalidArgumentException;

class CeramicBoundsGuideAnalyzer
{
    /**
     * @return array{png: string, config: array<string, mixed>}
     */
    public function analyze(string $imageBytes): array
    {
        $image = @imagecreatefromstring($imageBytes);

        if (! $image) {
            throw new InvalidArgumentException('Khong doc duoc anh bounds. Hay upload PNG, JPG hoac WebP hop le.');
        }

        try {
            $width = imagesx($image);
            $height = imagesy($image);
            $target = $this->detectBounds($image, $width, $height, fn (int $red, int $green, int $blue): bool =>
                ($blue >= 65 && $blue >= $red + 25 && $blue >= $green + 18)
                || ($blue >= 110 && $green >= 90 && $red <= 100)
                || ($blue >= 60 && $blue >= $green + 25 && $red >= $green + 15)
            );
            $safezone = $this->detectBounds($image, $width, $height, fn (int $red, int $green, int $blue): bool =>
                $green >= 130 && $green >= $red + 55 && $green >= $blue + 45
            );

            if (! $target) {
                throw new InvalidArgumentException('Khong tim thay vong xanh nuoc/Template trong anh bounds.');
            }

            ob_start();
            imagealphablending($image, false);
            imagesavealpha($image, true);
            imagepng($image);
            $pngBytes = ob_get_clean();

            if (! is_string($pngBytes) || $pngBytes === '') {
                throw new InvalidArgumentException('Khong the chuan hoa anh bounds thanh PNG.');
            }

            return [
                'png' => $pngBytes,
                'config' => [
                    'version' => 1,
                    'canvas' => ['width' => $width, 'height' => $height],
                    'target' => $target,
                    'safezone' => $safezone,
                    'detected_at' => now()->toIso8601String(),
                ],
            ];
        } finally {
            imagedestroy($image);
        }
    }

    public function loadOrCreate(
        Filesystem $disk,
        string $imagePath = 'admin/ceramic/bounds-guide.png',
        string $configPath = 'admin/ceramic/bounds-guide.json',
    ): ?array {
        if ($disk->exists($configPath)) {
            $config = json_decode($disk->get($configPath), true);

            if (is_array($config)) {
                return $config;
            }
        }

        if (! $disk->exists($imagePath)) {
            return null;
        }

        $result = $this->analyze($disk->get($imagePath));
        $disk->put($imagePath, $result['png']);
        $disk->put($configPath, json_encode($result['config'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

        return $result['config'];
    }

    /**
     * @param  callable(int, int, int): bool  $matches
     * @return array{x: float, y: float, width: int, height: int}|null
     */
    private function detectBounds($image, int $width, int $height, callable $matches): ?array
    {
        $minX = $width;
        $minY = $height;
        $maxX = -1;
        $maxY = -1;
        $matchedPixels = 0;

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $color = imagecolorat($image, $x, $y);
                $red = ($color >> 16) & 0xFF;
                $green = ($color >> 8) & 0xFF;
                $blue = $color & 0xFF;

                if (! $matches($red, $green, $blue)) {
                    continue;
                }

                $matchedPixels++;
                $minX = min($minX, $x);
                $minY = min($minY, $y);
                $maxX = max($maxX, $x);
                $maxY = max($maxY, $y);
            }
        }

        if ($matchedPixels < 100 || $maxX < $minX || $maxY < $minY) {
            return null;
        }

        $boundsWidth = $maxX - $minX + 1;
        $boundsHeight = $maxY - $minY + 1;

        return [
            'x' => $minX,
            'y' => $minY,
            'width' => $boundsWidth,
            'height' => $boundsHeight,
        ];
    }
}


