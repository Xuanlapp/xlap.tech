<?php

namespace App\Http\Livewire\Pages\Program;

use App\Http\Livewire\Traits\Notification;
use App\Models\Program_forms;
use App\Models\Program_subforms;
use App\Models\Programs;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\Program\ProgramFunctionServices;

class ProgramFormsList extends Component
{
    use Notification, LivewireAlert;

    public $programId, $mainPrograms = [], $Programs = [], $showDropdown = null, $Showsub_forms = null, $cutSubForm, $totalForms = 0, $totalInserts = 0;
    private $programFunctionServices;

    public function processPrepressData($programId)
    {
        $forms = Program_forms::where('program_id', $programId)->get();
        $subForms = Program_subforms::where('program_id', $programId)->get();
        foreach ($forms as $form) {
            // Tách dữ liệu từ prepress_color_front
            $cutPrepressColorFont = $this->splitPrepressData($form->prepress_color_front);

            // Tách dữ liệu từ prepress_color_back
            $cutPrepressColorBack = $this->splitPrepressData($form->prepress_color_back);

            // Cập nhật vào bảng forms
            Program_forms::where('id', $form->id)->update([
                'prepress_color_front_json' => json_encode($cutPrepressColorFont),
                'prepress_color_back_json' => json_encode($cutPrepressColorBack),
            ]);
        }
        foreach ($subForms as $subForm) {
            // Tách dữ liệu từ prepress_color_front
            $cutPrepressColorFont = $this->splitPrepressData($subForm->prepress_color_front);

            // Tách dữ liệu từ prepress_color_back
            $cutPrepressColorBack = $this->splitPrepressData($subForm->prepress_color_back);

            // Cập nhật vào bảng subforms
            Program_subforms::where('id', $subForm->id)->update([
                'prepress_color_front_json' => json_encode($cutPrepressColorFont),
                'prepress_color_back_json' => json_encode($cutPrepressColorBack),
            ]);
        }
        $this->showAlertMessage('success', 'Prepress data processed successfully.');
    }

    private function splitPrepressData($data)
    {
        if (!$data) {
            return []; // Trả về mảng rỗng nếu không có dữ liệu
        }
        // Tách chuỗi theo dấu "+" và loại bỏ khoảng trắng
        return array_map(function ($item) {
            return str_replace(' ', '', trim($item));
        }, explode('+', $data));
    }

    public function exportAllSubForms($ProgramId)
    {
        $programCode = Programs::where('id', $ProgramId)->value('code');
        $subForms = Program_subforms::where('program_id', $ProgramId)
            // ->whereColumn('form_group', 'insert_name_group')
            ->whereColumn('color_group', 'insert_name_group')
            ->orderBy('id')
            ->get()
            ->groupBy(function ($item) {
                return $item->insert_name . '|' . $item->prepress_color_front . '|' . $item->prepress_color_back;
            })
            ->map(function ($group, $key) {
                $keyParts = explode('|', $key);
                return [
                    'form_group' => $group->first()->form_group,
                    'color_group' => $group->first()->color_group,
                    'insert_name' => $keyParts[0],
                    'prepress_color_front' => $keyParts[1],
                    'prepress_color_back' => $keyParts[2],
                    'forms' => implode(', ', $group->pluck('form')->toArray()),
                ];
            })
            ->values()
            ->toArray();
        $csvData = [];
        $csvData[] = ['Form #', 'Insert Name', 'Prepress Color Front', 'Prepress Color Back', 'Form Group']; // Header
        foreach ($subForms as $group) {
            $csvData[] = [
                ' ' . $group['forms'],
                $group['insert_name'],
                $group['prepress_color_front'],
                $group['prepress_color_back'],
                $group['form_group'],
                $group['color_group'],
            ];
        }
        // Tạo file CSV
        $fileName = "subforms_export_program_{$programCode}.csv";
        $handle = fopen($fileName, 'w');
        foreach ($csvData as $line) {
            fputcsv($handle, $line);
        }
        fclose($handle);

        // Trả file về trình duyệt
        return response()->download($fileName)->deleteFileAfterSend(true);
    }

    public function exportCsv()
    {
        //        return Excel::download(new ProgramsListExportRepository, 'programs.csv');
        return Excel::download(new ProgramsListExport($this->programId), 'programs.csv');
    }

    public function mount()
    {
        $this->programFunctionServices = app(ProgramFunctionServices::class);
        $this->totalForms = Program_forms::where('program_id', $this->programId)->count();
        $this->totalInserts = Program_subforms::where('program_id', $this->programId)->count();
        $this->mainPrograms = Program_forms::where('program_id', $this->programId)
            ->with('subPrograms')
            ->get();
    }

    public function GroupSubForm($ProgramId)
    {
        if (!$this->programFunctionServices) {
            $this->programFunctionServices = app(ProgramFunctionServices::class);
        }
        // 检查$ProgramId是否为null
        if ($ProgramId === null) {
            $this->showAlertMessage('error', 'Program ID cannot be null.');
            return;
        }

        //        dd($ProgramId);
        $this->programFunctionServices->GroupSubForm($ProgramId);
        // $subForms = Program_subforms::where('program_id', $ProgramId)->get();
        // //lấy những form cua subform không có "-"
        // $filteredForms = $subForms->filter(function ($form) {
        //     return strpos($form->form, '-') === false;
        // });
        // $formCounts = $filteredForms->groupBy('form')->map(function ($group) {
        //     return $group->count();
        // });

        // while ($formCounts->isNotEmpty()) {
        //     // Lấy các form có lặp lại >= 2 lần
        //     $duplicateForms = $formCounts->filter(function ($count) {
        //         return $count >= 2;
        //     });

        //     // Nếu không còn form lặp lại, thoát khỏi vòng lặp
        //     if ($duplicateForms->isEmpty()) {
        //         break;
        //     }
        //     //            dd($duplicateForms);
        //     // Xử lý từng form lặp lại
        //     $duplicateForms->each(function ($count, $form) use ($ProgramId, &$formCounts) {
        //         $this->assignFormGroups($ProgramId, $form, "Multi Form");
        //         $this->assignColorGroups($ProgramId, $form, "Multi Form");
        //         $this->assignInsertNameGroups($ProgramId, $form, "Multi Form");
        //         $formCounts->forget($form);
        //     });
        // }
        // $subform = Program_subforms::whereIn('form', $formCounts->keys())
        //     ->whereRaw("form NOT LIKE '%-%'")
        //     ->where('program_id', $ProgramId)
        //     ->orderBy('id')
        //     ->get();
        // while ($formCounts->isNotEmpty()) {
        //     $firstFormId = $formCounts->keys()->first();
        //     $firstSubform = $subform->firstWhere('form', $firstFormId);
        //     $firstInsertName = $firstSubform->insert_name;
        //     // Lọc các form có insert_name trùng với insert_name của form đầu tiên
        //     $matchingForms = $subform->filter(function ($subform) use ($firstInsertName) {
        //         return $subform->insert_name === $firstInsertName;
        //     });
        //     // Kiểm tra nếu có ít nhất 1 form trùng (chắc chắn có form trùng để xử lý)
        //     if ($matchingForms->count() > 1) {
        //         $this->assignMergeFormGroups($ProgramId, $matchingForms);
        //         $matchingFormIds = $matchingForms->pluck('form')->toArray();
        //         $formCounts = $formCounts->forget($matchingFormIds);
        //     } else {
        //         // Xử lý khi chỉ có một form trùng
        //         $this->assignFormGroups($ProgramId, $matchingForms, "Single Form");
        //         $this->assignColorGroups($ProgramId, $matchingForms, "Single Form");
        //         $this->assignInsertNameGroups($ProgramId, $matchingForms, "Single Form");
        //         $formCounts = $formCounts->forget($firstFormId);
        //         //
        //     }
        //     // Điều kiện tiếp tục vòng lặp: chỉ khi vẫn còn form trong formCounts
        //     if ($formCounts->isEmpty()) {
        //         break; // Dừng vòng lặp khi không còn form nào
        //     }
        // }


        $this->showAlertMessage('success', 'All forms and subforms updated successfully!');
        $this->dispatchBrowserEvent('refresh-page');
    }


    // **Hàm xử lý `form_group`**
    public function assignMergeFormGroups($ProgramId, $form)
    {
        //        dd($form);
        $formValues = $form->pluck('form')->sort();
        $remainingSubForms = collect();
        foreach ($formValues as $formValue) {
            // Thực hiện truy vấn cho mỗi giá trị formValue
            $subForms = Program_subforms::where('program_id', $ProgramId)
                ->whereRaw('SUBSTRING_INDEX(form, "-", 1) = ?', [$formValue])
                ->orderBy('id')
                ->get();

            // Gom kết quả vào collection
            $remainingSubForms = $remainingSubForms->merge($subForms);
        }

        $firstId = $remainingSubForms->first()?->id;

        if ($firstId) {
            foreach ($formValues as $formValue) {
                // Gán form_group cho tất cả các subForms
                Program_subforms::where('program_id', $ProgramId)
                    ->whereRaw('SUBSTRING_INDEX(form, "-", 1) = ?', [$formValue])
                    ->update(['form_group' => $firstId]);
            }
        }
        $this->assignMergeColorGroups($ProgramId, $firstId);
    }

    public function assignMergeColorGroups($ProgramId, $Form_group)
    {
        //        dd($Form_group);
        $subForms = Program_subforms::where('program_id', $ProgramId)
            ->where('form_group', $Form_group)
            ->orderBy('id')
            ->get();
        $firstGroupSubForm = $subForms->first();
        $Group_Form = $firstGroupSubForm->id;
        while ($subForms->isNotEmpty()) {
            // Lấy bản ghi đầu tiên trong collection
            $firstSubForm = $subForms->first();
            $firstId = $firstSubForm->id;
            $firstColor = $firstSubForm->prepress_color_front;
            // Tìm các bản ghi có prepress_color_front giống với màu của bản ghi đầu tiên
            $matchingSubForms = $subForms->filter(function ($subForm) use ($firstColor) {
                return $subForm->prepress_color_front === $firstColor;
            });

            // Cập nhật color_group cho tất cả các bản ghi trùng màu
            $matchingSubForms->each(function ($subForm) use ($firstId) {
                $subForm->color_group = $firstId;
                $subForm->save();
            });
            // Xóa các bản ghi đã xử lý khỏi collection
            $subForms = $subForms->diff($matchingSubForms);
        }

        $this->assignMergeInsertNameGroups($ProgramId, $Group_Form);
    }


    public function assignMergeInsertNameGroups($ProgramId, $Form_group)
    {
        $subForms = Program_subforms::where('program_id', $ProgramId)
            ->where('form_group', $Form_group)
            ->orderBy('id')
            ->get();
        // Kiểm tra nếu có dữ liệu và thực hiện các thao tác tiếp theo
        // Duyệt qua các bản ghi
        while ($subForms->isNotEmpty()) {
            // Lấy bản ghi đầu tiên trong collection
            $firstSubForm = $subForms->first();
            $firstId = $firstSubForm->id;
            $firstInsertName = $firstSubForm->insert_name;

            // Tìm các bản ghi có insert_name giống với giá trị của bản ghi đầu tiên
            $matchingSubForms = $subForms->filter(function ($subForm) use ($firstInsertName) {
                return $subForm->insert_name === $firstInsertName;
            });

            // Cập nhật color_group cho tất cả các bản ghi trùng insert_name
            $matchingSubForms->each(function ($subForm) use ($firstId) {
                $subForm->insert_name_group = $firstId;
                $subForm->save();
            });

            // Xóa các bản ghi đã xử lý khỏi collection
            $subForms = $subForms->diff($matchingSubForms);
        }
    }


    public function assignFormGroups($ProgramId, $form, $status)
    {
        if ($status == 'Single Form') {
            $formValues = $form->pluck('form')->sort();
            $remainingSubForms = collect();
            foreach ($formValues as $formValue) {
                // Thực hiện truy vấn cho mỗi giá trị formValue
                $subForms = Program_subforms::where('program_id', $ProgramId)
                    ->whereRaw('SUBSTRING_INDEX(form, "-", 1) = ?', [$formValue])
                    ->orderBy('id')
                    ->get();

                $remainingSubForms = $remainingSubForms->merge($subForms);
            }
        } else {
            $remainingSubForms = Program_subforms::where('program_id', $ProgramId)
                ->whereRaw('SUBSTRING_INDEX(form, "-", 1) = ?', [$form])
                ->orderBy('id')
                ->get();
        }
        $isFormGroupComplete = false;
        while (!$isFormGroupComplete) {
            $isFormGroupComplete = true;

            foreach ($remainingSubForms as $subForm) {
                $currentInsertName = implode(' ', array_slice(explode(' ', trim($subForm->insert_name)), 0, 2));
                //                $currentInsertName = trim($subForm->insert_name);
                // Lọc ra các subform có insert_name giống nhau
                $matchingInsertNames = $remainingSubForms->filter(function ($subForm) use ($currentInsertName) {
                    return implode(
                        ' ',
                        array_slice(explode(' ', trim($subForm->insert_name)), 0, 2)
                    ) === $currentInsertName;
                });
                //                $matchingInsertNames = $remainingSubForms->filter(function ($subForm) use ($currentInsertName) {
                //                    $otherInsertName = trim($subForm->insert_name);
                //                    // So sánh khoảng cách Levenshtein giữa currentInsertName và insert_name của từng subform
                //                    return levenshtein($currentInsertName,
                //                            $otherInsertName) <= 3; // Thay số 3 để điều chỉnh mức độ giống nhau
                //                });
                $sortedMatchingSubForms = $matchingInsertNames->sortBy('id');
                $firstId = $sortedMatchingSubForms->first()?->id;

                if ($firstId) {
                    foreach ($sortedMatchingSubForms as $matchedSubForm) {
                        Program_subforms::where('id', $matchedSubForm->id)
                            ->update(['form_group' => $firstId]);
                    }

                    // Lọc lại các subform đã gán form_group
                    $remainingSubForms = $remainingSubForms->reject(function ($subForm) use ($sortedMatchingSubForms) {
                        return $sortedMatchingSubForms->contains('id', $subForm->id);
                    });

                    $isFormGroupComplete = false; // Tiếp tục nếu chưa hoàn thành
                }
            }
        }
    }

    // **Hàm xử lý `color_group`**
    public function assignColorGroups($ProgramId, $form, $status)
    {
        if ($status == 'Single Form') {
            $formValues = $form->pluck('form')->sort();
            $allGroups = collect();
            foreach ($formValues as $formValue) {
                // Thực hiện truy vấn cho mỗi giá trị formValue
                $subForms = Program_subforms::where('program_id', $ProgramId)
                    ->whereRaw('SUBSTRING_INDEX(form, "-", 1) = ?', [$formValue])
                    ->whereNotNull('form_group')
                    ->orderBy('id')
                    ->get()
                    ->groupBy('form_group');

                $allGroups = ($subForms);
            }
        } else {
            $allGroups = Program_subforms::where('program_id', $ProgramId)
                ->whereRaw('SUBSTRING_INDEX(form, "-", 1) = ?', $form)
                ->whereNotNull('form_group')
                ->orderBy('id')
                ->get()
                ->groupBy('form_group');
        }

        foreach ($allGroups as $groupSubForms) {
            $processedIds = []; // Lưu các ID đã xử lý
            $colorGroupMap = []; // Lưu nhóm đã gán cho từng màu

            foreach ($groupSubForms as $subForm) {
                // Bỏ qua nếu đã xử lý
                if (in_array($subForm->id, $processedIds)) {
                    continue;
                }
                $color = $subForm->prepress_color_front;

                // Tạo hoặc lấy nhóm cho màu hiện tại
                $colorGroupId = $colorGroupMap[$color] ?? $subForm->id;
                $colorGroupMap[$color] = $colorGroupId;

                // Lọc các bản ghi trùng màu chưa xử lý
                $matchingForms = $groupSubForms->filter(function ($item) use ($color, $processedIds) {
                    return $item->prepress_color_front === $color && !in_array($item->id, $processedIds);
                });

                // Cập nhật nhóm và đánh dấu đã xử lý
                $matchingForms->each(function ($matchingForm) use (&$processedIds, $colorGroupId) {
                    Program_subforms::where('id', $matchingForm->id)
                        ->update(['color_group' => $colorGroupId]);
                    $processedIds[] = $matchingForm->id; // Đánh dấu đã xử lý
                });
            }
        }
    }

    // **Hàm xử lý `insert_name_group`**
    public function assignInsertNameGroups($ProgramId, $form, $status)
    {

        if ($status == 'Merge Form') {
            $formValues = $form->pluck('form')->sort();
            $allInsertNames = collect();
            foreach ($formValues as $formValue) {
                // Thực hiện truy vấn cho mỗi giá trị formValue
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
                // Thực hiện truy vấn cho mỗi giá trị formValue
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

        // Lọc các subform theo `insert_name` và gán nhóm
        $remainingInsertNames = $allInsertNames->keyBy('id');
        $isInsertNameGroupComplete = false;

        while (!$isInsertNameGroupComplete) {
            $isInsertNameGroupComplete = true;

            foreach ($remainingInsertNames as $subFormId => $subForm) {
                $currentInsertName = $subForm->insert_name;

                // Lọc các subform có insert_name giống nhau
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

                    $isInsertNameGroupComplete = false; // Tiếp tục xử lý nếu chưa hoàn thành
                }
            }
            if ($remainingInsertNames->isEmpty()) {
                break;
            }
        }
    }

    public function SpitForms($ProgramId, $form)
    {
        $status = 'lập';
        // **Bước 1**: Gán `form_group`
        $this->assignFormGroups($ProgramId, $form, $status);

        // **Bước 2**: Lấy dữ liệu đã có `form_group` rồi và tiếp tục xử lý `color_group`
        $this->assignColorGroups($ProgramId, $form, $status);

        // **Bước 3**: Lấy dữ liệu đã có `color_group` rồi và tiếp tục xử lý `insert_name_group`
        $this->assignInsertNameGroups($ProgramId, $form, $status);

        // Sau khi hoàn thành, thông báo thành công và refresh trang
        $this->showAlertMessage('success', 'All forms and subforms updated successfully!');
        $this->dispatchBrowserEvent('refresh-page');
    }

    public function reloadSpitForms($ProgramId, $form)
    {
        $this->cutSubForm = Program_subforms::where('program_id', $ProgramId)
            ->whereRaw('SUBSTRING_INDEX(form, "-", 1) = ?', [$form])
            ->orderBy('id')
            ->get();

        $isFormGroupComplete = false;
        $isColorGroupComplete = false;
        $isInsertNameGroupComplete = false;

        // **Step 1**: Gán `form_group`
        $remainingSubForms = $this->cutSubForm;
        while (!$isFormGroupComplete) {
            $isFormGroupComplete = true;

            foreach ($remainingSubForms as $subFormId => $subForm) {
                $currentInsertName = trim($subForm->insert_name);

                // Tìm các subform có `insert_name` gần giống nhất
                $matchingSubForms = $remainingSubForms->filter(function ($subForm) use ($currentInsertName) {
                    $otherInsertName = trim($subForm->insert_name);

                    // Tính khoảng cách Levenshtein giữa các `insert_name`
                    $distance = levenshtein($currentInsertName, $otherInsertName);

                    // Chỉ lấy các chuỗi có khoảng cách <= 3 (hoặc giá trị phù hợp với yêu cầu của bạn)
                    return $distance <= 2;
                });


                $sortedMatchingSubForms = $matchingSubForms->sortBy('id');
                $firstId = $sortedMatchingSubForms->first()?->id;

                if ($firstId) {
                    foreach ($sortedMatchingSubForms as $matchedSubForm) {
                        Program_subforms::where('id', $matchedSubForm->id)
                            ->update(['form_group' => $firstId]);
                    }

                    $remainingSubForms = $remainingSubForms->reject(function ($subForm) use (
                        $sortedMatchingSubForms
                    ) {
                        return $sortedMatchingSubForms->contains('id', $subForm->id);
                    });

                    $isFormGroupComplete = false;
                }
            }
        }

        $allGroups = Program_subforms::where('program_id', $ProgramId)
            ->whereRaw('SUBSTRING_INDEX(form, "-", 1) = ?', [$form])
            ->whereNotNull('form_group')
            ->orderBy('id')
            ->get()
            ->groupBy('form_group'); // Nhóm dữ liệu theo form_group

        foreach ($allGroups as $formGroupId => $groupSubForms) {
            $processedIds = []; // Lưu các ID đã xử lý
            $colorGroupMap = []; // Lưu nhóm đã gán cho từng màu

            foreach ($groupSubForms as $subForm) {
                // Nếu bản ghi đã xử lý, bỏ qua
                if (in_array($subForm->id, $processedIds)) {
                    continue;
                }

                $color = $subForm->prepress_color_front;

                // Nếu màu đã có nhóm, sử dụng lại
                if (isset($colorGroupMap[$color])) {
                    $colorGroupId = $colorGroupMap[$color];
                } else {
                    // Nếu màu chưa có nhóm, tạo nhóm mới
                    $colorGroupId = $subForm->id;
                    $colorGroupMap[$color] = $colorGroupId;
                }

                // Cập nhật nhóm cho tất cả bản ghi trùng màu trong nhóm này
                $matchingForms = $groupSubForms->filter(function ($item) use ($color, $processedIds) {
                    return $item->prepress_color_front === $color && !in_array($item->id, $processedIds);
                });

                foreach ($matchingForms as $matchingForm) {
                    Program_subforms::where('id', $matchingForm->id)
                        ->update(['color_group' => $colorGroupId]);
                    $processedIds[] = $matchingForm->id; // Đánh dấu đã xử lý
                }
            }
        }
        $isColorGroupComplete = true;

        // **Step 3**: Gán `insert_name_group`
        $allInsertNames = Program_subforms::where('program_id', $ProgramId)
            ->whereRaw('SUBSTRING_INDEX(form, "-", 1) = ?', [$form])
            ->get();
        $remainingInsertNames = $allInsertNames->keyBy('id');
        while (!$isInsertNameGroupComplete) {
            $isInsertNameGroupComplete = true;

            foreach ($remainingInsertNames as $subFormId => $subForm) {
                $currentInsertName = $subForm->insert_name;

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

                    $remainingInsertNames = $remainingInsertNames->reject(function ($subForm) use (
                        $sortedInsertNames
                    ) {
                        return $sortedInsertNames->contains('id', $subForm->id);
                    });

                    $isInsertNameGroupComplete = false;
                }
            }
        }

        // Nếu tất cả 3 bước đã hoàn thành, thoát khỏi `foreach`
        if ($isFormGroupComplete && $isColorGroupComplete && $isInsertNameGroupComplete) {
            $this->showAlertMessage('success', 'All forms and subforms updated successfully!');
            $this->dispatchBrowserEvent('refresh-page');
        }
    }

    public function toggleDropdown($mainProgramId)
    {
        $this->showDropdown = $this->showDropdown === $mainProgramId ? null : $mainProgramId;
    }

    public function render()
    {
        return view('livewire.pages.program.program-forms-list', [
            'mainPrograms' => $this->mainPrograms,
            'programs' => Programs::where('id', $this->programId)->first(),
        ]);
    }
}
