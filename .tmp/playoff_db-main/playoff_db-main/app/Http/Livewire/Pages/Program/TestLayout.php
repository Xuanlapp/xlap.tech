<?php

namespace App\Http\Livewire\Pages\Program;

use App\Http\Livewire\Traits\Notification;
use App\Models\color_name;
use App\Models\Program_subforms;
use Illuminate\Support\Facades\File;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class TestLayout extends Component
{
    use  LivewireAlert, Notification;


    public function mount()
    {
        $this->color = color_name::orderByRaw("FIELD(sure, 0, 1) ASC")->orderBy('color_name', 'asc')->get();
    }


    public function render()
    {
        return view('livewire.pages.program.test-layout',

        );
    }

    public function createFolder()
    {
        $folderPath = public_path('kdrive/my-folder');

        if (!File::exists($folderPath)) {
            File::makeDirectory($folderPath, 0777, true, true);
            $this->showAlertMessage('success', 'Folder created successfully!');
        }
        $this->showAlertMessage('warning', 'Folder already exists!');
    }
}
