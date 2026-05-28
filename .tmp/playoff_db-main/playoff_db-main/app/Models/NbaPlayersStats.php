<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NbaPlayersStats extends Model
{
    protected $fillable = [
        'nba_player_id',
        'name',
        'active',
        'g',
        'fgm',
        'fga',
        'ftm',
        'fta',
        'fg%',
        'ft%',
        '3pm',
        'rpg',
        'apg',
        'stl',
        'blk',
        'pts',
        'ppg',
        'last_year_career_stats',
        'career_stats_last_updated_at',
        'marked',
        'latest_year'
    ];

    protected $casts = [
        'active' => 'boolean',
        'last_year_career_stats' => 'array',
        'career_stats_last_updated_at' => 'datetime',
        'g' => 'integer',
        'fgm' => 'integer',
        'fga' => 'integer',
        'ftm' => 'integer',
        'fta' => 'integer',
        'fg%' => 'float',
        'ft%' => 'float',
        '3pm' => 'integer',
        'rpg' => 'float',
        'apg' => 'float',
        'stl' => 'integer',
        'blk' => 'integer',
        'pts' => 'integer',
        'ppg' => 'float'
    ];
}
