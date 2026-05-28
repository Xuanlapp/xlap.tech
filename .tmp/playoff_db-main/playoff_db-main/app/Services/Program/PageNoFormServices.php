<?php

namespace App\Services\Program;


use App\Models\Program_subforms;
use App\Models\ProgramSubforms;
use Livewire\Component;

class PageNoFormServices extends Component
{
    public $nullMainPrograms;

    public function mount()
    {
        $this->nullMainPrograms = Program_subforms::whereNull('main_form_id')->get();
    }

    public function render()
    {
        return view('livewire.pages.program.change-father', [
            'nullMainPrograms' => $this->nullMainPrograms,
        ]);
    }
}
