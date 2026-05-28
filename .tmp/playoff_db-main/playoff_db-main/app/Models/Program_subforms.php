<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Program_subforms extends Model
{
    use HasFactory;

    protected $table = 'program_subforms';
    protected $fillable = [
        'config',
        'form',
        'insert_name',
        'cards',
        'seq',
        'substrate',
        'foil',
        'autos',
        'pms',
        'prepress_color_front',
        'lam_front',
        'prepress_color_back',
        'lam_back',
        'coating_front',
        'coating_back',
        'panini',
        'leagues',
        'stamped',
        'none',
        'panini_binder',
        'total_inc_sht',
        'main_form_id',
        'program_id',
        'form_main',
        'form_group',
        'color_group',
        'insert_name_group',
        'prepress_color_back_json',
        'prepress_color_front_json',

    ];

    public function mainProgram()
    {
        return $this->belongsTo(Program_forms::class, 'main_form_id');
    }

    public function program()
    {
        return $this->belongsTo(Programs::class, 'program_id');
    }
}
