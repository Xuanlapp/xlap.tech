<?php

namespace App\Http\Livewire\Modals\Program;

use App\Models\Program_forms;
use Livewire\WithPagination;
use LivewireUI\Modal\ModalComponent;
use App\Models\Programs;
use Maatwebsite\Excel\Facades\Excel;
use Livewire\WithFileUploads;
use App\Http\Livewire\Traits\Notification;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Carbon\Carbon;

class ImportDoudateExcel extends ModalComponent
{
    use WithPagination, LivewireAlert, Notification, WithFileUploads;

    public $file;

    public function mount()
    {
    }

    public $DouDateFile;

    public function uploadDouDateFile()
    {
        $this->validate([
            'DouDateFile' => 'required|file',
        ]);

        if (!$this->DouDateFile) {
            $this->showAlertMessage('error', 'No file uploaded.');
            return;
        }
        try {
            $this->validate([
                'DouDateFile' => 'required|file',
            ]);
            if (!$this->DouDateFile) {
                $this->showAlertMessage('error', 'No file uploaded.');
                return;
            }
            $fileName = $this->DouDateFile->getClientOriginalName();
            // Sử dụng biểu thức chính quy để lấy số ở đầu tên file
            preg_match('/^\d+/', $fileName, $matches);
            // Kiểm tra nếu tìm thấy số
            $fileNumber = isset($matches[0]) ? $matches[0] : null;
            $data = Excel::toArray([], $this->DouDateFile->getRealPath())[0];
            $program = Programs::where('code', $fileNumber)->first();
//            dd($program);
            if (!$program) {
                $this->showAlertMessage('warning', 'Program not found');
                return;
            } else {
                $ship = $this->convertToDate($data[1][43] ?? null); // Dòng thứ 2 của dữ liệu
                if (!$ship) {
                    $this->showAlertMessage('warning', 'Ship date not found.');
                    return;
                } else {
                    $program->ship = $ship;
                    $program->save();
                }

                foreach ($data as $index => $row) {
                    if ($index === 0) {
                        continue;
                    }
                    $config = $row[0] ?? null;
                    $code = $row[1] ?? null;
                    $year = $row[2] ?? null;
                    $collection = trim($row[3] ?? '');
                    $sp = $row[4] ?? null;
                    $fm = $row[5] ?? null;
                    $insert_name = $row[6] ?? null;
                    $plist = $this->convertToDate($row[9] ?? null);
//                dd("chuyển ngày:", $this->convertToDate(44205));
//                dd($row[12]);
                    $doneplist = $this->convertToDate($row[10] ?? null);
                    $notephoto = $this->convertToDate($row[11] ?? null);
                    $photo = $this->convertToDate($row[12] ?? null);
                    $donephoto = $this->convertToDate($row[13] ?? null);
                    $notecolor = $this->convertToDate($row[14] ?? null);
                    $color = $this->convertToDate($row[15] ?? null);
                    $outcolor = $this->convertToDate($row[16] ?? null);
                    $donecolor = $this->convertToDate($row[17] ?? null);
                    $noteedit = $this->convertToDate($row[18] ?? null);
                    $edit = $this->convertToDate($row[19] ?? null);
                    $doneedit = $this->convertToDate($row[20] ?? null);
                    $notetr = $this->convertToDate($row[21] ?? null);
                    $TR = $this->convertToDate($row[22] ?? null);
                    $intr = $this->convertToDate($row[23] ?? null);
                    $outtr = $this->convertToDate($row[24] ?? null);
                    $backtr = $this->convertToDate($row[25] ?? null);
                    $noterd1 = $this->convertToDate($row[26] ?? null);
                    $rd1 = $this->convertToDate($row[27] ?? null);
                    $inrd1 = $this->convertToDate($row[28] ?? null);
                    $outrd1 = $this->convertToDate($row[29] ?? null);
                    $noterd2 = $this->convertToDate($row[30] ?? null);
                    $rd2 = $this->convertToDate($row[31] ?? null);
                    $inrd2 = $this->convertToDate($row[32] ?? null);
                    $outrd2 = $this->convertToDate($row[33] ?? null);
                    $noteok = $this->convertToDate($row[34] ?? null);
                    $ok = $this->convertToDate($row[35] ?? null);
                    $plyr = $this->convertToDate($row[36] ?? null);
                    $prop = $this->convertToDate($row[37] ?? null);
                    $introk = $this->convertToDate($row[38] ?? null);
                    $notefiles = $this->convertToDate($row[39] ?? null);
                    $files = $this->convertToDate($row[40] ?? null);
                    $infiles = $this->convertToDate($row[41] ?? null);
                    $outfiles = $this->convertToDate($row[42] ?? null);

                    if (!$config || !$code || !$year || !$collection) {
                        continue;
                    }
//                    $program = Programs::whereRaw('RIGHT(CAST(code AS CHAR), 4) = ?', [$code])
//                        ->where('collection', $collection)
//                        ->where('year', $year)
//                        ->where('sp', $sp)
//                        ->first();
//                    if ($program) {
                    $programForms = Program_forms::where('program_id', $program->id)
                        ->where('config', 'like', "%$config%")
                        ->where('insert_name', 'like', "%$insert_name%")
                        ->where('form', 'like', "%$fm%")
                        ->first();
                    if ($programForms) {
//                        dd($row);
//                        dd("lập nè");
                        $programForms->plist = json_encode(['due' => $plist ?? '', 'done' => $doneplist ?? '']);
                        $programForms->photo = json_encode(['note' => $notephoto ?? '', 'due' => $photo ?? '',]);
                        $programForms->color = json_encode([
                            'note' => $notecolor ?? '',
                            'due' => $color ?? '',
                            'out' => $outcolor ?? '',
                            'done' => $donecolor ?? ''
                        ]);
                        $programForms->edit = json_encode([
                            'note' => $noteedit ?? '',
                            'due' => $edit ?? '',
                            'done' => $doneedit ?? ''
                        ]);
                        $programForms->TR = json_encode([
                            'note' => $notetr ?? '',
                            'due' => $TR ?? '',
                            'in' => $intr ?? '',
                            'out' => $outtr ?? '',
                            'back' => $backtr ?? ''
                        ]);

                        $programForms->rd1 = json_encode([
                            'note' => $noterd1 ?? '',
                            'due' => $rd1 ?? '',
                            'in' => $inrd1 ?? '',
                            'out' => $outrd1 ?? ''
                        ]);
                        $programForms->rd2 = json_encode([
                            'note' => $noterd2 ?? '',
                            'due' => $rd2 ?? '',
                            'in' => $inrd2 ?? '',
                            'out' => $outrd2 ?? ''
                        ]);
                        $programForms->ok = json_encode([
                            'note' => $noteok ?? '',
                            'due' => $ok ?? '',
                            'plyr' => $plyr ?? '',
                            'prop' => $prop ?? '',
                            'intr' => $introk ?? ''
                        ]);
                        $programForms->files = json_encode([
                            'note' => $notefiles ?? '',
                            'due' => $files ?? '',
                            'in' => $infiles ?? '',
                            'out' => $outfiles ?? ''
                        ]);
//                        $programForms->ship = json_encode(['due' => $Ship]);
                        // $programForms->save();
                        if ($programForms->save()) {
                            $this->showAlertMessage('success', 'Updated success!');
                            $this->dispatchBrowserEvent('refresh-page');
                        } else {
                            $this->showAlertMessage('error', 'Failed to save program forms.');
                            $this->dispatchBrowserEvent('refresh-page');
                        }
                    } else {
                        $this->showAlertMessage('warning',
                            'Due date upload successfully ,some insert forms were not found');
                        $this->dispatchBrowserEvent('refresh-page');
                    }
//                    } else {
//                        $this->showAlertMessage('warning', 'Program Form not found');
//                        $this->dispatchBrowserEvent('refresh-page');
//                    }
                }


            }
        } catch (\Exception $e) {
            session()->flash('error', 'Error processing the file: ' . $e->getMessage());
        }
    }

    private function convertToDate($value)
    {
        try {
            // Kiểm tra nếu $value là số (Excel date serial)
            if (is_numeric($value)) {
                // Ngày gốc trong Excel là 01-01-1900
                $startDate = Carbon::createFromFormat('m-d-Y', '01-01-1900');

                // Cộng thêm số ngày từ Excel (trừ 2 ngày để điều chỉnh lỗi năm nhuận 1900)
                $convertedDate = $startDate->addDays($value - 2);

                // Cộng thêm 4 năm lẻ 1 ngày
                $convertedDate = $convertedDate->addYears(4)->addDay();

                // Trả về định dạng m-d-Y
                return $convertedDate->format('m-d-Y');
            }

            // Nếu $value là chuỗi ngày hợp lệ, xử lý và định dạng lại
            if (!empty($value)) {
                // Cộng thêm 4 năm lẻ 1 ngày vào ngày hiện tại
                return Carbon::parse($value)->addYears(4)->addDay()->format('m-d-Y');
            }

            // Nếu $value rỗng, trả về null
            return null;
        } catch (\Exception $e) {
            // Bắt lỗi và trả về null nếu có lỗi xảy ra
            return null;
        }
    }

//    private function convertToDate($value)
//    {
//        try {
//            // Kiểm tra nếu $value là số (Excel date serial)
////            dd("Giá trị đầu vào:", $value);
//            if (is_numeric($value)) {
//                // Ngày gốc trong Excel là 01-01-1900
//                $startDate = Carbon::createFromFormat('m-d-Y', '01-01-1900');
//
//                // Cộng thêm số ngày từ Excel (trừ 2 ngày để điều chỉnh lỗi năm nhuận 1900)
//                $convertedDate = $startDate->addDays($value - 2);
////                dd("Kết quả ngày:", $convertedDate->format('m-d-Y'));
//
//                // Trả về định dạng m-d-Y
//                return $convertedDate->format('m-d-Y');
//            }
//
//            // Nếu $value là chuỗi ngày hợp lệ, xử lý và định dạng lại
//            if (!empty($value)) {
//                return Carbon::parse($value)->format('m-d-Y');
//            }
//
//            // Nếu $value rỗng, trả về null
//            return null;
//        } catch (\Exception $e) {
//            // Bắt lỗi và trả về null nếu có lỗi xảy ra
//            return null;
//        }
//    }
//


    public function render()
    {
        return view('livewire.modals.program.import-doudate');
    }


    public static function modalMaxWidth(): string
    {
        return '7xl';
    }
}
