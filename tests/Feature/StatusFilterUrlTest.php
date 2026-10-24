<?php

namespace Tests\Feature;

use App\Livewire\Pages\Drive\DriveUploads;
use App\Livewire\Pages\Marketplace\ListingMetadataStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StatusFilterUrlTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_query_parameter_initializes_both_status_pages(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::withQueryParams(['status' => 'failed'])
            ->test(ListingMetadataStatus::class)
            ->assertSet('status', 'failed');

        Livewire::withQueryParams(['status' => 'completed'])
            ->test(DriveUploads::class)
            ->assertSet('status', 'completed');
    }

    public function test_invalid_status_query_parameter_falls_back_to_all(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::withQueryParams(['status' => 'unexpected'])
            ->test(ListingMetadataStatus::class)
            ->assertSet('status', 'all');

        Livewire::withQueryParams(['status' => 'unexpected'])
            ->test(DriveUploads::class)
            ->assertSet('status', 'all');
    }
}