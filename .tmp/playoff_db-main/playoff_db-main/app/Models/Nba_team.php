<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Nba_team extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'team_name',
        'team_name_accent',
        'team_abb',
        'team_color',
        'team_id',
        'kind',
        'stat_name',
        'init_letters',
        'pickup_name',
        'city',
        'parent_id',
    ];

    public function team_icon($style)
    {
        if ($style == "L") {
            return "https://cdn.nba.com/logos/nba/" . $this->team_id . "/global/L/logo.svg";
        } else {
            return "https://cdn.nba.com/logos/nba/" . $this->team_id . "/global/D/logo.svg";
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

    public function related_teams()
    {
        return $this->hasMany(Nba_team::class, 'parent_id')
            ->whereColumn('parent_id', '!=', 'id');
    }

    public function parent_team()
    {
        return $this->belongsTo(Nba_team::class, 'parent_id');
    }
    

    public function logo_nba_vesions()
    {
        return $this->hasMany(logo_nba_versions::class, 'id');
    }
}
