<?php

namespace App\Http\Livewire\Modals\Program;


use LivewireUI\Modal\ModalComponent;
use Illuminate\Support\Facades\Mail;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use App\Http\Livewire\Traits\Notification;
use Livewire\WithFileUploads;

class ChangeJsonColor extends ModalComponent
{
    use LivewireAlert, Notification, WithFileUploads;


    public function render()
    {
        return view('livewire.modals.program.change-json-color');
    }

    public static function modalMaxWidth(): string
    {
        return '7xl';
    }
}
