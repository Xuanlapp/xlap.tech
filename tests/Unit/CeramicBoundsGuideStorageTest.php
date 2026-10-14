<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\Ceramic\CeramicBoundsGuideStorage;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CeramicBoundsGuideStorageTest extends TestCase
{
    public function test_guide_priority_is_personal_then_ceramic_then_glass(): void
    {
        Storage::fake('public');
        $disk = Storage::disk('public');
        $user = new User();
        $user->id = 42;
        $guide = app(CeramicBoundsGuideStorage::class);

        $this->assertSame('admin/glass/bounds-guide.png', $guide->paths($user)['image']);
        $this->assertNull($guide->url($user));

        $disk->put('admin/glass/bounds-guide.png', 'glass');
        $this->assertStringContainsString('admin%2Fglass%2Fbounds-guide.png', $guide->url($user));
        $this->assertStringContainsString('admin%2Fglass%2Fbounds-guide.png', $guide->sharedUrl());

        $disk->put('admin/ceramic/bounds-guide.png', 'ceramic');
        $this->assertSame('admin/ceramic/bounds-guide.png', $guide->paths($user)['image']);
        $this->assertStringContainsString('admin%2Fceramic%2Fbounds-guide.png', $guide->sharedUrl());

        $disk->put('users/42/ceramic/bounds-guide.png', 'personal');
        $this->assertSame('users/42/ceramic/bounds-guide.png', $guide->paths($user)['image']);
        $this->assertTrue($guide->paths($user)['personal']);
    }
}