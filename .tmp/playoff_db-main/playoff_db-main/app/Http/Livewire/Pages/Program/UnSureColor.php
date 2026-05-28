<?php

namespace App\Http\Livewire\Pages\Program;

use App\Http\Livewire\Traits\MlbDownloadData;
use App\Http\Livewire\Traits\Notification;
use App\Models\color_name;
use App\Models\Program_subforms;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class UnSureColor extends Component
{
    use  LivewireAlert, Notification;

    public $color, $sortOption = 'front';

    public function mount()
    {
        $this->color = color_name::orderByRaw("FIELD(sure, 0, 1) ASC")->orderBy('color_name', 'asc')->get();
    }

    public $colorItems = [];

    public function change($colorId)
    {
        if (!isset($this->colorItems[$colorId]['change_color_to'])) {
            $this->showAlertMessage('error', 'Please fill in information!');
        } else {
            $colorItem = color_name::find($colorId);
            $colorItem->change_color_to = $this->colorItems[$colorId]['change_color_to'];
            $colorItem->sure = 1;
            $colorItem->save();
            $this->showAlertMessage('success', 'Information is updated');
        }
        $this->dispatchBrowserEvent('open-new-tab');
        $this->dispatchBrowserEvent('refresh-page');
    }


    public function render()
    {
        return view('livewire.pages.program.unsure-color',
            [
                'color' => $this->color,
            ]
        );
    }
}
