<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('panini_wnba_players', function (Blueprint $table) {
            $table->id();
            $table->string('player');
            $table->string('team')->nullable();
            $table->string('full_pos')->nullable(); // 球員位置
            $table->string('team_year')->nullable(); // 球隊年份
            $table->enum('retire', ['Y', 'N'])->default('N'); // 退休狀態
            $table->string('status')->nullable(); // 狀態
            $table->string('panini_id')->nullable()->default(null); // 狀態
            $table->json('stat')->nullable(); // 統計數據
            $table->json('career_stat')->nullable(); // 職業生涯統計
            $table->tinyInteger('marked')->default(0); // 標記狀態，用於篩選
            $table->timestamps();
            
            // 建立索引提升查詢效能
            $table->index('player');
            $table->index('team');
            $table->index('marked');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('panini_wnba_players');
    }
};
