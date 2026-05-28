<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\FormatStatsTrait;

class PaniniNbaPlayerStats extends Model
{
    use FormatStatsTrait;
    protected $table = 'panini_nba_players_stats';
    protected $fillable = [
        'id',
        'player_id',
        'year',
        'team_full',
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
        'is_combined_team_stat',
        'last_updated_at',
        'original_team_stats'
    ];

    protected $casts = [
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

    // Define the NBA stats as an array
    private $nba_stat_arr = [
        "g" => "G",
        "fg%" => "FG%",
        "ft%" => "FT%",
        "3pm" => "3PM",
        "rpg" => "RPG",
        "apg" => "APG",
        "stl" => "STL",
        "blk" => "BLK",
        "pts" => "PTS",
        "ppg" => "PPG"
    ];

    /**
     * 顯示 NBA 統計數據
     */
    public function show_nba_stat()
    {
        $stat = [];
        $stat[] = $this->year;
        $stat[] = strtoupper(($this->team_full === null || $this->is_combined_team_stat == 1) ? $this->team : $this->team_full);
        foreach ($this->nba_stat_arr as $key => $label) {
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
