<?php

namespace App\Http\Livewire\Modals\Program;

use Livewire\Component;
use LivewireUI\Modal\ModalComponent;

class ImageModal extends ModalComponent
{
    public $isOpen = false;
    public $imageSrc = '';
    public $url;

    public function openModal($src)
    {
        $this->imageSrc = $src;
        $this->isOpen = true;
    }


    public function render()
    {
//        dd('here');
        return view('livewire.modals.program.image-modal',
            ['url' => $this->url]);
    }

    public static function modalMaxWidth(): string
    {
        return '7xl';
    }
}
