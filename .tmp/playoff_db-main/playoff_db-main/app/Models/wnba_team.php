<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class wnba_team extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['team_name', 'team_abb', 'team_color', 'team_id'];

    public function team_icon($style)
    {
        if ($style == "L") {
            return "https://cdn.wnba.com/logos/nba/" . $this->team_id . "/global/L/logo.svg";
        } else {
            return "https://cdn.wnba.com/logos/nba/" . $this->team_id . "/global/D/logo.svg";
        }
    }

    public function team_color()
    {
        if ($this->team_abb !== null) {
            return $this->team_color;
        } else {
            return "#000000";
        }
    }
}
