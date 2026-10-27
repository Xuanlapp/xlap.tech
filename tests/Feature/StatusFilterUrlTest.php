<?php

namespace Tests\Feature;

use App\Livewire\Pages\Drive\DriveUploads;
use App\Livewire\Pages\Marketplace\ListingMetadataStatus;
use App\Livewire\Pages\Marketplace\MarketplaceExports;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StatusFilterUrlTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_filters_initialize_from_url_and_validate_values(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::withQueryParams(['status' => 'exported', 'marketplace' => 'etsy'])
            ->test(MarketplaceExports::class)
            ->assertSet('status', 'exported')
            ->assertSet('marketplace', 'etsy')
            ->set('status', 'all')
            ->set('marketplace', 'amazon')
            ->assertSet('status', 'all')
            ->assertSet('marketplace', 'amazon');

        Livewire::withQueryParams(['status' => 'invalid', 'marketplace' => 'invalid'])
            ->test(MarketplaceExports::class)
            ->assertSet('status', 'unexported')
            ->assertSet('marketplace', 'all');
    }

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