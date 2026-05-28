<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Program_forms extends Model
{
    use HasFactory;

    protected $table = 'program_forms';

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
        'program_id',
        'plist',
        'photo',
        'color',
        'edit',
        'TR',
        'rd1',
        'rd2',
        'ok',
        'files',
        'insert_short_name',
        'prepress_color_back_json',
        'prepress_color_front_json',
    ];

    public function subPrograms()
    {
        return $this->hasMany(Program_subforms::class, 'main_form_id');
    }

    public function program()
    {
        return $this->belongsTo(Programs::class, 'program_id');
    }
}
