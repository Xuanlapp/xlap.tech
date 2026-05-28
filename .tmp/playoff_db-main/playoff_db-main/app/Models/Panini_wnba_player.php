<?php

namespace App\Models;

use App\Models\wnba_team;
use App\Models\Traits\FormatStatsTrait;
use Illuminate\Database\Eloquent\Model;

class Panini_wnba_player extends Model
{
    use FormatStatsTrait;
    
    protected $table = 'panini_wnba_players';
    protected $fillable = [
        'player',
        'team',
        'full_pos',
        'team_year',
        'retire',
        'status',
        'stat',
        'career_stat',
        'marked',
        'panini_id'
    ];

    protected $casts = [
        'stat' => 'array',
        'career_stat' => 'array',
    ];

    public function show_stat_title()
    {
        return ['Season', 'Team', 'G', 'FG%', 'FT%', '3PM', 'RPG', 'APG', 'STL', 'BLK', 'PTS', 'PPG'];
    }

    public function show_career_header()
    {
        return ['G', 'FG%', 'FT%', '3PM', 'RPG', 'APG', 'STL', 'BLK', 'PTS', 'PPG'];
    }

    /**
     * 獲取最新一年的統計數據
     */
    public function getLatestSeasonStat()
    {
        if (empty($this->stat) || !is_array($this->stat)) {
            return null;
        }

        // 如果stat是多年數據的數組，取最新的一年
        if (isset($this->stat[0]) && is_array($this->stat[0])) {
            // 找到最新年份的數據
            $latestStat = null;
            $latestYear = 0;
            
            foreach ($this->stat as $seasonStat) {
                if (isset($seasonStat['season'])) {
                    $year = $this->extractYearFromSeason($seasonStat['season']);
                    if ($year > $latestYear) {
                        $latestYear = $year;
                        $latestStat = $seasonStat;
                    }
                }
            }
            return $latestStat;
        }
        
        // 如果stat直接是單年數據
        return $this->stat;
    }

    /**
     * 從season字符串中提取年份
     */
    private function extractYearFromSeason($season)
    {
        if (preg_match('/(\d{4})/', $season, $matches)) {
            return (int)$matches[1];
        }
        return 0;
    }

    /**
     * 格式化並返回最新一年的統計數據
     */
    public function formatLatestSeasonStat()
    {
        $latestStat = $this->getLatestSeasonStat();
        if (!$latestStat) {
            return '';
        }

        $stat = [];
        $stat[] = $latestStat['season'] ?? '-';
        $stat[] = $latestStat['team'] ?? '-';
        
        // 統計數據欄位 - 使用大寫字段名
        $statFields = ['G', 'FG%', 'FT%', '3PM', 'RPG', 'APG', 'STL', 'BLK', 'PTS', 'PPG'];
        
        foreach ($statFields as $field) {
            $value = $latestStat[$field] ?? '-';
            
            // 格式化數據
            if (is_numeric($value) && $value !== '-') {
                if (in_array($field, ['FG%', 'FT%'])) {
                    $value = $this->formatPercentage($value);
                } elseif (in_array($field, ['RPG', 'APG', 'PPG'])) {
                    $value = $this->formatAverage($value);
                }
            }
            
            $stat[] = $value;
        }
        
        return implode("\t", $stat) . " ";
    }

    /**
     * 格式化並返回生涯統計數據
     */
    public function formatCareerStat()
    {
        if (empty($this->career_stat) || !is_array($this->career_stat)) {
            return '';
        }

        $stat = [];
        
        // 加入 team 欄位 (如 "11 WNBA Seasons")
        $stat[] = $this->career_stat['team'] ?? '-';
        
        $statFields = ['G', 'FG%', 'FT%', '3PM', 'RPG', 'APG', 'STL', 'BLK', 'PTS', 'PPG'];
        
        foreach ($statFields as $field) {
            $value = $this->career_stat[$field] ?? '-';
            
            // 格式化數據
            if (is_numeric($value) && $value !== '-') {
                if (in_array($field, ['FG%', 'FT%'])) {
                    $value = $this->formatPercentage($value);
                } elseif (in_array($field, ['RPG', 'APG', 'PPG'])) {
                    $value = $this->formatAverage($value);
                }
            }
            
            $stat[] = $value;
        }
        
        return implode("\t", $stat);
    }

    /**
     * 檢查是否有統計數據
     */
    public function hasStats()
    {
        return !empty($this->stat) && is_array($this->stat);
    }

    /**
     * 檢查是否有生涯數據
     */
    public function hasCareerStats()
    {
        return !empty($this->career_stat) && is_array($this->career_stat);
    }

    public function team_icon($style)
    {
        $team = wnba_team::where('team_name', $this->team)->first();
        if ($team && $team->team_abb !== null) {
            $logoType = $style == "L" ? "L" : "D";
            return "https://cdn.wnba.com/logos/nba/{$team->team_id}/global/{$logoType}/logo.svg";
        }
        return 0;
    }
}
