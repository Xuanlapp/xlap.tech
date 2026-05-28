<?php

namespace App\Services\Program;

use App\Models\Programs;
use App\Models\Program_forms;
use App\Models\Program_subforms;
use App\Models\insert_name_color;


class ProgramFunctionServices
{
    //將 prepress_color_front 或 prepress_color_back 分割成陣列
    public function splitPrepressData($data)
    {
        if (!$data) {
            return []; // 如果沒有數據則返回空陣列
        }
        // 以 "+" 分割字串並移除空格
        return array_map(function ($item) {
            return str_replace(' ', '', trim($item));
        }, explode('+', $data));
    }

    private function assignMergeFormGroups($ProgramId, $form)
    {
        $formValues = $form->pluck('form')->sort();
        $remainingSubForms = collect();
        foreach ($formValues as $formValue) {
            // 執行每個 formValue 的查詢
            $subForms = Program_subforms::where('program_id', $ProgramId)
                ->whereRaw('SUBSTRING_INDEX(form, "-", 1) = ?', [$formValue])
                ->orderBy('id')
                ->get();

            $remainingSubForms = $remainingSubForms->merge($subForms);
        }

        $firstId = $remainingSubForms->first()?->id;

        if ($firstId) {
            foreach ($formValues as $formValue) {
                // 為所有子表單指定 form_group
                Program_subforms::where('program_id', $ProgramId)
                    ->whereRaw('SUBSTRING_INDEX(form, "-", 1) = ?', [$formValue])
                    ->update(['form_group' => $firstId]);
            }
        }
        $this->assignMergeColorGroups($ProgramId, $firstId);
    }

    private function assignMergeColorGroups($ProgramId, $Form_group)
    {
        $subForms = Program_subforms::where('program_id', $ProgramId)
            ->where('form_group', $Form_group)
            ->orderBy('id')
            ->get();
        $firstGroupSubForm = $subForms->first();
        $Group_Form = $firstGroupSubForm->id;
        while ($subForms->isNotEmpty()) {
            // 取得集合中的第一筆記錄
            $firstSubForm = $subForms->first();
            $firstId = $firstSubForm->id;
            $firstColor = $firstSubForm->prepress_color_front;
            // 尋找 prepress_color_front 與第一筆記錄顏色相同的記錄
            $matchingSubForms = $subForms->filter(function ($subForm) use ($firstColor) {
                return $subForm->prepress_color_front === $firstColor;
            });

            // 更新所有顏色相符記錄的 color_group
            $matchingSubForms->each(function ($subForm) use ($firstId) {
                $subForm->color_group = $firstId;
                $subForm->save();
            });
            // 從集合中移除已處理的記錄
            $subForms = $subForms->diff($matchingSubForms);
        }

        $this->assignMergeInsertNameGroups($ProgramId, $Group_Form);
    }


    private function assignMergeInsertNameGroups($ProgramId, $Form_group)
    {
        $subForms = Program_subforms::where('program_id', $ProgramId)
            ->where('form_group', $Form_group)
            ->orderBy('id')
            ->get();
        // 檢查是否有數據並執行後續操作
        // 遍歷記錄
        while ($subForms->isNotEmpty()) {
            // 取得集合中的第一筆記錄
            $firstSubForm = $subForms->first();
            $firstId = $firstSubForm->id;
            $firstInsertName = $firstSubForm->insert_name;

            // 尋找 insert_name 與第一筆記錄值相同的記錄
            $matchingSubForms = $subForms->filter(function ($subForm) use ($firstInsertName) {
                return $subForm->insert_name === $firstInsertName;
            });

            // 更新所有 insert_name 相符記錄的 color_group
            $matchingSubForms->each(function ($subForm) use ($firstId) {
                $subForm->insert_name_group = $firstId;
                $subForm->save();
            });

            // 從集合中移除已處理的記錄
            $subForms = $subForms->diff($matchingSubForms);
        }
    }


    /**
     * 分配表單組功能
     * 此方法將相似的表單分組在一起，主要基於插入名稱的前兩個詞
     * 
     * @param int $ProgramId 程式ID
     * @param mixed $form 表單值或表單集合
     * @param string $status 狀態：'Single Form'(單一表單) 或 'Multi Form'(多表單)
     */
    private function assignFormGroups($ProgramId, $form, $status)
    {
        // 根據狀態決定如何獲取表單
        if ($status == 'Single Form') {
            // 如果是單一表單狀態，從集合中獲取並排序表單值
            $formValues = $form->pluck('form')->sort();
            $remainingSubForms = collect();
            foreach ($formValues as $formValue) {
                // 為每個表單值查詢相關子表單
                // SUBSTRING_INDEX提取表單名稱中"-"前面的部分
                $subForms = Program_subforms::where('program_id', $ProgramId)
                    ->whereRaw('SUBSTRING_INDEX(form, "-", 1) = ?', [$formValue])
                    ->orderBy('id')
                    ->get();

                // 將查詢結果合併到待處理集合中
                $remainingSubForms = $remainingSubForms->merge($subForms);
            }
        } else {
            // 如果是多表單狀態，直接查詢特定表單值的子表單
            $remainingSubForms = Program_subforms::where('program_id', $ProgramId)
                ->whereRaw('SUBSTRING_INDEX(form, "-", 1) = ?', [$form])
                ->orderBy('id')
                ->get();
        }

        // 初始化分組完成標記為false，開始分組迭代
        $isFormGroupComplete = false;
        while (!$isFormGroupComplete) {
            // 假設此輪迭代將完成分組
            $isFormGroupComplete = true;

            foreach ($remainingSubForms as $subForm) {
                // 處理insert_name，只取前一個詞作為匹配依據
                $currentInsertName = implode(' ', array_slice(explode(' ', trim($subForm->insert_name)), 0, 1));
                //                $currentInsertName = trim($subForm->insert_name);

                // 查找所有insert_name前一個詞相同的子表單
                $matchingInsertNames = $remainingSubForms->filter(function ($subForm) use ($currentInsertName) {
                    return implode(
                        ' ',
                        array_slice(explode(' ', trim($subForm->insert_name)), 0, 1)
                    ) === $currentInsertName;
                });

                // 按ID排序匹配的子表單
                $sortedMatchingSubForms = $matchingInsertNames->sortBy('id');
                // 獲取排序後的第一個子表單ID作為組ID
                $firstId = $sortedMatchingSubForms->first()?->id;

                if ($firstId) {
                    // 將所有匹配的子表單更新到相同的form_group
                    foreach ($sortedMatchingSubForms as $matchedSubForm) {
                        Program_subforms::where('id', $matchedSubForm->id)
                            ->update(['form_group' => $firstId]);
                    }

                    // 從待處理集合中移除已處理的子表單
                    $remainingSubForms = $remainingSubForms->reject(function ($subForm) use ($sortedMatchingSubForms) {
                        return $sortedMatchingSubForms->contains('id', $subForm->id);
                    });

                    // 設置標記為false表示需要繼續處理
                    $isFormGroupComplete = false; // 繼續迭代直到所有子表單都被處理
                }
            }
        }
    }

    /**
     * 分配顏色組邏輯
     * 此方法根據表單的預印顏色(JSON格式)將其分組
     * 
     * @param int $ProgramId 程式ID
     * @param mixed $form 表單值或表單集合
     * @param string $status 狀態：'Single Form' 或 'Multi Form'
     */
    private function assignColorGroups($ProgramId, $form, $status)
    {
        // 根據狀態決定如何獲取表單
        if ($status == 'Single Form') {
            // 如果是單一表單狀態，從集合中獲取並排序表單值
            $formValues = $form->pluck('form')->sort();
            $allGroups = collect();

            // 遍歷每個表單值
            foreach ($formValues as $formValue) {
                // 查詢指定程式中特定表單值（取出"-"前面的部分）的子表單
                // 只獲取已經有form_group的子表單，並按ID排序
                $subForms = Program_subforms::where('program_id', $ProgramId)
                    ->whereRaw('SUBSTRING_INDEX(form, "-", 1) = ?', [$formValue])
                    ->whereNotNull('form_group')
                    ->orderBy('id')
                    ->get()
                    ->groupBy('form_group');

                // 將查詢結果賦值給allGroups
                $allGroups = ($subForms);
            }
        } else {
            // 如果是多表單狀態，直接查詢程式中的特定表單
            // 獲取具有相同form_group的所有子表單，並按form_group分組
            $allGroups = Program_subforms::where('program_id', $ProgramId)
                ->whereRaw('SUBSTRING_INDEX(form, "-", 1) = ?', $form)
                ->whereNotNull('form_group')
                ->orderBy('id')
                ->get()
                ->groupBy('form_group');
        }

        // 處理每個表單組
        foreach ($allGroups as $groupSubForms) {
            $processedIds = []; // 用於記錄已處理的ID
            $colorGroupMap = []; // 用於記錄每種顏色對應的組ID

            // 遍歷組內的每個子表單
            foreach ($groupSubForms as $subForm) {
                // 如果該子表單已處理，則跳過
                if (in_array($subForm->id, $processedIds)) {
                    continue;
                }

                // 獲取子表單的前表面顏色JSON數據
                $colorJson = $subForm->prepress_color_front_json;

                // 將JSON解碼為數組，排序後再編碼回JSON字符串
                // 這樣可以確保相同顏色組合的不同順序也會被視為相同
                $colorArray = json_decode($colorJson, true);
                if (is_array($colorArray)) {
                    sort($colorArray);
                    $colorKey = json_encode($colorArray);
                } else {
                    // 如果JSON解碼失敗，則使用原始JSON字符串
                    $colorKey = $colorJson;
                }

                // 為當前顏色獲取或創建一個組ID
                // 如果顏色已有對應的組ID，則使用該ID，否則使用當前子表單的ID
                $colorGroupId = $colorGroupMap[$colorKey] ?? $subForm->id;
                $colorGroupMap[$colorKey] = $colorGroupId;

                // 查找組內所有具有相同顏色且尚未處理的子表單
                $matchingForms = $groupSubForms->filter(function ($item) use ($colorKey, $processedIds) {
                    // 獲取當前項目的顏色JSON
                    $itemColorJson = $item->prepress_color_front_json;

                    // 如果JSON為空，則比較字符串格式
                    if (empty($itemColorJson)) {
                        $itemColorKey = $item->prepress_color_front;
                    } else {
                        // 將JSON解碼為數組，排序後再編碼回JSON字符串
                        $itemColorArray = json_decode($itemColorJson, true);
                        if (is_array($itemColorArray)) {
                            sort($itemColorArray);
                            $itemColorKey = json_encode($itemColorArray);
                        } else {
                            $itemColorKey = $itemColorJson;
                        }
                    }

                    return $itemColorKey === $colorKey && !in_array($item->id, $processedIds);
                });

                // 更新所有匹配的表單並標記為已處理
                $matchingForms->each(function ($matchingForm) use (&$processedIds, $colorGroupId) {
                    // 更新子表單的color_group為當前顏色組ID
                    Program_subforms::where('id', $matchingForm->id)
                        ->update(['color_group' => $colorGroupId]);
                    // 將處理過的ID添加到已處理列表
                    $processedIds[] = $matchingForm->id;
                });
            }
        }
    }

    private function assignInsertNameGroups($ProgramId, $form, $status)
    {

        if ($status == 'Merge Form') {
            $formValues = $form->pluck('form')->sort();
            $allInsertNames = collect();
            foreach ($formValues as $formValue) {
                // 執行每個 formValue 的查詢
                $subForms = Program_subforms::where('program_id', $ProgramId)
                    ->whereRaw('SUBSTRING_INDEX(form, "-", 1) = ?', [$formValue])
                    ->whereNotNull('color_group')
                    ->get();

                $allInsertNames = $allInsertNames->merge($subForms);
            }
            $allInsertNames = $allInsertNames->flatten();
        } elseif ($status == 'Single Form') {
            $formValues = $form->pluck('form')->sort();
            $allInsertNames = collect();
            foreach ($formValues as $formValue) {
                // 執行每個 formValue 的查詢
                $subForms = Program_subforms::where('program_id', $ProgramId)
                    ->whereRaw('SUBSTRING_INDEX(form, "-", 1) = ?', [$formValue])
                    ->whereNotNull('color_group')
                    ->get();

                $allInsertNames = $allInsertNames->merge($subForms);
            }
            $allInsertNames = $allInsertNames->flatten();
        } else {
            $allInsertNames = Program_subforms::where('program_id', $ProgramId)
                ->whereRaw('SUBSTRING_INDEX(form, "-", 1) = ?', [$form])
                ->whereNotNull('color_group')
                ->get();
        }

        // 按 `insert_name` 過濾子表單並指定群組
        $remainingInsertNames = $allInsertNames->keyBy('id');
        $isInsertNameGroupComplete = false;

        while (!$isInsertNameGroupComplete) {
            $isInsertNameGroupComplete = true;

            foreach ($remainingInsertNames as $subFormId => $subForm) {
                $currentInsertName = $subForm->insert_name;

                // 過濾具有相同 insert_name 的子表單
                $matchingInsertNames = $remainingInsertNames->filter(function ($subForm) use ($currentInsertName) {
                    return $subForm->insert_name === $currentInsertName;
                });

                $sortedInsertNames = $matchingInsertNames->sortBy('id');
                $firstInsertNameId = $sortedInsertNames->first()?->id;

                if ($firstInsertNameId) {
                    foreach ($sortedInsertNames as $matchedSubForm) {
                        Program_subforms::where('id', $matchedSubForm->id)
                            ->update(['insert_name_group' => $firstInsertNameId]);
                    }

                    $remainingInsertNames = $remainingInsertNames->reject(function ($subForm) use ($sortedInsertNames) {
                        return $sortedInsertNames->contains('id', $subForm->id);
                    });

                    $isInsertNameGroupComplete = false; // 如果未完成則繼續處理
                }
            }
            if ($remainingInsertNames->isEmpty()) {
                break;
            }
        }
    }

    /**
     * 處理子表單的分組邏輯
     * 此函數主要用於處理和組織程式的子表單，包括：
     * 1. 處理重複的表單
     * 2. 合併相關的表單
     * 3. 分配表單組
     * 4. 更新表單狀態
     * 
     * @param int $ProgramId 要處理的程式ID
     */
    public function GroupSubForm($ProgramId)
    {
        // 檢查 $ProgramId 是否為 null
        if ($ProgramId === null) {
            return; // 如果為 null 則直接返回不執行後續操作
        }

        // 步驟 1: 獲取所有屬於此程式的子表單
        // 使用程式 ID 從數據庫中查詢所有相關的子表單
        $subForms = Program_subforms::where('program_id', $ProgramId)->get();

        // 步驟 2: 過濾表單
        // 只保留表單編號中不包含連字符"-"的表單
        // 這樣可以分離出主要表單和子表單
        $mainForms = $subForms->filter(function ($form) {
            return strpos($form->form, '-') === false;
        });

        // 步驟 3: 統計表單數量
        // 按表單編號分組並計算每個編號出現的次數
        // 用於識別重複的表單
        $formCounts = $mainForms->groupBy('form')->map(function ($group) {
            return $group->count();
        });

        // 步驟 4: 處理重複表單的第一輪循環
        // 處理所有出現次數大於等於 2 次的表單
        while ($formCounts->isNotEmpty()) {
            // 找出所有重複的表單（出現次數 >= 2）
            $duplicateForms = $formCounts->filter(function ($count) {
                return $count >= 2;
            });

            // 如果沒有重複的表單，退出循環
            if ($duplicateForms->isEmpty()) {
                break;
            }

            // 處理每個重複的表單
            $duplicateForms->each(function ($count, $form) use ($ProgramId, &$formCounts) {
                // 為重複表單分配表單組
                $this->assignFormGroups($ProgramId, $form, "Multi Form");
                // 為重複表單分配顏色組
                $this->assignColorGroups($ProgramId, $form, "Multi Form");
                // 為重複表單分配插入名稱組
                $this->assignInsertNameGroups($ProgramId, $form, "Multi Form");
                // 從待處理列表中移除已處理的表單
                $formCounts->forget($form);
            });
        }

        // 步驟 5: 獲取剩餘的表單
        // 查詢剩餘未處理的表單，這些是沒有連字符且尚未分組的表單
        $subform = Program_subforms::whereIn('form', $formCounts->keys())
            ->whereRaw("form NOT LIKE '%-%'")  // 排除包含連字符的表單
            ->where('program_id', $ProgramId)   // 限定特定程式
            ->orderBy('id')                     // 按ID排序
            ->get();

        // 步驟 6: 處理剩餘表單的第二輪循環
        while ($formCounts->isNotEmpty()) {
            // 獲取第一個待處理的表單ID
            $firstFormId = $formCounts->keys()->first();
            // 找到對應的表單對象
            $firstSubform = $subform->firstWhere('form', $firstFormId);
            // 獲取該表單的插入名稱
            $firstInsertName = $firstSubform->insert_name;

            // 查找具有相同插入名稱的所有表單
            $matchingForms = $subform->filter(function ($subform) use ($firstInsertName) {
                return $subform->insert_name === $firstInsertName;
            });

            // 根據匹配的表單數量決定處理方式
            if ($matchingForms->count() > 1) {
                // 如果有多個匹配的表單，進行合併處理
                $this->assignMergeFormGroups($ProgramId, $matchingForms);
                // 獲取所有匹配表單的ID
                $matchingFormIds = $matchingForms->pluck('form')->toArray();
                // 從待處理列表中移除這些表單
                $formCounts = $formCounts->forget($matchingFormIds);
            } else {
                // 如果只有一個表單，作為單獨的表單處理
                // 分配表單組
                $this->assignFormGroups($ProgramId, $matchingForms, "Single Form");
                // 分配顏色組
                $this->assignColorGroups($ProgramId, $matchingForms, "Single Form");
                // 分配插入名稱組
                $this->assignInsertNameGroups($ProgramId, $matchingForms, "Single Form");
                // 從待處理列表中移除此表單
                $formCounts = $formCounts->forget($firstFormId);
            }

            // 檢查是否還有表單需要處理
            if ($formCounts->isEmpty()) {
                break;
            }
        }
    }
}
