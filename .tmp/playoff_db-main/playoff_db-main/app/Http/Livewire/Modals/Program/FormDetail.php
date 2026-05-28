<?php

namespace App\Http\Livewire\Modals\Program;

use App\Http\Livewire\Traits\Notification;
use App\Models\insert_name_color;
use App\Models\Program_forms;

// Cập nhật model
use App\Models\sport_customers;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use LivewireUI\Modal\ModalComponent;

class FormDetail extends ModalComponent
{
    use LivewireAlert, Notification;

    public $program_form, $config = '', $form = '', $insert_name = '', $cards = '', $seq = '', $substrate = '', $foil = '', $autos = '', $pms = '', $prepress_colors_front = '', $lam_front = '', $prepress_colors_back = '', $lam_back = '', $coating_front = '', $coating_back = '', $panini = '', $leagues = '', $stamped = '', $panini_binder = '', $total_inc_sht = '', $program_id = '', $insert_short_name = '';

    public function mount($program_form)
    {
        $this->program_form = Program_forms::findOrFail($program_form);
        $this->config = $this->program_form->config;
        $this->form = $this->program_form->form;
        $this->insert_name = $this->program_form->insert_name;
        $this->cards = $this->program_form->cards;
        $this->seq = $this->program_form->seq;
        $this->substrate = $this->program_form->substrate;
        $this->foil = $this->program_form->foil;
        $this->autos = $this->program_form->autos;
        $this->pms = $this->program_form->pms;
        $this->prepress_colors_front = $this->program_form->prepress_colors_front;
        $this->lam_front = $this->program_form->lam_front;
        $this->prepress_colors_back = $this->program_form->prepress_colors_back;
        $this->lam_back = $this->program_form->lam_back;
        $this->coating_front = $this->program_form->coating_front;
        $this->coating_back = $this->program_form->coating_back;
        $this->panini = $this->program_form->panini;
        $this->leagues = $this->program_form->leagues;
        $this->stamped = $this->program_form->stamped;
        $this->panini_binder = $this->program_form->panini_binder;
        $this->total_inc_sht = $this->program_form->total_inc_sht;
        $this->program_id = $this->program_form->program_id;
        $this->insert_short_name = $this->program_form->insert_short_name;

    }

    public $error_message = null; // Biến để lưu thông báo lỗi

// Lắng nghe sự kiện cập nhật giá trị
    public function updatedInsertShortName($value)
    {
        if (strlen($value) > 10) {
            $this->insert_short_name = substr($value, 0, 10); // Cắt chuỗi về đúng 10 ký tự
            $this->error_message = 'You can only enter up to 10 characters.'; // Gắn thông báo lỗi
        } else {
            $this->error_message = null; // Xóa thông báo lỗi nếu hợp lệ
        }
    }

    public function rules()
    {
        return [
            'seq' => 'required',
            'substrate' => 'required|string|max:255',
            'foil' => 'required|string|max:255',
            'autos' => 'nullable|string|max:255',
            'pms' => 'nullable|string|max:255',
            'lam_front' => 'nullable|string|max:255',
            'lam_back' => 'nullable|string|max:255',
            'coating_front' => 'nullable|string|max:255',
            'coating_back' => 'nullable|string|max:255',
            'panini' => 'nullable|integer',
            'leagues' => 'nullable|integer',
            'stamped' => 'nullable|string|max:255',
            'panini_binder' => 'nullable|string|max:255',
            'total_inc_sht' => 'required|integer',
            'program_id' => 'required|integer',
            'insert_short_name' => 'nullable|string|max:255',
        ];
    }

    public function updateFormDetail()
    {
        try {
            $this->program_form->update([
                'config' => $this->config,
                'form' => $this->form,
                'insert_name' => $this->insert_name,
                'cards' => $this->cards,
                'seq' => $this->seq,
                'substrate' => $this->substrate,
                'foil' => $this->foil,
                'autos' => $this->autos,
                'pms' => $this->pms,
                'prepress_colors_front' => $this->prepress_colors_front,
                'lam_front' => $this->lam_front,
                'prepress_colors_back' => $this->prepress_colors_back,
                'lam_back' => $this->lam_back,
                'coating_front' => $this->coating_front,
                'coating_back' => $this->coating_back,
                'panini' => $this->panini,
                'leagues' => $this->leagues,
                'stamped' => $this->stamped,
                'panini_binder' => $this->panini_binder,
                'total_inc_sht' => $this->total_inc_sht,
                'program_id' => $this->program_id,
                'insert_short_name' => $this->insert_short_name,
            ]);
            $formInsertShortNames = Program_forms::where('insert_name', $this->insert_name)->get();
            $insertNameColors = insert_name_color::where('insert_name', $this->insert_name)->get();
            foreach ($insertNameColors as $insertNameColor) {
                $insertNameColor->update([
                    'insert_short_name' => $this->insert_short_name,
                ]);
            }
            foreach ($formInsertShortNames as $formInsertShortName) {
                $formInsertShortName->update([
                    'insert_short_name' => $this->insert_short_name,
                ]);
            }
            $this->showAlertMessage('success', 'Update form detail successfully.');
            $this->dispatchBrowserEvent('refresh-page');


        } catch (\Exception $e) {
            $this->showAlertMessage('error', 'Fail update form detail');
        }


    }

    public function render()
    {
        return view('livewire.modals.program.form-detail');
    }

    public static function modalMaxWidth(): string
    {
        return '7xl';
    }


}
