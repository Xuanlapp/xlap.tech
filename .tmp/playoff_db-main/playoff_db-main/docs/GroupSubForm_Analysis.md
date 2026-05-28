# GroupSubForm 函數分析

## 概述

`GroupSubForm` 函數位於 `app/Services/Program/ProgramFunctionServices.php` 文件中，其主要目的是處理和組織指定程式ID (`$ProgramId`) 下的子表單 (`Program_subforms`)。它通過一系列步驟過濾、計數和分組子表單，以優化數據結構和後續處理流程。

## 主要功能

1.  **數據準備**：獲取與給定 `$ProgramId` 相關的所有子表單。
2.  **表單分類與計數**：
    *   區分主要表單（表單名稱中不含 `-` 的表單）。
    *   統計每個主要表單名稱出現的次數，以識別重複表單。
3.  **分組處理**：根據表單的重複情況和 `insert_name` 的相似性，將表單分配到不同的組中（表單組 `form_group`、顏色組 `color_group`、插入名稱組 `insert_name_group`）。

## 調用的內部輔助函數

`GroupSubForm` 函數在執行過程中，會調用以下幾個同一類中的 `private` 輔助函數來完成具體的分組邏輯：

1.  `assignFormGroups($ProgramId, $form, $status)`：
    *   **目的**：分配表單組 (`form_group`)。
    *   **邏輯**：根據傳入的 `$status` (`"Multi Form"` 或 `"Single Form"`) 和 `$form`（單個表單名稱或表單集合）來處理。它會基於 `insert_name` 的前幾個詞（目前是第一個詞）來將具有相似插入名稱前綴的子表單歸為一組，並將該組中第一個子表單的 ID 作為 `form_group` 的值。

2.  `assignColorGroups($ProgramId, $form, $status)`：
    *   **目的**：分配顏色組 (`color_group`)。
    *   **邏輯**：在已分配 `form_group` 的基礎上進行。它會檢查子表單的 `prepress_color_front_json` 字段（預印正面顏色，JSON 格式）。具有相同顏色組合（JSON 內容相同，順序不影響）的子表單會被歸為一組，並將該組中第一個子表單的 ID（或特定顏色組合首次出現時對應的子表單ID）作為 `color_group` 的值。

3.  `assignInsertNameGroups($ProgramId, $form, $status)`：
    *   **目的**：分配插入名稱組 (`insert_name_group`)。
    *   **邏輯**：在已分配 `color_group` 的基礎上進行。它會基於子表單的完整 `insert_name` 字段。具有完全相同 `insert_name` 的子表單會被歸為一組，並將該組中第一個子表單的 ID 作為 `insert_name_group` 的值。

4.  `assignMergeFormGroups($ProgramId, $formCollection)`：
    *   **目的**：處理多個 `insert_name` 相同但表單名稱不同的 "單一表單"（在 `GroupSubForm` 的第二輪處理中）。
    *   **邏輯**：將傳入的 `$formCollection`（包含多個具有相同 `insert_name` 的表單對象）中的所有表單合併到一個表單組。它會將集合中第一個表單的 ID 作為 `form_group` 的值賦予集合中的所有表單。接著，它會調用 `assignMergeColorGroups` 來進一步處理這些已合併表單的顏色分組。

5.  `assignMergeColorGroups($ProgramId, $Form_group)`：（由 `assignMergeFormGroups` 間接調用）
    *   **目的**：為已通過 `assignMergeFormGroups` 合併的表單組分配顏色組。
    *   **邏輯**：與 `assignColorGroups` 類似，但在指定的 `form_group` 內操作，根據 `prepress_color_front`（此處未使用JSON字段）進行分組。

6.  `assignMergeInsertNameGroups($ProgramId, $Form_group)`：（由 `assignMergeColorGroups` 間接調用）
    *   **目的**：為已通過 `assignMergeFormGroups` 和 `assignMergeColorGroups` 處理的表單組分配插入名稱組。
    *   **邏輯**：與 `assignInsertNameGroups` 類似，但在指定的 `form_group` 內操作，根據 `insert_name` 進行分組。


## 邏輯思路逐步分解

1.  **初始化和空值檢查**：
    *   接收 `$ProgramId` 作為參數。
    *   如果 `$ProgramId` 為 `null`，則函數直接返回，不執行任何操作。

2.  **獲取所有相關子表單**：
    *   從 `Program_subforms` 表中查詢所有 `program_id` 等于傳入的 `$ProgramId` 的記錄。

3.  **過濾出主要表單**：
    *   從所有子表單中篩選出表單名稱 (`form` 字段) 不包含連字符 (`-`) 的表單。這些被視為 "主要表單"。

4.  **統計主要表單的出現次數**：
    *   將主要表單按其 `form` 字段分組，並計算每個 `form` 名稱出現的次數。這有助於識別哪些表單名稱是重複的。

5.  **第一輪處理：處理重複表單（`"Multi Form"` 狀態）**：
    *   進入一個 `while` 循環，只要還存在待處理的表單計數 (`$formCounts`)。
    *   在循環內部，篩選出所有出現次數大於等於 2 次的表單（即重複表單）。
    *   如果沒有重複表單，則跳出此輪循環。
    *   遍歷每個識別出的重複表單名稱：
        *   調用 `assignFormGroups($ProgramId, $formName, "Multi Form")`。
        *   調用 `assignColorGroups($ProgramId, $formName, "Multi Form")`。
        *   調用 `assignInsertNameGroups($ProgramId, $formName, "Multi Form")`。
        *   將已處理的表單名稱從 `$formCounts` 中移除。

6.  **獲取剩餘的單一表單**：
    *   在第一輪處理後，`$formCounts` 中剩下的鍵應該是那些只出現過一次的表單名稱。
    *   查詢數據庫，獲取這些 `form` 名稱對應的、不含連字符且屬於當前 `$ProgramId` 的子表單記錄。

7.  **第二輪處理：處理單一表單和需要合併的表單**：
    *   進入另一個 `while` 循環，只要 `$formCounts`（此時代表單一出現的表單）中還有數據。
    *   取出 `$formCounts` 中的第一個表單名稱 (`$firstFormId`)。
    *   從步驟 6 中獲取的子表單集合中找到該 `$firstFormId` 對應的表單對象 (`$firstSubform`)，並獲取其 `insert_name`。
    *   在步驟 6 的子表單集合中，篩選出所有與 `$firstSubform` 的 `insert_name` 相同的表單 (`$matchingForms`)。
    *   **判斷處理方式**：
        *   **如果 `$matchingForms` 的數量大于 1**：
            *   這意味著有多個不同的主要表單共享相同的 `insert_name`。
            *   調用 `assignMergeFormGroups($ProgramId, $matchingForms)` 來將這些表單合併到一個組中，並處理它們的顏色組和插入名稱組。
            *   從 `$formCounts` 中移除所有這些 `$matchingForms` 的表單名稱。
        *   **如果 `$matchingForms` 的數量等于 1**（或在過濾後只剩下它自己）：
            *   這是一個獨立的單一表單。
            *   調用 `assignFormGroups($ProgramId, $matchingForms, "Single Form")`。
            *   調用 `assignColorGroups($ProgramId, $matchingForms, "Single Form")`。
            *   調用 `assignInsertNameGroups($ProgramId, $matchingForms, "Single Form")`。
            *   從 `$formCounts` 中移除當前的 `$firstFormId`。
    *   如果 `$formCounts` 為空，則跳出此輪循環。

## 總結

`GroupSubForm` 函數通過兩輪主要的循環處理，系統地對子表單進行分組。第一輪專注於處理具有相同表單名稱的重複表單。第二輪則處理單一出現的表單，並特別關注那些雖然表單名稱不同但 `insert_name` 相同的表單，將它們合併處理。整個過程依賴於一系列更細粒度的 `assign...Group` 和 `assignMerge...Group` 輔助函數來完成實際的數據庫更新和分組邏輯。 