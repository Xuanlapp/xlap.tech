<?php

namespace App\Http\Livewire\Traits;

trait Notification
{
    public function showAlertMessage($type, $message)
    {
        $this->alert($type, $message, [
            'position' => 'top-end',
            'timer' => 5000,
            'toast' => true,
            'text' => '',
            'confirmButtonText' => 'Ok',
            'cancelButtonText' => 'Cancel',
            'showCancelButton' => false,
            'showConfirmButton' => false,
        ]);
    }
}
