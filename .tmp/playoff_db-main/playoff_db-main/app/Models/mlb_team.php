<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class mlb_team extends Model
{
    use HasFactory;
    protected $table = "mlb_panini_teams";
    public $timestamps = false;
    protected $fillable = ['team_name'];
}
