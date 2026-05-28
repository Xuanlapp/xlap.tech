<?php

namespace App\View\Components\Program;


use App\Models\Program_forms;
use App\Models\Program_subforms;
use App\Models\Programs;
use Illuminate\View\Component;

class ProgramSubFormNav extends Component
{
    public $form;


    public function __construct($form)
    {
//        dd($form);
        $this->form = $form;
    }

    public function render()
    {
//        dd($this->SubFormID);
        return view('components.program.program-sub-form-nav', [
            'form' => $this->form
        ]);
    }
}
