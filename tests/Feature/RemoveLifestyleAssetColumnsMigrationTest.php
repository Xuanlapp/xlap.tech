<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductDesignAsset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RemoveLifestyleAssetColumnsMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_lifestyle_urls_move_into_free_mockup_slots_before_drop(): void
    {
        Schema::table('product_design_assets', function ($table): void {
            $table->text('lifestyle1')->nullable();
            $table->text('lifestyle2')->nullable();
            $table->text('lifestyle3')->nullable();
        });

        $user = User::factory()->create();
        $product = Product::query()->firstOrFail();
        $asset = ProductDesignAsset::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'item_number' => 1,
            'keyword' => 'legacy lifestyle test',
            'mockup1' => '/storage/mockup/first.png',
        ]);
        DB::table('product_design_assets')->where('id', $asset->id)->update([
            'lifestyle1' => '/storage/lifestyle/first.png',
            'lifestyle2' => '/storage/lifestyle/second.png',
            'lifestyle3' => '/storage/lifestyle/second.png',
        ]);

        $migration = require database_path('migrations/2026_10_27_000001_remove_lifestyle_fields_from_product_design_assets.php');
        $migration->up();

        $asset->refresh();
        $this->assertSame('/storage/mockup/first.png', $asset->mockup1);
        $this->assertSame('/storage/lifestyle/first.png', $asset->mockup2);
        $this->assertSame('/storage/lifestyle/second.png', $asset->mockup3);
        $this->assertFalse(Schema::hasColumn('product_design_assets', 'lifestyle1'));
        $this->assertFalse(Schema::hasColumn('product_design_assets', 'lifestyle2'));
        $this->assertFalse(Schema::hasColumn('product_design_assets', 'lifestyle3'));
    }
}
