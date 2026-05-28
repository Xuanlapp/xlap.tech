<?php

namespace App\Http\Livewire\Pages\Program;

use App\Models\Program_subforms;
use Livewire\Component;
use App\Models\ProgramSubforms;

class PageNoForm extends Component
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
