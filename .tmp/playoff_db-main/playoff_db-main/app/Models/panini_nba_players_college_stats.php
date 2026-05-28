<?php

namespace App\Models;

use App\Models\Traits\FormatStatsTrait;
use Dflydev\DotAccessData\Data;
use Illuminate\Database\Eloquent\Model;

class panini_nba_players_college_stats extends Model
{
    use FormatStatsTrait;

    protected $fillable = [
        'player_id',
        'year',
        'team',
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
        'extra',
        'team_full'
    ];

    private $college_stat_arr = [
        'g' => 'G',
        'fg%' => 'FG%',
        'ft%' => 'FT%',
        '3pm' => '3PM',
        'rpg' => 'RPG',
        'apg' => 'APG',
        'stl' => 'STL',
        'blk' => 'BLK',
        'pts' => 'PTS',
        'ppg' => 'PPG'
    ];


    public function get_college_stat()
    {
        return array_values($this->college_stat_arr);
    }


    public function show_college_stats()
    {
        $stat = [];
        $stat[] = $this->year;
        $stat[] = $this->team;
        foreach ($this->college_stat_arr as $key => $label) {
            $value = $this->{$key} ?? "-";

            // 檢查值是否為數字且不是 "-"
            if (is_numeric($value) && $value !== '-') {
                // 根據統計類型進行格式化
                if (in_array($key, ['fg%', 'ft%', '3p%'])) {
                    $value = $this->formatPercentage($value);
                } elseif (in_array($key, ['rpg', 'apg', 'ppg'])) {
                    $value = $this->formatAverage($value);
                }
            }

            $stat[] = $value;
        }
        return $stat;
    }
}
