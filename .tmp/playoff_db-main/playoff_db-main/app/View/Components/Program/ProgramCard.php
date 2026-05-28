<?php

namespace App\View\Components\Program;


use App\Models\Program_forms;
use App\Models\Program_subforms;
use App\Models\Programs;
use Illuminate\View\Component;

class ProgramCard extends Component
{
//    public $programId;
    public $totalForms;
    public $totalInserts;
    public $program;
    public $programId;

    public function __construct($programId)
    {
        $this->programId = $programId;
        $this->program = Programs::where('id', $this->programId)->first();
        $this->totalForms = Program_forms::where('program_id', $programId)->count();
        $this->totalInserts = Program_subforms::where('program_id', $programId)->count();

    }

    public function render()
    {
//        dd($this->programId);
        return view('components.program.program-card', [
            'programs' => $this->program,
            'totalForms' => $this->totalForms,
            'totalInserts' => $this->totalInserts,
        ]);
    }
}
