<?php

namespace App\Http\Livewire\Pages\Program;

use App\Http\Livewire\Traits\Notification;
use App\Models\color_name;
use App\Models\Program_subforms;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class SureColor extends Component
{
    use  LivewireAlert, Notification;

    public $color, $sortOption = 'front';
    public $changeColorTo = [];

    public function mount()
    {
        $this->color = color_name::orderByRaw("FIELD(sure, 0, 1) ASC")->orderBy('color_name', 'asc')->get();
    }


    public function changeColorFront($id)
    {
        // Tìm kiếm color_name dựa trên ID
        $change = color_name::find($id);

        // Lấy tất cả các subform có program_id = 84
        $subForms = Program_subforms::get();
        $save = false;
        foreach ($subForms as $subForm) {
            // Giải mã dữ liệu JSON từ các cột prepress_color_front_json và prepress_color_back_json
            $frontColors = json_decode($subForm->prepress_color_front_json, true);

            if (is_array($frontColors)) {
                foreach ($frontColors as $key => $color) {
                    // Kiểm tra nếu color_name có trong $frontColors
                    if ($color == $change->color_name) {
                        if ($change->to_db == 2) {
                            $change->to_db = 3;
                            $change->save();
                        } else {
                            $change->to_db = 1;
                            $change->save();
                        }
                        // Thay thế bằng change_color_to
                        $frontColors[$key] = $change->change_color_to;
                        $subForm->prepress_color_front_json = json_encode($frontColors);
                        $subForm->save();  // Lưu lại sự thay đổi trong subForm
                    }
                }
            }
        }
        $this->dispatchBrowserEvent('refresh-page');


    }


    public function changeColorBack($id)
    {
        $change = color_name::find($id);
        $subForms = Program_subforms::get();
        foreach ($subForms as $subForm) {
            // Giải mã dữ liệu JSON từ các cột prepress_color_front_json và prepress_color_back_json
            $backColors = json_decode($subForm->prepress_color_back_json, true);

            if (is_array($backColors)) {
                foreach ($backColors as $key => $color) {
                    // Kiểm tra nếu color_name có trong $backColors
                    if ($color == $change->color_name) {
                        // Thay thế bằng change_color_to
                        $backColors[$key] = $change->change_color_to;
                        if ($change->to_db == 1) {
                            $change->to_db = 3;
                            $change->save();
                        } else {
                            $change->to_db = 2;
                            $change->save();
                        }

                        $subForm->prepress_color_front_json = json_encode($backColors);

                        $subForm->save();  // Lưu lại sự thay đổi trong subForm
                    }
                }
            }
        }
        $this->dispatchBrowserEvent('refresh-page');


    }

    public function render()
    {
        return view('livewire.pages.program.sure-color',
            [
                'color' => $this->color,
            ]
        );
    }
}
