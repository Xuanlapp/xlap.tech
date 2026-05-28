<?php

namespace App\Http\Livewire\Modals\Program;

use App\Models\Program_forms;
use App\Models\Program_subforms;
use App\Models\Programs;
use LivewireUI\Modal\ModalComponent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class GroupSubForm extends ModalComponent
{
    public $form_insert_name, $program_id, $form, $form_id, $programData = [], $programFormsData = [], $headerForm = [];

    public $sub_forms = [];
    public $GroupSubForm = [];
    public $form_group;

    public function mount()
    {
//        dd($this->form_id);
        $form_insert_name = $this->form_insert_name;
        $form_id = $this->form_id;
        $program_id = $this->program_id;
        $form = $this->form;
        $this->programData = Programs::where('id', $this->program_id)->first();
        $this->programFormsData = Program_forms::where('program_id', $this->program_id)
            ->where('id', $this->form_id)
            ->first();
        // Xử lý để lấy từ đầu tiên trong form_insert_name
        //        $form_insert_name = preg_replace('/\s*\(.*?\)\s*/', ' ', $form_insert_name);
        $currentInsertName = implode(' ', array_slice(explode(' ', trim($form_insert_name)), 0, 2));
        $wordsArray = array_filter(explode(' ', trim($form_insert_name)));

        //        $form_insert_name = $wordsArray[0] ?? '';

        // Lấy thông tin GroupSubForm
        $this->GroupSubForm = Program_subforms::with('program')
            ->where('program_id', $program_id)
            ->whereRaw('SUBSTRING_INDEX(form, " ", 1) = ?', [explode(' ', $form)[0]])
            ->where('insert_name', 'LIKE', '%' . $form_insert_name . '%')
            //            ->whereRaw('SUBSTRING_INDEX(insert_name, " ", 1) = ?', [explode(' ', $form_insert_name)[0]])
            ->first();

        if ($this->GroupSubForm) {
            $this->form_group = $this->GroupSubForm->form_group;
            $this->sub_forms = Program_subforms::where('program_id', $program_id)
                ->where('form_group', $this->form_group)
                ->whereColumn('color_group', 'insert_name_group')
                ->orderBy('id')
                ->get()
                ->groupBy(function ($item) {
                    return $item->insert_name . '|' . $item->prepress_color_front;
                })
                ->map(function ($group, $key) {
                    $keyParts = explode('|', $key);
                    return [
                        'form_group' => $group->first()->form_group,
                        'insert_name' => $keyParts[0],
                        'prepress_color_front' => $keyParts[1],
                        'forms' => $group->pluck('form')->toArray(),
                        'color_group' => $group->first()->color_group,
                        'id' => $group->first()->id,
                        'form_id' => $this->form_id,
                        'prepress_color_back' => $group->first()->prepress_color_back,
                    ];
                })
                ->values()
                ->toArray();
        } else {
            $this->sub_forms = [];
        }
        $this->GroupForm();
    }

    public function GroupForm()
    {
        $formForms = $this->programFormsData->form;
        $insert_name_Forms = $this->programFormsData->insert_name;


        $subform = Program_subforms::where('form', $formForms)
            ->whereRaw("form NOT LIKE '%-%'")
            ->where('program_id', $this->program_id)
            ->where('insert_name', $insert_name_Forms)
            ->orderBy('id')
            ->get();
        $this->color_group = $subform->first()->color_group;
//        dd($colorGroup);
//        dd($this->programData = Programs::where('id', $this->program_id)->first());
//        $this->programData
        $this->headerForm = Program_subforms::where('color_group', $this->color_group)
            ->whereColumn('color_group', 'insert_name_group')
            ->orderBy('id')
            ->get()
            ->groupBy(function ($item) {
                //                $item->prepress_color_front = $item->prepress_color_front ?? '';
                $this->prepress_color_back = $item->prepress_color_back = $item->prepress_color_back ?? '';
                return $item->insert_name . '|' . $item->prepress_color_front;
            })->map(function ($group, $key) {
                $keyParts = explode('|', $key);
                $this->program_id = $group->first()->program_id;
                $this->form_insert_name = $group->first()->insert_name;
                $this->form = $group->first()->form;
                $code = Programs::where('id', $this->program_id)->first()->code;
                return [
                    'form_group' => $group->first()->form_group,
                    'insert_name' => $keyParts[0],
                    'prepress_color_front' => $keyParts[1],
                    'forms' => $group->pluck('form')->toArray(),
                    'code' => $code,
                    'prepress_color_back' => $group->first()->prepress_color_back,
                ];
            })
            ->values()
            ->toArray();
    }

    public function render()
    {
        return view('livewire.modals.program.group-sub-form', [
            'programData' => $this->programData,
            'programFormsData' => $this->programFormsData,
            'sub_forms' => $this->sub_forms,
            'formgroup' => $this->headerForm,
        ]);
    }


    public static function modalMaxWidth(): string
    {
        return '7xl';
    }
}
