<?php

namespace App\Http\Livewire\Pages\Program;

use App\Http\Livewire\Traits\Notification;
use App\Models\Program_forms;
use App\Models\Program_subforms;
use App\Models\Programs;
use App\Http\Livewire\Pages\Program\ProgramsListExport;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class ColorForm extends Component
{
    use Notification, LivewireAlert;

    public $color_group, $groupColor = [], $prepress_color_back, $form_group = [], $program_id, $form, $form_id, $colorGroupIndex, $programFormsData, $programData;
    public $sub_forms = [];
    protected $queryString = ['form_id'];
    public $GroupSubForm = [];


    //    public $GroupArrayColor = [];

    public function mount()
    {
        $this->GroupForm();
        $this->ColorGroup();
        $this->ArrayColorGroup();
        $this->programData = Programs::where('id', $this->program_id)->first();
        $this->programFormsData = Program_forms::where('program_id', $this->program_id)
            ->where('id', $this->form_id)
            ->first();


    }

    public function nextColorGroup()
    {
        if ($this->colorGroupIndex < count($this->sub_forms) - 1) {
            $this->color_group = $this->sub_forms[$this->colorGroupIndex + 1];
            $this->ColorGroup(); // Cập nhật dữ liệu nhóm màu mới
        }
    }

    public function previousColorGroup()
    {
        if ($this->colorGroupIndex > 0) {
            $this->color_group = $this->sub_forms[$this->colorGroupIndex - 1];
            $this->ColorGroup(); // Cập nhật dữ liệu nhóm màu mới
        }
    }

    public function ColorGroup()
    {
        $this->GroupForm();
        $this->groupColor = Program_subforms::where('color_group', $this->color_group)
            ->orderBy('id')
            ->get()
            ->groupBy('insert_name')  // Group by insert_name
            ->map(function ($group) {
                // For each group, concatenate the form values with a comma
                //                dd($group);
                return [
                    'insert_name' => $group->first()->insert_name,
                    'forms' => $group->pluck('form')->implode(', '),
                    'foil' => $group->first()->foil,
                    'pms' => $group->first()->pms,
                    'prepress_color_front' => $group->first()->prepress_color_front,
                    'prepress_color_back' => $group->first()->prepress_color_back,
                    'substrate' => $group->first()->substrate,

                ];
            })
            ->filter(function ($group) {
                // Exclude the group if its insert_name is already in the form_group
                return !in_array($group['insert_name'], array_column($this->form_group, 'insert_name'));
            })
            ->values()
            ->toArray();
    }


    public function GroupForm()
    {


        $this->form_group = Program_subforms::where('color_group', $this->color_group)
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
                    'prepress_color_front_json' => $group->first()->prepress_color_front_json,
                    'prepress_color_back_json' => $group->first()->prepress_color_back_json,

                ];
            })
            ->values()
            ->toArray();
    }


    public function ArrayColorGroup()
    {
        $form_insert_name = $this->form_insert_name;
        $program_id = $this->program_id;
        $form = $this->form;
        $form_insert_name = preg_replace('/\s*\(.*?\)\s*/', ' ', $form_insert_name);
        $wordsArray = array_filter(explode(' ', trim($form_insert_name)));
        $form_insert_name = $wordsArray[0] ?? '';
        // Lấy thông tin GroupSubForm
        $this->GroupSubForm = Program_subforms::where('program_id', $program_id)
            ->whereRaw('SUBSTRING_INDEX(form, " ", 1) = ?', [explode(' ', $form)[0]])
            ->whereRaw('SUBSTRING_INDEX(insert_name, " ", 1) = ?', [explode(' ', $form_insert_name)[0]])
            ->first();
        if ($this->GroupSubForm) {
            $this->form_groups = $this->GroupSubForm->form_group;
            //            dd($this->form_groups);
            $this->sub_forms = Program_subforms::where('program_id', $program_id)
                ->where('form_group', $this->form_groups)
                ->whereColumn('color_group', 'insert_name_group')
                ->orderBy('id')
                ->get()
                ->pluck('color_group')
                ->unique()
                ->values()
                ->toArray();
            //            dd($this->sub_forms);
        } else {
            $this->sub_forms = [];
        }

        $this->colorGroupIndex = array_search($this->color_group, $this->sub_forms);
    }

    public function render()
    {

        //        dd($this->form_group);
        return view('livewire.pages.program.color-form', [
            'programData' => $this->programData,
            'programFormsData' => $this->programFormsData,
            'groupform' => $this->groupColor,
            'formgroup' => $this->form_group,
            'colorGroupIndex' => $this->colorGroupIndex,
        ]);
    }
}
