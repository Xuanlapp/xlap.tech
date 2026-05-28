<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Insert_name_color extends Model
{
    use HasFactory;

    protected $table = 'press_color_memory';
    protected $fillable = [
        'config',
        'insert_name',
        'prepress_color_front',
        'prepress_color_back',
        'program_name',
        'insert_short_name',
        'sport',
        'year',
        'description'
    ];
    protected $casts = [
        'prepress_color_front' => 'array',
        'prepress_color_back' => 'array',
    ];
}
