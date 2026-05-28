<?php

namespace App\Http\Livewire\Modals\Program;

use App\Models\Program_forms;
use App\Models\Programs;
use LivewireUI\Modal\ModalComponent;
use Livewire\WithFileUploads;

class ImageImport extends ModalComponent
{
    use WithFileUploads;

    public $program_id, $Form, $Form_id, $selectedFolder, $program_code;

    public function mount()
    {
        $Programs = Programs::where('id', $this->program_id)->first();
        $this->program_code = $Programs->code;
    }


    public function uploadImage()
    {
        //lấy tên của folder
        dd($this->selectedFolder);
//        dd($this->selectedFolder);
        $firstFile = $this->selectedFolder[0];

        // Lấy đường dẫn thư mục từ tệp đầu tiên
        $folderPath = dirname($firstFile->getRealPath());

        // Duyệt qua cấu trúc thư mục để lấy danh sách thư mục con
        $directories = collect(\File::directories($folderPath));  // \File::directories() trả về danh sách các thư mục con

        // Trích xuất tên thư mục (từ đường dẫn)
        $folderNames = $directories->map(function ($directory) {
            return basename($directory);  // Lấy tên thư mục từ đường dẫn
        });

        // Gỡ lỗi: Kiểm tra tên thư mục
        dd($folderNames);

        // Lấy đường dẫn thư mục từ tệp đầu tiên
        $folderPath = dirname($firstFile->getRealPath()); // Đường dẫn thư mục

        // Lấy tên thư mục từ đường dẫn
        $folderName = basename($folderPath); // Trả về tên thư mục

        // Gỡ lỗi: Kiểm tra tên thư mục
        dd($folderName);

        // Lấy thông tin chương trình và form
        $Programs = Programs::where('id', $this->program_id)->first();
        $this->program_code = $Programs->code;
        $Programcode = $Programs->code;
        $Form_id = $this->Form_id;
        $Form = $this->Form;

        // Đường dẫn cơ sở
        $baseDir = public_path('images/program_designs');
        $programDir = $baseDir . DIRECTORY_SEPARATOR . $Programcode;
        $folderName = $Form_id . '-F' . $Form; // Tên thư mục mới
        $folderPath = $programDir . DIRECTORY_SEPARATOR . $folderName;

        try {
            // Tạo thư mục nếu chưa tồn tại
            if (!is_dir($baseDir)) {
                mkdir($baseDir, 0755, true);
            }
            if (!is_dir($programDir)) {
                mkdir($programDir, 0755, true);
            }
            if (!is_dir($folderPath)) {
                mkdir($folderPath, 0755, true);
            }

            // Duyệt qua các file trong thư mục được chọn
            foreach ($this->selectedFolder as $file) {
                // Lấy tên file gốc
                $fileName = $file->getClientOriginalName();

                // Kiểm tra nếu tệp có định dạng hợp lệ và lưu vào thư mục
                $file->storeAs(
                    'images/program_designs/' . $Programcode . '/' . $folderName,
                    $fileName,
                    'public'
                );
            }

            // Thông báo thành công
            session()->flash('success', 'Folder and files have been uploaded successfully.');
        } catch (\Exception $e) {
            // Thông báo lỗi
            session()->flash('error', 'Error uploading folder: ' . $e->getMessage());
        }

        // Reset selectedFolder
        $this->selectedFolder = [];
    }


    public function render()
    {
        return view('livewire.modals.program.image-import', [
            'program_code' => $this->program_code,
            'program_id' => $this->program_id,
            'Form' => $this->Form,
            'Form_id' => $this->Form_id,
        ]);
    }

    public static function modalMaxWidth(): string
    {
        return '7xl';
    }
}
