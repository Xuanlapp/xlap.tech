<?php

namespace App\Imports;

use App\Models\Panini_wnba_player;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class WnbaPlayersImport implements ToCollection
{
    private $sheetName;
    
    public function __construct($sheetName = null)
    {
        $this->sheetName = $sheetName;
    }
    
    public function collection(Collection $collection)
    {
        if ($collection->isEmpty()) {
            return;
        }
        
        // 使用工作表名稱作為預設球隊名稱
        $defaultTeam = $this->sheetName;
        
        $currentPlayer = null;
        $seasonStats = [];
        $careerStats = [];
        
        foreach ($collection as $index => $row) {
            // 跳過空行
            if (empty($row->filter()->toArray())) {
                continue;
            }
            
            $rowArray = $row->toArray();
            
            // 確保陣列至少有基本的欄位
            $rowArray = array_pad($rowArray, 12, '');
            
            // 步驟1: 檢查是否為球員名稱行 - 修正：檢查第二欄（B欄）
            if (!empty($rowArray[1])) {  // 改為檢查第二欄
                $playerNameCell = trim($rowArray[1]);
                
                // 跳過明顯的標題行和工作表名稱
                if (in_array(strtolower($playerNameCell), [
                    'g', 'fg%', 'ft%', '3pm', 'rpg', 'apg', 'stl', 'blk', 'pts', 'ppg',
                    'atlanta dream', 'chicago sky', 'connecticut sun', 'dallas wings',
                    'indiana fever', 'las vegas aces', 'minnesota lynx', 'new york liberty',
                    'phoenix mercury', 'seattle storm', 'washington mystics', 'retired',
                    'team', 'old team', 'new team', 'player'
                ])) {
                    continue;
                }
                
                // 特別處理：如果第二欄是 "Season" 且有其他標題欄位，這是標題行
                if (strtolower($playerNameCell) === 'season' && 
                    !empty($rowArray[2]) && strtolower(trim($rowArray[2])) === 'team') {
                    continue; // 跳過標題行，不當作球員名稱
                }
                
                // 檢查是否為球員名稱（第二欄有值，第三欄和第四欄為空）
                $isPlayerName = false;
                
                // 條件：第二欄有值，第3-5欄都是空的，且看起來像人名
                if (strlen($playerNameCell) > 2 && 
                    !is_numeric($playerNameCell) &&
                    empty(trim($rowArray[2])) && 
                    empty(trim($rowArray[3])) && 
                    empty(trim($rowArray[4])) &&
                    !in_array(strtolower($playerNameCell), ['season', 'career', 'team'])) {
                    $isPlayerName = true;
                }
                
                if ($isPlayerName) {
                    // 儲存前一個球員
                    if ($currentPlayer) {
                        $this->savePlayer($currentPlayer, $defaultTeam, $seasonStats, $careerStats);
                    }
                    
                    // 重置所有狀態
                    $currentPlayer = $playerNameCell;
                    $seasonStats = [];
                    $careerStats = [];
                    
                    continue;
                }
            }
            
            // 步驟2&3: 直接處理統計資料行（不需要區段標題）
            if ($currentPlayer && count($rowArray) >= 4) {
                
                // 跳過標題行
                $secondCell = !empty($rowArray[1]) ? strtolower(trim($rowArray[1])) : '';
                if ($secondCell === 'season' && !empty($rowArray[2]) && strtolower(trim($rowArray[2])) === 'team') {
                    continue;
                }
                
                // 檢查是否為有效的統計資料行
                if ($this->isValidStatRowAdjusted($rowArray)) {
                    
                    $season = trim($rowArray[1]);  // 第二欄是 season 或 "Career"
                    $team = trim($rowArray[2]);    // 第三欄是 team
                    
                    // 建立統計資料
                    $G = $this->safeInt($rowArray[3]);
                    $PTS = $this->safeInt($rowArray[11]);
                    
                    // 計算 PPG = PTS / G，保留小數點後一位
                    $PPG = ($G > 0) ? round($PTS / $G, 1) : 0.0;
                    
                    $statData = [
                        'season' => $season,
                        'team' => $team,
                        'G' => $G,
                        'FG%' => $this->safeFloat($rowArray[4]),
                        'FT%' => $this->safeFloat($rowArray[5]),
                        '3PM' => $this->safeInt($rowArray[6]),
                        'RPG' => $this->safeFloat($rowArray[7]),
                        'APG' => $this->safeFloat($rowArray[8]),
                        'STL' => $this->safeInt($rowArray[9]),
                        'BLK' => $this->safeInt($rowArray[10]),
                        'PTS' => $PTS,
                        'PPG' => $PPG    // 計算得出的 PPG
                    ];
                    
                    // 根據第二欄判斷是 Season 還是 Career
                    if (strtolower($season) === 'career') {
                        $careerStats = $statData;
                    } else {
                        // 假設是年份格式（如 "2018-19"）
                        $seasonStats[] = $statData;
                    }
                }
            }
        }
        
        // 儲存最後一個球員
        if ($currentPlayer) {
            $this->savePlayer($currentPlayer, $defaultTeam, $seasonStats, $careerStats);
        }
    }
    
    /**
     * 檢查是否為有效的統計資料行 - 修正版本
     */
    private function isValidStatRowAdjusted($rowArray)
    {
        // 第二欄不能是標題 "Season"，但可以是 "Career" 或年份
        $secondCell = !empty($rowArray[1]) ? strtolower(trim($rowArray[1])) : '';
        if (in_array($secondCell, ['season', 'team'])) {
            return false;
        }
        
        // 第二欄和第三欄必須有值
        if (empty(trim($rowArray[1])) || empty(trim($rowArray[2]))) {
            return false;
        }
        
        // 第四欄（G）必須是有效的正整數
        if (!isset($rowArray[3]) || !is_numeric($rowArray[3]) || $rowArray[3] <= 0) {
            return false;
        }
        
        return true;
    }
    
    /**
     * 安全的整數轉換
     */
    private function safeInt($value)
    {
        // 如果是空值或null，返回0
        if (empty($value) && $value !== 0 && $value !== '0') {
            return 0;
        }
        
        // 轉換為字符串並清理
        $cleanValue = trim((string)$value);
        
        // 移除千分位分隔符和其他非數字字符（保留負號）
        $cleanValue = preg_replace('/[^0-9\-]/', '', $cleanValue);
        
        // 檢查是否為有效數字
        if (is_numeric($cleanValue)) {
            return (int)$cleanValue;
        }
        
        // 記錄無法轉換的值
        \Log::warning("Could not convert to int: '{$value}' (cleaned: '{$cleanValue}')");
        return 0;
    }
    
    /**
     * 安全的浮點數轉換
     */
    private function safeFloat($value)
    {
        // 如果是空值或null，返回0.0
        if (empty($value) && $value !== 0 && $value !== '0') {
            return 0.0;
        }
        
        // 轉換為字符串並清理
        $cleanValue = trim((string)$value);
        
        // 移除千分位分隔符和其他非數字字符（保留小數點和負號）
        $cleanValue = preg_replace('/[^0-9.\-]/', '', $cleanValue);
        
        // 檢查是否為有效數字
        if (is_numeric($cleanValue)) {
            return (float)$cleanValue;
        }
        
        // 記錄無法轉換的值
        \Log::warning("Could not convert to float: '{$value}' (cleaned: '{$cleanValue}')");
        return 0.0;
    }
    
    private function savePlayer($playerName, $defaultTeam, $seasonStats, $careerStats)
    {
        // 檢查球員是否已存在
        $existingPlayer = Panini_wnba_player::where('player', $playerName)->first();
        
        if ($existingPlayer) {
            // 球員已存在，只更新統計資料
            $existingPlayer->update([
                'stat' => !empty($seasonStats) ? $seasonStats : null,
                'career_stat' => !empty($careerStats) ? $careerStats : null,
            ]);
        } else {
            // 球員不存在，創建新記錄
            
            // 判斷球員狀態
            $retire = 'N';
            $status = null;
            $team = $defaultTeam;
            
            // 從工作表名稱判斷狀態
            if (stripos($this->sheetName, 'retired') !== false || 
                stripos($this->sheetName, 'retire') !== false) {
                $retire = 'Y';
            } elseif (stripos($this->sheetName, 'rookie') !== false || 
                      stripos($this->sheetName, 'rookies') !== false) {
                $status = 'Rookie';
            }
            
            // 從最新賽季統計或職業生涯統計取得球隊資訊
            if (!empty($seasonStats)) {
                $latestSeason = collect($seasonStats)->sortByDesc('season')->first();
                if ($latestSeason && !empty($latestSeason['team'])) {
                    $team = $latestSeason['team'];
                }
            } elseif (!empty($careerStats) && !empty($careerStats['team'])) {
                // 如果沒有季賽統計，從職業生涯統計取得球隊
                $team = $careerStats['team'];
            }
            
            // 清理球隊名稱
            if (stripos($team, 'season') !== false || stripos($team, 'wnba') !== false) {
                $team = $defaultTeam; // 回到工作表名稱
            }
            
            // 取得最新年份
            $teamYear = null;
            if (!empty($seasonStats)) {
                $teamYear = collect($seasonStats)->pluck('season')->max();
            }
            
            // 建立新球員記錄
            Panini_wnba_player::create([
                'player' => $playerName,
                'team' => $team,
                'full_pos' => null,
                'team_year' => $teamYear,
                'retire' => $retire,
                'status' => $status,
                'stat' => !empty($seasonStats) ? $seasonStats : null,
                'career_stat' => !empty($careerStats) ? $careerStats : null,
                'marked' => 0
            ]);
        }
    }
}

