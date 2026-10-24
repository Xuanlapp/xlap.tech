<?php

namespace Tests\Feature;

use App\Livewire\Pages\Suncatcher\SuncatcherStatusPanel;
use App\Livewire\Pages\SuncatcherTwo\SuncatcherTwoStatusPanel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SuncatcherPollingPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_suncatcher_status_panels_use_visible_fifteen_second_refresh_instead_of_five_second_running_scan(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(SuncatcherStatusPanel::class, ['status' => 'all', 'perPage' => 10])
            ->assertSeeHtml('wire:poll.visible.15s="refreshAssets"')
            ->assertDontSeeHtml('wire:poll.5s="pollRunningAssets"');

        Livewire::test(SuncatcherTwoStatusPanel::class, ['status' => 'all', 'perPage' => 10])
            ->assertSeeHtml('wire:poll.visible.15s="refreshAssets"')
            ->assertDontSeeHtml('wire:poll.5s="pollRunningAssets"');
    }
}
