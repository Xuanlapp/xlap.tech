<?php

namespace App\Http\Livewire\Modals\Mlb;

use LivewireUI\Modal\ModalComponent;

class EmailReport extends ModalComponent
{
    public $job;
    public $comment;

    public function render()
    {
        return view('livewire.modals.mlb.email-report');
    }

    public function submit()
    {
        $data['job'] = $this->job;
        $data['comment'] = $this->comment;
        $this->closeModal();
        $this->emit('submit', $data);
    }
}
