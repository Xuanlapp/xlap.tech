<?php

namespace App\Http\Livewire\Pages\Dashboard;

use Illuminate\Support\Facades\Route;
use Livewire\Component;
use App\Http\Livewire\Traits\Notification;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use App\Http\Livewire\Traits\MenuNavArray;

class Dashboard extends Component
{
    use WithPagination, LivewireAlert, Notification, WithFileUploads, MenuNavArray;

    public $list = '';

    public function render()
    {
        foreach ($this->getMenuNavArray() as $category => $routes) {
            if (array_key_exists(explode('.', Route::currentRouteName())[0], $routes)) {
                $this->list = $category;
                break;
            }
        }
        return view('livewire.pages.dashboard.dashboard', [
            'links' => $this->getMenuNavArray(),
            'list' => $this->list,
        ]);
    }
}
