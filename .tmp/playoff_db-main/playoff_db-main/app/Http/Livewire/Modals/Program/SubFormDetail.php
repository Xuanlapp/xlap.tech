<?php

namespace App\Http\Livewire\Modals\Program;

use App\Http\Livewire\Traits\Notification;
use App\Models\insert_name_color;
use App\Models\Program_forms;
use App\Models\Program_subforms;
use App\Services\Program\ProgramSubFormDetailServices;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\WithPagination;
use LivewireUI\Modal\ModalComponent;

class SubFormDetail extends ModalComponent
{
    use LivewireAlert, Notification, WithPagination;

    public $SubForm, $programId, $insert_name_alt = '', $SubForm_Id, $config = '', $form = '', $insert_name = '', $cards = '', $seq = '', $substrate = '', $foil = '', $autos = '', $pms = '', $prepress_color_front = '', $lam_front = '', $prepress_color_back = '', $lam_back = '', $coating_front = '', $coating_back = '', $panini = '', $leagues = '', $stamped = '', $panini_binder = '', $total_inc_sht = '', $program_id = '', $insert_short_name = '';
    public $minSubFormId, $maxSubFormId;
    protected $programSubFormDetailServices;
    public $checkedItems = [];
    public $fields = [
        'form',
        'config',
        'cards',
        'seq',
        'prepress_color_front',
        'prepress_color_back',
        'lam_front',
        'lam_back',
        'coating_front',
        'coating_back',
        'substrate',
        'foil',
        'autos',
        'pms',
        'panini',
        'leagues',
        'stamped',
        'panini_binder',
        'total_inc_sht',
        'splitdatas',
        'headers'
    ];

    public function mount(ProgramSubFormDetailServices $programSubFormDetailServices)
    {
        $this->programSubFormDetailServices = $programSubFormDetailServices;
        $this->loadSubFormDetails();

    }

    public $isUpdatingColor = false;


    /**
     * Load details of the subform.
     *
     * @return void
     */
    public function loadSubFormDetails()
    {
        $this->SubForm = Program_subforms::findOrFail($this->SubForm_Id);
        $this->config = $this->SubForm->config;
        $this->form = $this->SubForm->form;
        $this->insert_name = $this->SubForm->insert_name;
        $this->cards = $this->SubForm->cards;
        $this->seq = $this->SubForm->seq;
        $this->substrate = $this->SubForm->substrate;
        $this->foil = $this->SubForm->foil;
        $this->autos = $this->SubForm->autos;
        $this->pms = $this->SubForm->pms;
        $this->prepress_color_front = $this->SubForm->prepress_color_front;
        $this->lam_front = $this->SubForm->lam_front;
        $this->prepress_color_back = $this->SubForm->prepress_color_back;
        $this->lam_back = $this->SubForm->lam_back;
        $this->coating_front = $this->SubForm->coating_front;
        $this->coating_back = $this->SubForm->coating_back;
        $this->panini = $this->SubForm->panini;
        $this->leagues = $this->SubForm->leagues;
        $this->stamped = $this->SubForm->stamped;
        $this->panini_binder = $this->SubForm->panini_binder;
        $this->total_inc_sht = $this->SubForm->total_inc_sht;
        $this->program_id = $this->SubForm->program_id;
        $this->insert_short_name = $this->SubForm->insert_short_name;
        $this->programId = $this->SubForm->program_id;
    }

    /**
     * Increment SubForm ID and load next subform details.
     */
    public function incrementSubFormId()
    {
        if ($this->SubForm_Id < $this->maxSubFormId) {
            $this->loadSubFormById($this->SubForm_Id + 1);
        }
    }

    /**
     * Decrement SubForm ID and load previous subform details.
     */
    public function decrementSubFormId()
    {
        if ($this->SubForm_Id > $this->minSubFormId) {
            $this->loadSubFormById($this->SubForm_Id - 1);
        }
    }

    /**
     * Load the subform data by ID.
     *
     * @param int $id
     */
    private function loadSubFormById($id)
    {
        $subForm = Program_subforms::find($id);

        if ($subForm) {
            $this->SubForm_Id = $id;
            $this->loadSubFormDetails();
        } else {
            session()->flash('error', 'SubForm not found.');
        }
    }

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName, [
            'insert_short_name' => 'nullable|string|max:10',
        ]);
    }

    public $headers = [];


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
            //            'insert_name_alt' => 'required|string|max:255',
        ];
    }

    public function updateSubFormDetail()
    {
        try {
            // Cập nhật SubForm
            $this->SubForm->update([
                'config' => $this->config,
                'form' => $this->form,
                'cards' => $this->cards,
                'seq' => $this->seq,
                'substrate' => $this->substrate,
                'foil' => $this->foil,
                'autos' => $this->autos,
                'pms' => $this->pms,
                'prepress_color_front' => $this->prepress_color_front,
                'lam_front' => $this->lam_front,
                'prepress_color_back' => $this->prepress_color_back,
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
                'insert_name' => $this->insert_name,
            ]);

            // Cập nhật insert_name_color và formInsertShortNames
            insert_name_color::where('insert_name',
                $this->insert_name)->update(['insert_short_name' => $this->insert_short_name]);
            Program_forms::where('insert_name',
                $this->insert_name)->update(['insert_short_name' => $this->insert_short_name]);

            $this->showAlertMessage('success', 'Update form detail successfully.');
            $this->dispatchBrowserEvent('refresh-page');
        } catch (\Exception $e) {
            $this->showAlertMessage('error', 'Fail to update form detail');
        }
    }


    // Update checkbox status and logic
    protected $listeners = ['refreshData'];

    public function refreshData($subFormId)
    {
        $this->SubForm_Id = $subFormId;
        $this->loadSubFormDetails(); // Gọi hàm tải dữ liệu của modal
    }

    public function render()
    {

        return view('livewire.modals.program.sub-form-detail', [
            'programId' => $this->SubForm->program_id,
            'SubForm_Id' => $this->SubForm_Id,
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
