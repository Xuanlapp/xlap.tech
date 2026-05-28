<?php

namespace App\Http\Livewire\Modals\Program;

use App\Http\Livewire\Traits\Notification;
use App\Models\insert_name_color;
use App\Models\Program_subforms;
use App\Services\Program\ProgramSubFormDetailServices;
use App\Services\Program\ProgramListServices;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\WithPagination;
use LivewireUI\Modal\ModalComponent;
use Illuminate\Support\Facades\Log;
use App\Http\Livewire\Traits\MenuNavArray;
use App\Http\Livewire\Traits\PressColorArray;

class ChangeColor extends ModalComponent
{
    use LivewireAlert, Notification, WithPagination, MenuNavArray, PressColorArray;

    public $SubForm_Id, $SubForms, $splitdatasFront = [], $headersFront = [], $splitdatasBack = [], $headersBack = [], $form, $sortOption, $firstSubFormId, $lap = false;
    protected $programSubFormDetailServices, $ProgramListServices;

    protected $listeners = [
        'previous',
        'next',
        'updateList' => '$refresh',
        'confirm' => 'removePlayer'
    ];


    public function mount(ProgramSubFormDetailServices $programSubFormDetailServices)
    {

        $this->program = Program_subforms::where('id', $this->SubForm_Id)
            ->first()
            ->program;
        $this->programSubFormDetailServices = $programSubFormDetailServices;
        $this->SubForms = Program_subforms::where('id', $this->SubForm_Id)->first();
        $this->form = $this->SubForms->form;
        $this->checkBoxcolor();
        $this->checkInsertcolor();
    }

    public $checkInsertName, $program;

    public $isChangedFront = false;
    public $isChangedBack = false;


    public function checkInsertcolor()
    {
        $this->checkInsertName = insert_name_color::where('insert_name', $this->SubForms->insert_name)
            ->where('sport', $this->program->sp)
            ->where('program_name', $this->program->collection)
            ->orderBy('year', 'asc')
            ->get();
    }

    public function changeVersion($idISN)
    {
        $this->isChangedFront = true;
        $this->isChangedBack = true;
        $this->headersFront = [];
        $this->headersBack = [];
        if (!$this->programSubFormDetailServices) {
            $this->programSubFormDetailServices = app(ProgramSubFormDetailServices::class);
        }
        $insertNameColor = insert_name_color::where('id', $idISN)->first();
        $data = $insertNameColor->prepress_color_front;
        $dataBack = $insertNameColor->prepress_color_back;
        $this->splitdatasFront = $this->programSubFormDetailServices->splitData($data);
        $parsedData = $this->programSubFormDetailServices->splitPrepressData($this->splitdatasFront);
        $this->headersFront = $parsedData;
        // Xử lý Back Color
        $this->splitdatasBack = $this->programSubFormDetailServices->splitData($dataBack);
        $parsedDataBack = $this->programSubFormDetailServices->splitPrepressDataBack($this->splitdatasBack);
        $this->headersBack = $parsedDataBack;
    }


    public function checkBoxcolor()
    {
        if (!$this->programSubFormDetailServices) {
            $this->programSubFormDetailServices = app(ProgramSubFormDetailServices::class);
        }
        // Reset headers trước khi cập nhật
        $this->headersFront = [];
        $this->headersBack = [];

        // Xử lý Front Color
        $data = Program_subforms::where('id', $this->SubForm_Id)
            ->pluck('prepress_color_front_json')
            ->first();
        $this->splitdatasFront = $this->programSubFormDetailServices->splitData($data);

        $parsedData = $this->programSubFormDetailServices->splitPrepressData($this->splitdatasFront);
        $this->headersFront = $parsedData;

        // Xử lý Back Color
        $dataBack = Program_subforms::where('id', $this->SubForm_Id)
            ->pluck('prepress_color_back_json')
            ->first();

        $this->splitdatasBack = $this->programSubFormDetailServices->splitData($dataBack);
        $parsedDataBack = $this->programSubFormDetailServices->splitPrepressDataBack($this->splitdatasBack);
        $this->headersBack = $parsedDataBack;
    }

    public $programssss;

    public function SaveColor()
    {
        if (!$this->ProgramListServices) {
            $this->ProgramListServices = app(ProgramListServices::class);
        }
        $trimmedName = trim($this->SubForms->insert_name);
        if ($trimmedName !== $this->SubForms->insert_name) {
            $this->SubForms->update(['insert_name' => $trimmedName]);
        }
        // Chuyển đổi mảng thành JSON trước khi lưu
        $formattedFront = json_encode($this->splitdatasFront, JSON_UNESCAPED_SLASHES);
        $formattedBack = json_encode($this->splitdatasBack, JSON_UNESCAPED_SLASHES);
        $check = insert_name_color
            ::where('insert_name', $trimmedName)
            ->where('program_name', $this->program->collection)
            ->where('sport', $this->program->sp)
            ->orderBy('year', 'desc')
            ->get();
        if ($check->isNotEmpty()) {
            foreach ($check as $item) {
                $checkyear = insert_name_color::where('insert_name', $trimmedName)
                    ->where('sport', $this->program->sp)
                    ->where('program_name', $this->program->collection)
                    ->pluck('year')
                    ->toArray();
                // Nếu back  trùng, kiểm tra năm và update
                if (in_array($item->year, $checkyear)) {
                    $prepressColorsFront = json_decode($item->prepress_color_front, true);
                    $cleanedFront = array_map('trim', $prepressColorsFront);
                    $formattedCleanedFront = array_map('trim', $this->splitdatasFront);
                    // Kiểm tra và giải mã prepress_color_back
                    $prepressColorsBack = json_decode($item->prepress_color_back, true);
                    $cleanedBack = array_map('trim', $prepressColorsBack);
                    $formattedCleanedBack = array_map('trim', $this->splitdatasBack);
                    //sắp sếp lại màu sắc
                    sort($cleanedFront);
                    sort($formattedCleanedFront);
                    sort($cleanedBack);
                    sort($formattedCleanedBack);
                    // So sánh các phần tử trong prepress_color_back
                    $resultback = array_diff($formattedCleanedBack, $cleanedBack);
                    // So sánh các phần tử trong prepress_color_front
                    $resultFront = array_diff($formattedCleanedFront, $cleanedFront);
                    //                    dd($resultFront, $cleanedFront, $formattedCleanedFront);
                    //                    dd($resultback, $cleanedBack, $formattedCleanedBack);
                    //                    dd($resultFront, $resultback);
                    if ($resultFront || $resultback) {
                        insert_name_color::where('insert_name', $trimmedName)
                            ->where('program_name', $this->program->collection)
                            ->where('sport', $this->program->sp)
                            ->where('year', $checkyear)
                            ->update([
                                'prepress_color_back' => $formattedBack,
                                'prepress_color_front' => $formattedFront,
                                'config' => $this->SubForms->config,
                                'insert_short_name' => $this->ProgramListServices->shortenInsertName($trimmedName),
                                'program_name' => $this->program->collection,
                                'insert_name' => $trimmedName,
                                'sport' => $this->program->sp,
                            ]);
                    }
                } else {
                    insert_name_color::create([
                        'description' => 3,
                        'insert_name' => $trimmedName,
                        'sport' => $this->program->sp,
                        'year' => $this->program->year,
                        'program_name' => $this->program->collection,
                        'prepress_color_front' => $formattedFront,
                        'prepress_color_back' => $formattedBack,
                        'config' => $this->SubForms->config,
                        'insert_short_name' => $this->ProgramListServices->shortenInsertName($trimmedName)
                    ]);
                }
            }
        } else {
            insert_name_color::create([
                'insert_name' => $trimmedName,
                'sport' => $this->program->sp,
                'year' => $this->program->year,
                'program_name' => $this->program->collection,
                'prepress_color_front' => $formattedFront,
                'prepress_color_back' => $formattedBack,
                'config' => $this->SubForms->config,
                'insert_short_name' => $this->ProgramListServices->shortenInsertName($trimmedName)
            ]);
        }


        try {
            Program_subforms::where('id', $this->SubForm_Id)
                ->update([
                    'prepress_color_front_json' => $formattedFront,
                    'prepress_color_back_json' => $formattedBack,
                ]);
            $this->emit('updateList');
            $this->showAlertMessage('success', 'Colors Updated Successfully');
            $this->isChangedFront = false;
            $this->isChangedBack = false;
        } catch (\Exception $e) {
            $this->showAlertMessage('error', 'Error Updating Colors');
        }
    }

    public function copyColor()
    {
        session([
            'copiedData' => [
                'front' => $this->splitdatasFront,
                'back' => $this->splitdatasBack,
            ]
        ]);
        $this->showAlertMessage('success', 'Colors copied successfully.');
    }

    public function pasteColor()
    {
        // Lấy dữ liệu từ session
        $copiedData = session('copiedData', []);
        if (!empty($copiedData)) {
            // Dán Front Color
            if (!empty($copiedData['front'])) {
                $this->splitdatasFront = $copiedData['front'];

                foreach ($this->headersFront as $group => &$items) {
                    foreach ($items as &$item) {
                        $value = $item['checkbox'] == 0 ? $group : "{$group}{$item['checkbox']}";
                        $item['status'] = in_array($value, $this->splitdatasFront);
                    }
                }
            }

            // Dán Back Color
            if (!empty($copiedData['back'])) {
                $this->splitdatasBack = $copiedData['back'];

                foreach ($this->headersBack as $group => &$items) {
                    foreach ($items as &$item) {
                        $value = $item['checkbox'] == 0 ? $group : "{$group}{$item['checkbox']}";
                        $item['status'] = in_array($value, $this->splitdatasBack);
                    }
                }
            }

            $this->showAlertMessage('success', 'Colors pasted successfully.');
        } else {
            // Hiển thị thông báo nếu không có dữ liệu trong session
            $this->showAlertMessage('error', 'No copied data available to paste.');
        }
    }


    public function toggleCheckbox($group, $checkbox, $numInFront, $isChecked, $isFront)
    {

        $value = $numInFront == 1 ? "{$checkbox}{$group}" : ($checkbox == 0 ? $group : "{$group}{$checkbox}");
        if ($isFront == 'front') {
            if ($isChecked) {
                if (!in_array($value, $this->splitdatasFront)) {
                    $this->splitdatasFront[] = $value;
                    $this->isChangedFront = true;
                }
                foreach ($this->headersFront as &$groupItems) {
                    foreach ($groupItems as &$item) {
                        if ($item['name'] === $group && $item['checkbox'] == $checkbox) {
                            $item['status'] = 1;
                        }
                    }
                }
                //                Log::channel('mylog')->info('Original Data:', [$this->headersFront]);

            } else {
                $this->splitdatasFront = array_filter($this->splitdatasFront, function ($item) use ($value) {
                    return $item !== $value;
                });
                $this->isChangedFront = true;
                foreach ($this->headersFront as &$groupItems) {
                    foreach ($groupItems as &$item) {
                        if ($item['name'] === $group && $item['checkbox'] == $checkbox) {
                            $item['status'] = 0;
                        }
                    }
                }
            }
            $this->splitdatasFront = $this->groupAndSortSplitDatas($this->splitdatasFront, $isFront);
        } else {
            if ($isChecked) {
                if (!in_array($value, $this->splitdatasBack)) {
                    $this->splitdatasBack[] = $value;
                    $this->isChangedBack = true;
                }
                foreach ($this->headersFront as &$groupItems) {
                    foreach ($groupItems as &$item) {
                        if ($item['name'] === $group && $item['checkbox'] == $checkbox) {
                            $item['status'] = 1;
                        }
                    }
                }
            } else {
                $this->splitdatasBack = array_filter($this->splitdatasBack, function ($item) use ($value) {
                    return $item !== $value;
                });
                $this->isChangedBack = true;
                foreach ($this->headersFront as &$groupItems) {
                    foreach ($groupItems as &$item) {
                        if ($item['name'] === $group && $item['checkbox'] == $checkbox) {
                            $item['status'] = 0;
                        }
                    }
                }
            }
            $this->splitdatasBack = $this->groupAndSortSplitDatas($this->splitdatasBack, $isFront);
        }
    }


    private function groupAndSortSplitDatas($data, $isFront)
    {

        $frontGroups = collect($this->pressColorGroupArray())
            ->filter(function ($item) {
                return $item['front'] == 1;
            })
            ->pluck('name')
            ->toArray();
        $backGroups = collect($this->pressColorGroupArray())
            ->filter(function ($item) {
                return $item['back'] == 1;
            })
            ->pluck('name')
            ->toArray();;
        $grouped = [];
        foreach ($data as $item) {
            if (preg_match('/^(\d+)(THK|THK_B)$/', $item, $matches)) {
                // Nếu là "THK" hoặc "THK_B", dùng regex riêng
                $groupName = $matches[2];
                $checkboxValue = $matches[1];
                $grouped[$groupName][] = $checkboxValue;
                //                dd($grouped);
            } else {
                // Nếu không phải là "THK" hoặc "THK_B", dùng regex bình thường
                preg_match('/^(.*?)(\d+)?$/', $item, $matches);
                $groupName = $matches[1];
                $checkboxValue = isset($matches[2]) ? $matches[2] : 0;
                $grouped[$groupName][] = $checkboxValue;
            }
        }

        // Sắp xếp nhóm theo thứ tự gốc
        $sortedData = [];
        if ($isFront == 'front') {
            foreach ($frontGroups as $group) {
                if (isset($grouped[$group])) {
                    sort($grouped[$group]); // Sắp xếp số tăng dần
                    // Thêm giá trị vào danh sách kết quả
                    foreach ($grouped[$group] as $checkboxValue) {
                        if (in_array($group, ['THK', 'THK_B'])) {
                            $sortedData[] = $checkboxValue == 0 ? $group : "{$checkboxValue}{$group}";
                        } else {
                            $sortedData[] = $checkboxValue == 0 ? $group : "{$group}{$checkboxValue}";
                        }
                    }
                }
            }
        } else {
            foreach ($backGroups as $group) {
                if (isset($grouped[$group])) {
                    // Sắp xếp các checkbox trong nhóm
                    sort($grouped[$group]); // Sắp xếp số tăng dần
                    // Thêm giá trị vào danh sách kết quả
                    foreach ($grouped[$group] as $checkboxValue) {
                        if (in_array($group, ['THK', 'THK_B'])) {
                            $sortedData[] = $checkboxValue == 0 ? $group : "{$checkboxValue}{$group}";
                        } else {
                            // Nếu không phải là "THK" hoặc "THK_B", giữ nguyên cách cũ
                            $sortedData[] = $checkboxValue == 0 ? $group : "{$group}{$checkboxValue}";
                        }
                    }
                }
            }
        }


        return $sortedData;
    }

    public function next()
    {
        $this->lap = true;
        if ($this->sortOption === "form") {
            $this->SubForm_Id++;
            $this->loadData();
        } elseif ($this->sortOption === "insert_name") {
            $nextSubForm = Program_subforms::where('insert_name', '>=', $this->SubForms->insert_name)
                ->where('program_id', $this->SubForms->program_id)
                ->where(function ($query) {
                    $query->where('insert_name', '>', $this->SubForms->insert_name)
                        ->orWhere('id', '>', $this->SubForm_Id);
                })
                ->orderBy('insert_name', 'asc')
                ->orderBy('id', 'asc')
                ->first();

            if ($nextSubForm) {
                $this->SubForm_Id = $nextSubForm->id;
                $this->loadData();
                $this->checkBoxcolor();
            } else {
                $this->showAlertMessage('error', 'No next form found.');
            }
        }
    }


    public function previous()
    {
        if ($this->sortOption === "form") {
            $this->SubForm_Id--; // Giảm id
            $this->loadData(); // Cập nhật chi tiết form

        } elseif ($this->sortOption === "insert_name") {
            $previousSubForm = Program_subforms::where('insert_name', '<=', $this->SubForms->insert_name)
                ->where('program_id', $this->SubForms->program_id)
                ->where(function ($query) {
                    $query->where('insert_name', '<', $this->SubForms->insert_name)
                        ->orWhere('id', '<', $this->SubForm_Id);
                })
                ->orderBy('insert_name', 'desc')
                ->orderBy('id', 'desc')
                ->first();

            if ($previousSubForm) {
                $this->SubForm_Id = $previousSubForm->id;

                $this->loadData();
            } else {
                $this->showAlertMessage('error', 'No previous form found.');
            }
        }
    }


    private function loadData()
    {

        $this->emit('updateList');
        foreach ($this->headersFront as $group => &$items) {
            foreach ($items as &$item) {
                $item['status'] = 0;
            }
        }

        foreach ($this->headersBack as $group => &$items) {
            foreach ($items as &$item) {
                $item['status'] = 0;
            }
        }
        $this->headersFront = [];
        $this->headersBack = [];
        $this->SubForms = Program_subforms::where('id', $this->SubForm_Id)->first();
        if ($this->SubForms) {
            $this->form = $this->SubForms->form;
            $this->isChangedFront = false;
            $this->isChangedBack = false;
            $this->checkBoxcolor();

            // Cập nhật dữ liệu màu sắc
        } else {
            $this->showAlertMessage('error', 'No form found with the given ID.');
        }
    }

    public function render()
    {
        $this->firstSubFormId = Program_subforms::where('program_id', $this->SubForms->program_id)
            ->orderBy('id', 'asc')
            ->value('id');
        return view('livewire.modals.program.change-color', [
            'ProgramId' => $this->SubForms->program_id,
            'SubForm_Id' => $this->SubForm_Id,
            'firstSubFormId' => $this->firstSubFormId,
        ]);
    }

    public static function modalMaxWidth(): string
    {
        return '7xl';
    }

    public static function closeModalOnEscape(): bool
    {
        return false;
    }

    public static function closeModalOnClickAway(): bool
    {
        return false;
    }
}
