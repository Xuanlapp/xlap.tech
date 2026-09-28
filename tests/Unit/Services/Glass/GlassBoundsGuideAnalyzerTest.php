<?php

namespace Tests\Unit\Services\Glass;

use App\Services\Glass\GlassBoundsGuideAnalyzer;
use Tests\TestCase;

class GlassBoundsGuideAnalyzerTest extends TestCase
{
    public function test_it_detects_template_and_safezone_from_guide_colors(): void
    {
        $image = imagecreatetruecolor(120, 120);
        $red = imagecolorallocate($image, 255, 0, 0);
        $blue = imagecolorallocate($image, 14, 40, 91);
        $green = imagecolorallocate($image, 55, 255, 0);
        imagefill($image, 0, 0, $red);
        imagefilledellipse($image, 60, 60, 120, 120, $blue);
        imagefilledellipse($image, 60, 60, 100, 100, $green);

        ob_start();
        imagepng($image);
        $bytes = ob_get_clean();
        imagedestroy($image);

        $result = app(GlassBoundsGuideAnalyzer::class)->analyze($bytes);

        $this->assertSame(['width' => 120, 'height' => 120], $result['config']['canvas']);
        $this->assertSame(['x' => 0, 'y' => 0, 'width' => 120, 'height' => 120], $result['config']['target']);
        $this->assertSame(['x' => 10, 'y' => 10, 'width' => 101, 'height' => 101], $result['config']['safezone']);
        $this->assertNotSame('', $result['png']);
    }
}
