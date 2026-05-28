<?php

namespace App\Http\Livewire\Modals\Program;

/**
 * ImportProgramExcel 類別
 *
 * 這個類別主要用於處理從Excel檔案導入程式數據的功能。主要功能包括：
 * 1. 解析Excel檔案中的程式數據
 * 2. 處理程式顏色信息
 * 3. 管理表單和子表單的關係
 * 4. 更新預印顏色數據
 * 5. 處理插入名稱和短名稱
 *
 * 主要方法：
 * - import(): 處理Excel檔案導入的主要邏輯
 * - GroupSubForm(): 處理子表單的分組
 * - processPrepressData(): 處理預印數據
 * - SaveNewData(): 保存新的程式數據
 * - parseAndSaveCodeProgram(): 解析和保存程式代碼
 */

use App\Models\Program_forms;
use App\Models\Program_subforms;
use App\Services\Program\ProgramFunctionServices;
use App\Services\Program\ProgramListServices;
use App\Services\Program\ChangeJSON;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use LivewireUI\Modal\ModalComponent;
use App\Models\Programs;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Insert_name_color;
use Livewire\WithFileUploads;
use App\Http\Livewire\Traits\Notification;
use Illuminate\Support\Facades\Log;

class ImportProgramExcel extends ModalComponent
{
    use WithFileUploads, LivewireAlert, Notification;

    public $file;  // 用於存儲上傳的Excel檔案
    public $isImportComplete = false;  // 標記導入是否完成
    private $programFunctionServices, $programListServices, $ChangeJSON;  // 服務類實例

    public function mount() {}

    /**
     * 處理Excel檔案導入的主要邏輯
     * 1. 初始化必要的服務
     * 2. 讀取Excel數據
     * 3. 處理表頭和數據
     * 4. 解析並保存程式代碼
     */
    public function import()
    {
        if (!$this->programFunctionServices) {
            $this->programFunctionServices = app(ProgramFunctionServices::class);
        }
        if (!$this->programListServices) {
            $this->programListServices = app(ProgramListServices::class);
        }
        if (!$this->ChangeJSON) {
            $this->ChangeJSON = app(ChangeJSON::class);
        }
        if (!$this->file) {
            $this->showAlertMessage('error', 'No file uploaded.');
            return;
        }

        $data = Excel::toArray([], $this->file->getRealPath())[0];

        $firstRow = $data[0] ?? null;
        $headers = array_map('trim', $data[2]);
        $program_color = 0;
        $formattedData = [];

        foreach ($data as $index => $row) {
            if (empty($row[1]) || $row[1] === "Form #" || empty($row[3])) {
                continue;
            }
            $program_color = empty($row[9]) ? 1 : 2;

            $formattedRow = [];
            foreach ($row as $key => $value) {
                $formattedRow[$headers[$key] ?? ""] = $value;
            }
            $formattedData[] = $formattedRow;
        }

        if ($firstRow) {
            $firstRowString = implode(" ", $firstRow);
            $codeProgram = $this->parseAndSaveCodeProgram($firstRowString, $program_color, $formattedData);
            $this->dispatchBrowserEvent('refresh-page');
        } else {
            $this->showAlertMessage('error', 'First row is empty.');
        }
    }

    /**
     * 處理預印數據
     * 1. 獲取所有表單和子表單
     * 2. 處理前後面的預印顏色
     * 3. 將數據轉換為JSON格式
     * 4. 更新數據庫
     */
    public function processPrepressData($programId)
    {
        $forms = Program_forms::where('program_id', $programId)->get();
        $subForms = Program_subforms::where('program_id', $programId)->get();
        foreach ($forms as $form) {
            $cutPrepressColorFont = $this->programFunctionServices->splitPrepressData($form->prepress_color_front);
            $cutPrepressColorBack = $this->programFunctionServices->splitPrepressData($form->prepress_color_back);

            Program_forms::where('id', $form->id)->update([
                'prepress_color_front_json' => json_encode($cutPrepressColorFont),
                'prepress_color_back_json' => json_encode($cutPrepressColorBack),
            ]);
        }
        foreach ($subForms as $subForm) {
            $cutPrepressColorFont = $this->programFunctionServices->splitPrepressData($subForm->prepress_color_front);
            $cutPrepressColorBack = $this->programFunctionServices->splitPrepressData($subForm->prepress_color_back);

            Program_subforms::where('id', $subForm->id)->update([
                'prepress_color_front_json' => json_encode($cutPrepressColorFont),
                'prepress_color_back_json' => json_encode($cutPrepressColorBack),
            ]);
        }
        $this->showAlertMessage('success', 'Prepress data processed successfully.');
    }

    /**
     * 更新預印顏色
     * 1. 遍歷格式化後的數據
     * 2. 更新插入名稱顏色表
     * 3. 更新程式表單
     * 4. 更新程式子表單
     */
    public function updatePrepressColors($formattedData, $existingCodeProgram)
    {
        foreach ($formattedData as $row) {
            $insertName = $row['Insert Name'] ?? null;
            $colorFront = $row['Prepress Colors Front'] ?? null;
            $colorBack = $row['Prepress Colors Back'] ?? null;

            $pressColor = Insert_name_color::where('insert_name', $insertName)->first();
            if ($pressColor) {
                $pressColor->update([
                    'prepress_color_front' => $colorFront,
                    'prepress_color_back' => $colorBack,
                ]);
            }

            Program_forms::where('program_id', $existingCodeProgram->id)
                ->where('insert_name', $insertName)
                ->update([
                    'prepress_color_front' => $colorFront,
                    'prepress_color_back' => $colorBack,
                ]);

            Program_subforms::where('program_id', $existingCodeProgram->id)
                ->where('insert_name', $insertName)
                ->update([
                    'prepress_color_front' => $colorFront,
                    'prepress_color_back' => $colorBack,
                ]);
        }

        $this->showAlertMessage('success', 'Prepress colors updated successfully.');
        $this->dispatchBrowserEvent('refresh-page');
    }

    /**
     * 處理插入名稱顏色
     * 1. 處理插入名稱
     * 2. 檢查顏色是否存在
     * 3. 根據顏色狀態更新或創建記錄
     */
    public function InsertNameColor($row, $codeProgram, $insertShortName, $program_color)
    {
        $insertName = $this->removeParentheses($row['Insert Name'] ?? null);
        $updateColor = [];
        $pressColorMemoryAttributes = [
            'config' => $row['Config'] ?? null,
            'insert_name' => $insertName,
            'prepress_color_front' => $this->ChangeJSON->changeToJson($row['Prepress Colors Front']) ?? null,
            'prepress_color_back' => $this->ChangeJSON->changeToJson($row['Prepress Colors Back']) ?? null,
            'program_name' => $codeProgram->collection,
            'sport' => $codeProgram->sp,
            'year' => $codeProgram->year,
        ];
        $exists = Insert_name_color::where('insert_name', $row['Insert Name'])
            ->where('prepress_color_front', $row['Prepress Colors Front'])
            ->first();
        if ($program_color == 2) {
            if ($exists) {
                if (!empty($exists['insert_short_name'])) {
                    $updateColor['insert_short_name'] = $exists['insert_short_name'];
                }
                $updateColor['prepress_color_front'] = $exists->prepress_color_front;
                $updateColor['prepress_color_back'] = $exists->prepress_color_back;
            } else {
                $pressColorMemoryAttributes['year'] = $codeProgram->year;
                $pressColorMemoryAttributes['insert_short_name'] = $insertShortName;
                Insert_name_color::create($pressColorMemoryAttributes);
                $updateColor = $pressColorMemoryAttributes;
            }

            return $updateColor;
        } elseif ($program_color == 1) {
            if ($exists) {
                if (empty($row['Prepress Colors Front'])) {
                    $updateColor['prepress_color_front'] = $exists->prepress_color_front;
                }
                if (empty($row['Prepress Colors Back'])) {
                    $updateColor['prepress_color_back'] = $exists->prepress_color_back;
                }
                if (!empty($exists['insert_short_name'])) {
                    $updateColor['insert_short_name'] = $exists['insert_short_name'];
                }
            } else {
                $pressColorMemoryAttributes['insert_short_name'] = $insertShortName;
                Insert_name_color::create($pressColorMemoryAttributes);
            }
            return $updateColor;
        }
    }

    /**
     * 保存新的程式數據
     * 1. 處理每個數據行
     * 2. 生成插入短名稱
     * 3. 處理顏色信息
     * 4. 保存表單和子表單數據
     */
    public function SaveNewData($formattedData, $codeProgram, $program_color)
    {
        $codeProgramId = $codeProgram->id;
        $createdSubform = false;
        foreach ($formattedData as $index => $row) {
            $insertShortName = $this->programListServices->shortenInsertName($row['Insert Name']);
            $updatecolor = $this->InsertNameColor($row, $codeProgram, $insertShortName, $program_color);

            $formValue = $row['Form #'] ?? null;
            $isInteger = ctype_digit(strval($formValue));
            $attributes = [
                'config' => $row['Config'] ?? null,
                'form' => $formValue,
                'insert_name' => $this->removeParentheses($row['Insert Name'] ?? null),
                'cards' => $row['Cards'] ?? null,
                'seq' => $row['Seq'] ?? null,
                'substrate' => $row['SUBSTRATE'] ?? null,
                'coating_front' => $row['Coating Front'] ?? null,
                'coating_back' => $row['Coating Back'] ?? null,
                'panini' => $row['Panini'] ?? null,
                'leagues' => $row['Leagues - USA BB'] ?? $row['Leagues'] ?? null,
                'foil' => $row['Foil'] ?? null,
                'autos' => $row['Autos'] ?? null,
                'pms' => $row['PMS'] ?? null,
                'lam_front' => $row['Lam Front'] ?? null,
                'lam_back' => $row['Lam Back'] ?? null,
                'stamped' => $row['Stamped'] ?? null,
                'panini_binder' => $row['Panini Binder'] ?? null,
                'total_inc_sht' => $row['Total Inc Sht'] ?? null,
                'program_id' => $codeProgramId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            if ($program_color == 1) {
                $attributes['prepress_color_front'] = $updatecolor['prepress_color_front'] ?? null;
                $attributes['prepress_color_back'] = $updatecolor['prepress_color_back'] ?? null;
            } elseif ($program_color == 2) {
                $attributes['prepress_color_back'] = $row['Prepress Colors Back'] ?? null;
                $attributes['prepress_color_front'] = $row['Prepress Colors Front'] ?? null;
            }
            if (isset($updatecolor['insert_short_name'])) {
                $attributes['insert_short_name'] = $updatecolor['insert_short_name'];
            } else {
                $attributes['insert_short_name'] = $insertShortName;
            }
            if ($attributes['form'] == null) {
                continue;
            }
            if ($isInteger) {
                Program_subforms::create($attributes);
                Program_forms::create($attributes);
            } else {
                $mainFormNumber = (int)explode('-', $formValue)[0];
                $mainPrograms = Program_forms::where('form', $mainFormNumber)->get();
                $bestMatch = null;
                foreach ($mainPrograms as $mainProgram) {
                    if (isset($row['Insert Name']) && isset($mainProgram->insert_name)) {
                        $subProgramWords = explode(' ', strtolower($row['Insert Name']));
                        $mainProgramWords = explode(' ', strtolower($mainProgram->insert_name));
                        $wordMatchFound = false;
                        foreach ($subProgramWords as $word) {
                            if (in_array($word, $mainProgramWords)) {
                                $wordMatchFound = true;
                                break;
                            }
                        }
                        if ($wordMatchFound) {
                            $bestMatch = $mainProgram;
                            break;
                        }
                    }
                }
                if ($bestMatch) {
                    $attributes['main_form_id'] = $bestMatch->id;
                } else {
                    $nullMainPrograms[] = $attributes;
                    $attributes['main_form_id'] = null;
                }
                $createdSubform = Program_subforms::create($attributes) ? true : false;
            }
        }
        if ($createdSubform) {
            $this->showAlertMessage('success', 'The data has been saved successfully.');

            $this->processPrepressData($codeProgram->id);
            $this->programFunctionServices->GroupSubForm($codeProgram->id);
            $this->dispatchBrowserEvent('refresh-page');
        } else {
            $this->showAlertMessage('error', 'The data could not be saved.');
            $this->dispatchBrowserEvent('refresh-page');
        }
    }

    /**
     * 解析和保存程式代碼
     * 1. 解析第一行數據
     * 2. 檢查程式是否已存在
     * 3. 處理顏色狀態
     * 4. 創建或更新程式記錄
     */
    public function parseAndSaveCodeProgram($firstRowString, $program_color, $formattedData)
    {
        $program_name = '';
        $result = preg_replace('/^FORM BREAK FOR\s*/', '', $firstRowString);
        if (preg_match('/(\d{4}(?:-\d{2})?)?\s+([\w\s]+)\s+([A-Z]{2,3})\s+(\d+)/', $result, $matches)) {
            list(, $year, $collection, $sp, $code) = $matches;
            $program_name = $collection;
            $existingCodeProgram = Programs::where('code', $code)
                ->first();
            if ($existingCodeProgram) {
                if ($existingCodeProgram->color == 1) {
                    if ($program_color == 1) {
                        $this->showAlertMessage(
                            'error',
                            'This Form Break has been uploaded once, now need the Form Break with color!!!'
                        );
                        return null;
                    } else {
                        $existingPressColorMemory = Insert_name_color::where(
                            'program_name',
                            $existingCodeProgram->collection
                        )->first();
                        if ($existingPressColorMemory) {
                            $existingColorProgram = Programs::where('code', $code)->first();
                            $program_color = 2;
                            $existingColorProgram->update([
                                'color' => $program_color,
                            ]);
                        } else {
                            $codeProgram = Programs::create([
                                'year' => $year,
                                'collection' => trim($collection),
                                'sp' => $sp,
                                'code' => $code,
                                'color' => $program_color,
                            ]);
                            if ($codeProgram) {
                                $this->showAlertMessage('success', 'The code program save new in the database.');
                                $this->dispatchBrowserEvent('refresh-page');
                            }
                        }
                        $this->updatePrepressColors($formattedData, $existingCodeProgram);
                    }
                } elseif ($existingCodeProgram->color == 2) {
                    $this->showAlertMessage('error', 'The code program already exists in the database.');
                    return null;
                }
            } else {
                $checkColor = Insert_name_color::where('program_name', $collection)->first();
                if ($checkColor) {
                    $program_color = 2;
                    $codeProgram = Programs::create([
                        'year' => $year,
                        'collection' => trim($collection),
                        'sp' => $sp,
                        'code' => $code,
                        'color' => $program_color,
                    ]);
                } else {
                    $codeProgram = Programs::create([
                        'year' => $year,
                        'collection' => trim($collection),
                        'sp' => $sp,
                        'code' => $code,
                        'color' => $program_color,
                    ]);
                    $this->showAlertMessage('error', 'Need prepress front/back color on the excel file');
                }
                $this->SaveNewData($formattedData, $codeProgram, $program_color);
            }
        } else {
            $this->showAlertMessage('error', 'Could not parse the first row.');
            return null;
        }
    }

    /**
     * 移除括號內容
     * 用於清理插入名稱中的括號內容
     */
    private function removeParentheses($input)
    {
        return preg_replace('/\s*\(.*?\)\s*/', ' ', $input);
    }

    public function render()
    {
        return view('livewire.modals.program.import-programs');
    }

    public static function modalMaxWidth(): string
    {
        return '7xl';
    }
}
