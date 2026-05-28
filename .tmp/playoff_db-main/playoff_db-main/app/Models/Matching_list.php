<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Matching_list extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = ['panini_player_id', 'mlb_player_id'];
}
