<?php

namespace App\Actions\Program;


use App\Models\Program_forms;
use App\Models\Program_subforms;
use App\Models\Programs;
use Livewire\Component;

class ProgramCard extends Component
{
    public $programId, $totalForms, $totalInserts, $Programs, $program;

    public function mount($program, $totalForms = 0, $totalInserts = 0)
    {
        dd("lap nè");
        $this->totalForms = Program_forms::where('program_id', $this->programId)->count();
        $this->totalInserts = Program_subforms::where('program_id', $this->programId)->count();
        $this->Programs = Programs::where('id', $this->programId)->first();

    }

    public function render()
    {
        return view('components.program-card', [
            //            'program' => $this->Programs
        ]);
    }
}
