<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_design_assets')) {
            return;
        }

        $lifestyleColumns = ['lifestyle1', 'lifestyle2', 'lifestyle3'];
        $existingColumns = array_values(array_filter($lifestyleColumns, fn (string $column): bool => Schema::hasColumn('product_design_assets', $column)));

        if ($existingColumns !== []) {
            DB::table('product_design_assets')
                ->select(['id', ...$existingColumns, ...array_map(fn (int $slot): string => "mockup{$slot}", range(1, 11))])
                ->orderBy('id')
                ->chunkById(100, function ($assets) use ($existingColumns): void {
                    foreach ($assets as $asset) {
                        $mockups = [];
                        for ($slot = 1; $slot <= 11; $slot++) {
                            $mockups[$slot] = $asset->{"mockup{$slot}"} ?? null;
                        }

                        $updates = [];
                        foreach ($existingColumns as $column) {
                            $url = $asset->{$column};
                            if (! is_string($url) || trim($url) === '' || in_array($url, $mockups, true)) {
                                continue;
                            }

                            $emptySlot = null;
                            foreach ($mockups as $slot => $mockup) {
                                if (! is_string($mockup) || trim($mockup) === '') {
                                    $emptySlot = $slot;
                                    break;
                                }
                            }

                            if ($emptySlot === null) {
                                throw new RuntimeException("Cannot migrate lifestyle image for product_design_asset {$asset->id}: all mockup slots are occupied.");
                            }

                            $mockups[$emptySlot] = $url;
                            $updates["mockup{$emptySlot}"] = $url;
                        }

                        if ($updates !== []) {
                            DB::table('product_design_assets')->where('id', $asset->id)->update($updates);
                        }
                    }
                });

            Schema::table('product_design_assets', function (Blueprint $table) use ($existingColumns): void {
                $table->dropColumn($existingColumns);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('product_design_assets')) {
            return;
        }

        Schema::table('product_design_assets', function (Blueprint $table): void {
            foreach (['lifestyle1', 'lifestyle2', 'lifestyle3'] as $column) {
                if (! Schema::hasColumn('product_design_assets', $column)) {
                    $table->text($column)->nullable();
                }
            }
        });
    }
};
