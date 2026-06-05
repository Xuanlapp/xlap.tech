<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_design_assets', function (Blueprint $table): void {
            $table->string('title')->nullable()->after('image_link');
            $table->text('description')->nullable()->after('title');
            $table->text('bullet_point_1')->nullable()->after('description');
            $table->text('bullet_point_2')->nullable()->after('bullet_point_1');
            $table->text('bullet_point_3')->nullable()->after('bullet_point_2');
            $table->text('bullet_point_4')->nullable()->after('bullet_point_3');
            $table->text('bullet_point_5')->nullable()->after('bullet_point_4');
            $table->string('generic_keyword')->nullable()->after('bullet_point_5');
            $table->text('tags')->nullable()->after('generic_keyword');
        });
    }

    public function down(): void
    {
        Schema::table('product_design_assets', function (Blueprint $table): void {
            $table->dropColumn([
                'title',
                'description',
                'bullet_point_1',
                'bullet_point_2',
                'bullet_point_3',
                'bullet_point_4',
                'bullet_point_5',
                'generic_keyword',
                'tags',
            ]);
        });
    }
};
