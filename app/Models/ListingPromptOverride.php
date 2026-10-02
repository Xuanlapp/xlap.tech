<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ListingPromptOverride extends Model
{
    protected $fillable = ['product_id', 'marketplace', 'content', 'input_mapping', 'output_mapping', 'updated_by'];

    protected function casts(): array
    {
        return ['input_mapping' => 'array', 'output_mapping' => 'array'];
    }
}
