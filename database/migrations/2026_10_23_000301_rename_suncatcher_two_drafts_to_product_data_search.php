<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('suncatcher_two_drafts') && ! Schema::hasTable('product_data_search')) {
            Schema::rename('suncatcher_two_drafts', 'product_data_search');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('product_data_search') && ! Schema::hasTable('suncatcher_two_drafts')) {
            Schema::rename('product_data_search', 'suncatcher_two_drafts');
        }
    }
};
