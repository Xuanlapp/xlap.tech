# UpdatePlayerStat.php 修改記錄

## 修改日期

2024-03-21

## 檔案位置

`app/Http/Livewire/Modals/Nba/UpdatePlayerStat.php`

## 主要修改內容

### 1. 修改球員統計數據查詢方式

#### 問題

原本的代碼直接在 HasMany 關係上調用 `isEmpty()`，這是不允許的。

#### 修改前

```php
$playerStats = $player->stats();
if ($playerStats->isEmpty()) {
    throw new Exception("No stats found for player ID: {$playerId}");
}
```

#### 修改後

```php
$playerStats = $player->stats()->get();
Log::info("Player stats retrieved", [
    'player_id' => $playerId,
    'stats_count' => $playerStats->count(),
    'first_stat' => $playerStats->first()
]);

if ($playerStats->count() === 0) {
    throw new Exception("No stats found for player ID: {$playerId}");
}
```

#### 改進

-   先獲取集合再檢查是否為空
-   添加了詳細的日誌記錄，方便調試
-   使用更安全的 `count()` 方法檢查

### 2. 修改賽季排序欄位

#### 問題

原本使用 `season` 作為排序欄位，但資料庫中實際使用的是 `year`。

#### 修改前

```php
$stats = $player->stats()->orderBy('season', 'desc')->get();
```

#### 修改後

```php
$stats = $player->stats()->orderBy('year', 'desc')->get();
```

#### 相關修改

-   日誌記錄也相應更新：

```php
// 修改前
'seasons' => $stats->pluck('season')->toArray()

// 修改後
'years' => $stats->pluck('year')->toArray()
```

## 待處理問題

### 1. 數據保存邏輯優化

需要移除多餘的 `save()` 調用：

```php
$player->update([...]);
if (!$player->save()) {  // 多餘的操作
    throw new Exception('Failed to save career stats');
}
```

### 2. 平均值計算邏輯優化

需要檢查並可能重新實現以下統計數據的計算：

-   RPG (Rebounds Per Game)
-   APG (Assists Per Game)
-   PPG (Points Per Game)

### 3. 錯誤處理機制改進

當前的錯誤處理可以更完善：

```php
catch (\Exception $e) {
    Log::error("Error updating player {$playerId}: " . $e->getMessage());
    $this->removePlayerIdFromLog($playerId);
}
```

建議改進：

-   添加更詳細的錯誤信息
-   考慮是否需要立即移除 playerId
-   添加重試機制

## 下一步建議

1. 移除多餘的 `save()` 調用
2. 優化平均值計算邏輯
3. 改進錯誤處理機制
4. 添加更多的數據驗證

## 注意事項

-   所有修改都需要經過測試
-   需要確保數據計算的準確性
-   建議添加單元測試
