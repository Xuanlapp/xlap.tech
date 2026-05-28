<?php

namespace App\Http\Livewire\Pages\Program;

use App\Http\Livewire\Traits\Notification;
use App\Models\insert_name_color;
use App\Models\Program_forms;

use App\Models\Program_subforms;
use App\Models\color_name;
use App\Models\Programs;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use App\Services\Program\ProgramFunctionServices;
use App\Services\Program\ProgramListServices;
use App\Services\Program\ChangeJSON;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use App\Models\PaniniNbaPlayerStats;
use App\Models\Nba_team;
use App\Services\NbaFetchDataService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProgramFunction extends Component
{
    use WithFileUploads, WithPagination, Notification, LivewireAlert;

    private $programFunctionServices, $NbaFetchDataServices, $ChangeJSON, $ProgramListServices;

    public function mount(ProgramFunctionServices $programFunctionServices, NbaFetchDataService $NbaFetchDataServices)
    {

        $this->programFunctionServices = $programFunctionServices;
        $this->NbaFetchDataServices = $NbaFetchDataServices;
    }

    public function redirectToSureColor()
    {
        return redirect()->route('program.surecolor');
    }

    public function processPrepressData()
    {
        if (!$this->programFunctionServices) {
            $this->programFunctionServices = app(ProgramFunctionServices::class);
        }

        $forms = Program_forms::get();
        $subForms = Program_subforms::get();
        foreach ($forms as $form) {
            // Tách dữ liệu từ prepress_color_front
            $cutPrepressColorFont = $this->programFunctionServices->splitPrepressData($form->prepress_color_front);

            // Tách dữ liệu từ prepress_color_back
            $cutPrepressColorBack = $this->programFunctionServices->splitPrepressData($form->prepress_color_back);

            // Cập nhật vào bảng forms
            Program_forms::where('id', $form->id)->update([
                'prepress_color_front_json' => json_encode($cutPrepressColorFont),
                'prepress_color_back_json' => json_encode($cutPrepressColorBack),
            ]);
        }
        foreach ($subForms as $subForm) {
            // Tách dữ liệu từ prepress_color_front
            $cutPrepressColorFont = $this->programFunctionServices->splitPrepressData($subForm->prepress_color_front);

            // Tách dữ liệu từ prepress_color_back
            $cutPrepressColorBack = $this->programFunctionServices->splitPrepressData($subForm->prepress_color_back);

            // Cập nhật vào bảng subforms
            Program_subforms::where('id', $subForm->id)->update([
                'prepress_color_front_json' => json_encode($cutPrepressColorFont),
                'prepress_color_back_json' => json_encode($cutPrepressColorBack),
            ]);
        }
        $this->showAlertMessage('success', 'Prepress data processed successfully.');
    }


    public function GroupSubForm()
    {
        if (!$this->programFunctionServices) {
            $this->programFunctionServices = app(ProgramFunctionServices::class);
        }
        // Lấy danh sách các ProgramId khác nhau trong bảng Program_subforms
        $programIds = Program_subforms::distinct('program_id')->pluck('program_id');

        foreach ($programIds as $ProgramId) {
            $subForms = Program_subforms::where('program_id', $ProgramId)->get();

            // Lấy những form của subform không có "-"
            $filteredForms = $subForms->filter(function ($form) {
                return strpos($form->form, '-') === false;
            });

            $formCounts = $filteredForms->groupBy('form')->map(function ($group) {
                return $group->count();
            });

            while ($formCounts->isNotEmpty()) {
                // Lấy các form có lặp lại >= 2 lần
                $duplicateForms = $formCounts->filter(function ($count) {
                    return $count >= 2;
                });

                if ($duplicateForms->isEmpty()) {
                    break;
                }

                // Xử lý từng form lặp lại
                $duplicateForms->each(function ($count, $form) use ($ProgramId, &$formCounts) {
                    $this->programFunctionServices->assignFormGroups($ProgramId, $form, "Multi Form");
                    $this->programFunctionServices->assignColorGroups($ProgramId, $form, "Multi Form");
                    $this->programFunctionServices->assignInsertNameGroups($ProgramId, $form, "Multi Form");
                    $formCounts->forget($form);
                });
            }

            $subform = Program_subforms::whereIn('form', $formCounts->keys())
                ->whereRaw("form NOT LIKE '%-%'")
                ->where('program_id', $ProgramId)
                ->orderBy('id')
                ->get();

            while ($formCounts->isNotEmpty()) {
                $firstFormId = $formCounts->keys()->first();
                $firstSubform = $subform->firstWhere('form', $firstFormId);
                $firstInsertName = $firstSubform->insert_name;

                // Lọc các form có insert_name trùng với insert_name của form đầu tiên
                $matchingForms = $subform->filter(function ($subform) use ($firstInsertName) {
                    return $subform->insert_name === $firstInsertName;
                });

                if ($matchingForms->count() > 1) {
                    $this->programFunctionServices->assignMergeFormGroups($ProgramId, $matchingForms);
                    $matchingFormIds = $matchingForms->pluck('form')->toArray();
                    $formCounts = $formCounts->forget($matchingFormIds);
                } else {
                    $this->programFunctionServices->assignFormGroups($ProgramId, $matchingForms, "Single Form");
                    $this->programFunctionServices->assignColorGroups($ProgramId, $matchingForms, "Single Form");
                    $this->programFunctionServices->assignInsertNameGroups($ProgramId, $matchingForms, "Single Form");
                    $formCounts = $formCounts->forget($firstFormId);
                }

                if ($formCounts->isEmpty()) {
                    break;
                }
            }
        }

        $this->showAlertMessage('success', 'All forms and subforms updated successfully!');
        $this->dispatchBrowserEvent('refresh-page');
    }

    public function ExportAllColor()
    {
        // Lấy dữ liệu từ bảng program_subforms
        $subForms = Program_subforms::all(['prepress_color_front_json', 'prepress_color_back_json']);

        // Tạo mảng lưu dữ liệu đã xử lý
        $data = [];

        // Duyệt qua từng record để chuẩn bị dữ liệu
        foreach ($subForms as $subForm) {
            $frontColors = json_decode($subForm->prepress_color_front_json, true) ?? [];
            // $backColors = json_decode($subForm->prepress_color_back_json, true) ?? [];

            // Kết hợp Front và Back trong một hàng
            // $row = array_merge($frontColors, $backColors);
            $row = $frontColors;
            $data[] = $row;
        }

        // Tạo file Excel
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Ghi dữ liệu vào sheet Excel
        $rowIndex = 1;
        foreach ($data as $row) {
            $sheet->fromArray($row, null, "A{$rowIndex}");
            $rowIndex++;
        }

        // Lưu file Excel vào thư mục tạm
        $filePath = storage_path('app/public/prepress_colors.xlsx');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($filePath);

        // Trả file Excel để tải về
        return response()->download($filePath)->deleteFileAfterSend(true);
    }


    public function processColors()
    {
        if (!$this->programFunctionServices) {
            $this->programFunctionServices = app(ProgramFunctionServices::class);
        }

        // Lọc ra các dòng có program_id = 84
        $subForms = Program_subforms::get();

        // Duyệt qua từng dòng trong bảng subforms
        foreach ($subForms as $subForm) {
            // Tách dữ liệu JSON từ các cột prepress_color_front_json và prepress_color_back_json
            $frontColors = json_decode($subForm->prepress_color_front_json, true);
            $backColors = json_decode($subForm->prepress_color_back_json, true);

            // Kiểm tra và lưu màu front
            if (is_array($frontColors)) {
                foreach ($frontColors as $color) {
                    // Kiểm tra nếu màu đã có trong bảng color_name
                    if (!color_name::where('color_name', $color)->exists()) {
                        // Kiểm tra nếu màu có mặt cả ở front và back
                        if (in_array($color, $backColors)) {
                            // Nếu có mặt cả ở front và back, lưu với color = 3
                            color_name::insert([
                                'color_name' => $color,
                                'color' => 3, // cả front và back
                                'sure' => 0,
                                'to_db' => 0,
                            ]);
                        } else {
                            // Nếu chỉ có mặt ở front, lưu với color = 1
                            color_name::insert([
                                'color_name' => $color,
                                'color' => 1, // front
                                'sure' => 0,
                                'to_db' => 0,
                            ]);
                        }
                    }
                }
            }

            // Kiểm tra và lưu màu back
            if (is_array($backColors)) {
                foreach ($backColors as $color) {
                    // Nếu màu đã có trong bảng color_name, bỏ qua
                    if (!color_name::where('color_name', $color)->exists()) {
                        // Nếu màu chỉ có mặt ở back, lưu với color = 2
                        color_name::insert([
                            'color_name' => $color,
                            'color' => 2, // back
                            'sure' => 0,
                            'to_db' => 0,
                        ]);
                    }
                }
            }
        }

        $this->showAlertMessage('success', 'Colors processed successfully.');
    }

    //update name team full in NBA
    public function Nameteamfull()
    {
        $players = PaniniNbaPlayerStats::all();
        if ($this->NbaFetchDataServices) {
            foreach ($players as $player) {
                // Check if the player's team contains a "/"
                $this->NbaFetchDataServices->UploadTeam($player->id);
            }
        } else {
            $this->NbaFetchDataServices = app(NbaFetchDataService::class);
            foreach ($players as $player) {
                // Check if the player's team contains a "/"
                $this->NbaFetchDataServices->UploadTeam($player->id);
            }
        }


        $this->showAlertMessage('success', 'Team full name updated successfully.');
    }

    public function parenthesesISN()
    {
        if (!$this->ChangeJSON) {
            $this->ChangeJSON = app(ChangeJSON::class);
        }
        $Subforms = Program_subforms::all();
        foreach ($Subforms as $Subform) {
            $insert_name = $Subform->insert_name;
            $insert_name = $this->ChangeJSON->removeParentheses($insert_name);
            Program_subforms::where('id', $Subform->id)->update([
                'insert_name' => $insert_name,
            ]);
        }
        $this->showAlertMessage('success', 'Parentheses removed successfully.');
    }


    public function GiveVersionColor()
    {
        // 設置執行時間限制為 300 秒
        ini_set('max_execution_time', 1000);

        if (!$this->ProgramListServices) {
            $this->ProgramListServices = app(ProgramListServices::class);
        }

        // 獲取所有運動類型的程序，而不僅僅是 BB
        $programs = Programs::orderBy('sp', 'asc')->orderBy('year', 'asc')->get();
        $successCount = 0;
        $failureCount = 0;
        $sportCount = [];

        // 使用事務處理確保數據一致性
        DB::beginTransaction();

        try {
            foreach ($programs as $program) {
                // 初始化每種運動類型的計數器
                if (!isset($sportCount[$program->sp])) {
                    $sportCount[$program->sp] = [
                        'new' => 0,
                        'updated' => 0
                    ];
                }

                $subForms = Program_subforms::where('program_id', $program->id)->orderBy('id', 'asc')->get();

                foreach ($subForms as $subForm) {
                    $trimmedName = rtrim($subForm->insert_name); // 去除尾部空格

                    // 如果需要更新 insert_name
                    if ($trimmedName !== $subForm->insert_name) {
                        $subForm->update(['insert_name' => $trimmedName]);
                    }

                    // 查找匹配記錄
                    $checks = insert_name_color::where('insert_name', $trimmedName)
                        ->where('program_name', $program->collection)
                        ->where('sport', $program->sp)
                        ->get();

                    if ($checks->isNotEmpty()) {
                        // 處理現有記錄
                        $result = $this->processExistingRecords($checks, $subForm, $trimmedName, $program, $successCount, $failureCount);
                        if ($result === 'updated') {
                            $sportCount[$program->sp]['updated']++;
                        } elseif ($result === 'new') {
                            $sportCount[$program->sp]['new']++;
                        }
                    } else {
                        // 創建新記錄
                        $this->createNewRecord($subForm, $trimmedName, $program);
                        $successCount++;
                        $sportCount[$program->sp]['new']++;
                    }
                }
            }

            DB::commit();

            // 準備詳細的運動類型統計信息
            $sportStats = [];
            foreach ($sportCount as $sport => $counts) {
                $sportStats[] = "$sport: {$counts['new']} new, {$counts['updated']} updated";
            }
            $sportStatsStr = implode('; ', $sportStats);

            if ($failureCount > 0) {
                $this->showAlertMessage('info', "Version color updated successfully. Total: $successCount new records created and $failureCount records updated. Details: $sportStatsStr");
            } else {
                $this->showAlertMessage('success', "Version color updated successfully. Total: $successCount new records created. Details: $sportStatsStr");
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $this->showAlertMessage('error', "An error occurred: " . $e->getMessage());
            Log::error("GiveVersionColor error: " . $e->getMessage());
        }
    }

    /**
     * 處理現有記錄
     * @return string 返回操作結果：'updated', 'new', 或 null
     */
    protected function processExistingRecords($checks, $subForm, $trimmedName, $program, &$successCount, &$failureCount)
    {
        $needNewRecord = true;
        $result = null;

        foreach ($checks as $check) {
            // 解析顏色數據
            $prepressColorsFront = json_decode($check->prepress_color_front, true);
            $prepressSubFormColorsFront = json_decode($subForm->prepress_color_front_json, true);

            // 確保數據是數組
            $prepressColorsFront = is_array($prepressColorsFront) ? $prepressColorsFront : [];
            $prepressSubFormColorsFront = is_array($prepressSubFormColorsFront) ? $prepressSubFormColorsFront : [];

            // 清理和排序顏色數據
            $cleanedFront = array_map('trim', $prepressColorsFront);
            $formattedCleanedFront = array_map('trim', $prepressSubFormColorsFront);
            sort($cleanedFront);
            sort($formattedCleanedFront);

            // 比較顏色是否有差異
            $result = array_diff($formattedCleanedFront, $cleanedFront);

            if (empty($result)) {
                // 顏色相同，不需要新記錄
                $needNewRecord = false;
                break;
            } else {
                // 顏色不同，檢查年份
                if ($check->year == $program->year) {
                    // 年份相同，更新記錄
                    $check->update([
                        'prepress_color_front' => $subForm->prepress_color_front_json,
                        'prepress_color_back' => $subForm->prepress_color_back_json,
                        'config' => $subForm->config,
                        'insert_short_name' => $this->ProgramListServices->shortenInsertName($trimmedName),
                    ]);
                    $failureCount++;
                    $needNewRecord = false;
                    $result = 'updated';
                    break;
                }
                // 如果年份不同，繼續檢查其他記錄
            }
        }

        if ($needNewRecord) {
            // 檢查是否已經存在相同年份的記錄
            $existingYearRecord = insert_name_color::where('insert_name', $trimmedName)
                ->where('program_name', $program->collection)
                ->where('sport', $program->sp)
                ->where('year', $program->year)
                ->first();

            if (!$existingYearRecord) {
                // 創建新記錄
                $this->createNewRecord($subForm, $trimmedName, $program);
                $successCount++;
                $result = 'new';
            }
        }

        return $result;
    }

    /**
     * 創建新記錄
     */
    protected function createNewRecord($subForm, $trimmedName, $program)
    {
        insert_name_color::create([
            'insert_name' => $trimmedName,
            'sport' => $program->sp,
            'year' => $program->year,
            'program_name' => $program->collection,
            'prepress_color_front' => $subForm->prepress_color_front_json,
            'prepress_color_back' => $subForm->prepress_color_back_json,
            'config' => $subForm->config,
            'insert_short_name' => $this->ProgramListServices->shortenInsertName($trimmedName),
        ]);
    }

    public function distances()
    {
        $beginning = [];
        $end = [];
        $updateend = [];
        $SubForms = Program_subforms::all();
        // kiểm tra khoảng trắng ở đầu
        foreach ($SubForms as $SubForm) {
            if (preg_match('/^\s/', $SubForm->insert_name)) { // Kiểm tra nếu có khoảng trắng ở đầu
                $beginning[] = $SubForm->id;
            }
        }
        // kiểm tra khoảng trắng ở cuối
        foreach ($SubForms as $SubForm) {
            if (preg_match('/[^\s]\s$/', $SubForm->insert_name)) { // Kiểm tra đúng 1 dấu cách cuối cùng
                $end[] = $SubForm->id; // Lưu ID vào mảng
            }
        }
        //update xóa khoảng trắng ở cuối
        foreach ($SubForms as $SubForm) {
            $trimmedName = rtrim($SubForm->insert_name); // Xóa toàn bộ khoảng trắng ở cuối
            if ($trimmedName !== $SubForm->insert_name) {
                // Chỉ cập nhật nếu có thay đổi
                $updateend[] = $SubForm->id;
                $SubForm->update(['insert_name' => $trimmedName]);
            }
        }
        dd($updateend);
    }


    public $cmyk = '';  // Biến lưu giá trị CMYK nhập từ người dùng
    public $hexColor = '';  // Biến lưu giá trị Hex

    function rgbToHex($r, $g, $b)
    {
        return sprintf("#%02X%02X%02X", $r, $g, $b);
    }


    public function render()
    {
        //        if ($this->cmyk != '') {
        //            $this->convertToHex($this->cmyk);
        //        }
        return view('livewire.pages.program.program-function');
    }
}
