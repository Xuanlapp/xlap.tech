# WNBA 球員資料匯入功能技術文檔

## 概述

本文檔記錄了 WNBA 球員資料庫系統的 Excel 匯入功能完整開發過程，包括需求分析、資料庫設計、匯入邏輯實作，以及開發過程中遇到的問題和解決方案。

## 目錄

1. [需求分析](#需求分析)
2. [資料庫設計](#資料庫設計)
3. [Excel 格式分析](#excel-格式分析)
4. [系統架構](#系統架構)
5. [開發過程](#開發過程)
6. [問題與解決方案](#問題與解決方案)
7. [最終實作](#最終實作)
8. [使用指南](#使用指南)

## 需求分析

### 功能需求

1. **資料表建立**：建立 `panini_wnba_players` 資料表
2. **Excel 匯入**：支援多工作表 Excel 檔案匯入
3. **工作表識別**：
   - 工作表名稱對應球隊名稱
   - "Retired" 工作表：球員標記為退休
   - "Rookies" 工作表：球員標記為新秀
4. **統計資料處理**：支援 Season 和 Career 統計資料
5. **更新策略**：已存在球員只更新統計資料，保留其他欄位

### 技術需求

- Laravel 框架
- Livewire 組件
- Laravel Excel 套件 (maatwebsite/excel)
- PhpSpreadsheet 引擎
- SQLite 資料庫

## 資料庫設計

### Migration 結構

```php
Schema::create('panini_wnba_players', function (Blueprint $table) {
    $table->id();
    $table->string('player')->unique();           // 球員姓名
    $table->string('team')->nullable();           // 球隊
    $table->string('full_pos')->nullable();       // 位置
    $table->string('team_year')->nullable();      // 球隊年份
    $table->enum('retire', ['Y', 'N'])->default('N'); // 是否退休
    $table->string('status')->nullable();         // 狀態 (Rookie 等)
    $table->json('stat')->nullable();             // 季賽統計 (JSON)
    $table->json('career_stat')->nullable();      // 職業生涯統計 (JSON)
    $table->tinyInteger('marked')->default(0);    // 標記欄位
    $table->timestamps();
});
```

### 索引設計

```php
$table->index('player');
$table->index('team');
$table->index('retire');
$table->index('marked');
```

## Excel 格式分析

### 工作表結構

每個工作表代表一個球隊或特殊分類：
- **一般球隊**：Atlanta Dream, Chicago Sky, Connecticut Sun 等
- **特殊分類**：Retired (退休球員), Rookies (新秀)

### 資料格式

```
Row 1: [工作表標題]
Row 2: [空行]
Row 3: [球員姓名]                     <- 球員名稱在 B 欄
Row 4: ["Season", "Team", "G", ...]   <- 統計表頭
Row 5: ["2018-19", "NC State", 34, ...] <- Season 統計資料
Row 6: ["Career", "2 NCAA Seasons", 69, ...] <- Career 統計資料
Row 7: [空行]
Row 8: [下一個球員姓名]
...
```

### 關鍵發現

1. **球員名稱位置**：在第二欄 (B欄)，不是第一欄 (A欄)
2. **統計資料識別**：透過第二欄內容判斷是年份還是 "Career"
3. **無區段標題**：不需要單獨的區段標題行
4. **欄位對應**：
   - 欄位 B：Season 年份或 "Career"
   - 欄位 C：Team 名稱
   - 欄位 D+：統計數據 (G, FG%, FT%, 3PM, RPG, APG, STL, BLK, PTS, PPG)

## 系統架構

### 檔案結構

```
app/
├── Http/Livewire/Pages/Wnba/
│   └── NewPlayer.php              # Livewire 組件
├── Imports/
│   └── WnbaPlayersImport.php      # Excel 匯入處理類別
├── Models/
│   └── PaniniWnbaPlayer.php       # Eloquent Model
└── database/migrations/
    └── create_panini_wnba_players_table.php
```

### 組件關係

```
NewPlayer (Livewire)
    ↓ uploadExcel()
PhpSpreadsheet Reader
    ↓ 逐工作表處理
WnbaPlayersImport
    ↓ collection()
球員資料處理
    ↓ savePlayer()
PaniniWnbaPlayer Model
```

## 開發過程

### 第一階段：基礎建設

1. **建立 Migration**
   ```bash
   php artisan make:migration create_panini_wnba_players_table
   ```

2. **建立 Model**
   ```bash
   php artisan make:model PaniniWnbaPlayer
   ```

3. **建立 Import 類別**
   ```bash
   php artisan make:import WnbaPlayersImport
   ```

### 第二階段：Excel 匯入功能

1. **Livewire 組件**：加入檔案上傳功能
2. **工作表讀取**：使用 PhpSpreadsheet 讀取所有工作表
3. **資料處理**：逐行解析球員和統計資料

### 第三階段：問題解決與優化

經歷多次迭代，解決格式識別、統計資料收集等問題。

## 問題與解決方案

### 問題 1：工作表名稱識別錯誤

**問題描述**：
```
Worksheet 'Atlanta' could not be found, out of bounds
```

**原因**：硬編碼工作表名稱與實際不符

**解決方案**：
```php
// 改用 PhpSpreadsheet 動態讀取所有工作表
foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
    $sheetName = $worksheet->getTitle();
    // 處理每個工作表
}
```

### 問題 2：球員名稱識別錯誤

**問題描述**：無法正確識別球員名稱

**原因**：球員名稱在第二欄 (B欄)，程式檢查第一欄 (A欄)

**解決方案**：
```php
// 修正前
if (!empty($rowArray[0])) {
    $firstCell = trim($rowArray[0]);

// 修正後  
if (!empty($rowArray[1])) {
    $playerNameCell = trim($rowArray[1]);
```

### 問題 3：統計區段識別失敗

**問題描述**：程式找不到 "Season" 和 "Career" 區段

**原因**：錯誤理解 Excel 格式，以為有單獨的區段標題行

**解決方案**：
```php
// 移除錯誤的區段概念，直接根據第二欄內容判斷
if (strtolower($season) === 'career') {
    $careerStats = $statData;
} else {
    // 假設是年份格式（如 "2018-19"）
    $seasonStats[] = $statData;
}
```

### 問題 4：標題行被當作球員名稱

**問題描述**：標題行 "Season" 被識別為球員名稱

**解決方案**：
```php
// 特別處理標題行
if (strtolower($playerNameCell) === 'season' && 
    !empty($rowArray[2]) && strtolower(trim($rowArray[2])) === 'team') {
    continue; // 跳過標題行
}
```

### 問題 5：統計資料無法寫入資料庫

**問題描述**：球員被正確識別，但統計資料都是 NULL

**原因**：
1. 區段識別邏輯錯誤
2. 統計資料驗證過於嚴格
3. 資料傳遞過程中變數重置

**解決方案**：
1. 簡化處理邏輯，移除錯誤的區段概念
2. 放寬驗證條件，允許 "Career" 作為有效標識符
3. 確保統計資料正確傳遞到 savePlayer 方法

### 問題 6：重複匯入覆蓋資料

**問題描述**：重新匯入會覆蓋手動設定的球員資料

**解決方案**：
```php
// 檢查球員是否已存在
$existingPlayer = PaniniWnbaPlayer::where('player', $playerName)->first();

if ($existingPlayer) {
    // 只更新統計資料
    $existingPlayer->update([
        'stat' => !empty($seasonStats) ? $seasonStats : null,
        'career_stat' => !empty($careerStats) ? $careerStats : null,
    ]);
} else {
    // 創建新球員記錄
    PaniniWnbaPlayer::create([...]);
}
```

## 最終實作

### 核心處理邏輯

```php
public function collection(Collection $collection)
{
    $currentPlayer = null;
    $seasonStats = [];
    $careerStats = [];
    
    foreach ($collection as $index => $row) {
        $rowArray = $row->toArray();
        $rowArray = array_pad($rowArray, 12, '');
        
        // 步驟1: 識別球員名稱
        if (!empty($rowArray[1])) {
            $playerNameCell = trim($rowArray[1]);
            
            // 跳過標題行和工作表名稱
            if (/* 各種排除條件 */) {
                continue;
            }
            
            if ($isPlayerName) {
                // 儲存前一個球員
                if ($currentPlayer) {
                    $this->savePlayer($currentPlayer, $defaultTeam, $seasonStats, $careerStats);
                }
                
                // 重置狀態
                $currentPlayer = $playerNameCell;
                $seasonStats = [];
                $careerStats = [];
            }
        }
        
        // 步驟2: 處理統計資料
        if ($currentPlayer && count($rowArray) >= 4) {
            // 跳過標題行
            if ($secondCell === 'season' && $thirdCell === 'team') {
                continue;
            }
            
            if ($this->isValidStatRow($rowArray)) {
                $statData = [/* 統計資料 */];
                
                // 根據第二欄判斷類型
                if (strtolower($season) === 'career') {
                    $careerStats = $statData;
                } else {
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
```

### 統計資料格式

```json
{
  "stat": [
    {
      "season": "2018-19",
      "team": "NC State",
      "G": 34,
      "FG%": 0.416,
      "FT%": 0.716,
      "3PM": 69,
      "RPG": 7.2,
      "APG": 2.8,
      "STL": 38,
      "BLK": 26,
      "PTS": 541,
      "PPG": 15.9
    }
  ],
  "career_stat": {
    "season": "Career",
    "team": "2 NCAA Seasons",
    "G": 69,
    "FG%": 0.415,
    "FT%": 0.716,
    "3PM": 108,
    "RPG": 6.6,
    "APG": 2.2,
    "STL": 73,
    "BLK": 52,
    "PTS": 986,
    "PPG": 14.3
  }
}
```

## 使用指南

### 匯入步驟

1. **準備 Excel 檔案**：
   - 支援 .xlsx 和 .xls 格式
   - 檔案大小限制：10MB
   - 確保工作表名稱正確對應球隊名稱

2. **執行匯入**：
   - 在 WNBA New Player 頁面選擇檔案
   - 點擊匯入按鈕
   - 等待處理完成

3. **檢查結果**：
   - 查看成功訊息
   - 檢查球員列表
   - 確認統計資料正確寫入

### 匯入規則

1. **新球員**：創建完整記錄，包括基本資料和統計資料
2. **現有球員**：只更新 `stat` 和 `career_stat` 欄位
3. **工作表分類**：
   - 一般工作表：設定為對應球隊
   - "Retired"：標記 `retire = 'Y'`
   - "Rookies"：設定 `status = 'Rookie'`

### 錯誤處理

- 單一工作表處理失敗不影響其他工作表
- 詳細錯誤日誌記錄在 `storage/logs/laravel.log`
- 匯入過程中顯示處理狀態

## 技術細節

### 效能考量

1. **記憶體管理**：使用 Collection 逐行處理，避免載入整個檔案
2. **資料庫操作**：批量處理減少 I/O 操作
3. **錯誤恢復**：單一錯誤不中斷整個匯入過程

### 安全性

1. **檔案驗證**：限制檔案類型和大小
2. **資料清理**：過濾和驗證輸入資料
3. **SQL 注入防護**：使用 Eloquent ORM

### 可擴展性

1. **模組化設計**：匯入邏輯獨立於 UI 組件
2. **配置靈活**：支援不同的工作表命名規則
3. **格式適應**：可輕鬆調整以支援其他 Excel 格式

## 結語

這個 WNBA 球員資料匯入功能經過多次迭代和問題解決，最終實現了穩定、靈活的資料匯入系統。關鍵成功因素包括：

1. **詳細的格式分析**：正確理解 Excel 資料結構
2. **漸進式開發**：逐步解決問題，持續改進
3. **充分的日誌記錄**：便於問題診斷和調試
4. **靈活的更新策略**：平衡資料完整性和更新需求

此文檔可作為類似功能開發的參考，也為系統維護提供了完整的技術資料。 