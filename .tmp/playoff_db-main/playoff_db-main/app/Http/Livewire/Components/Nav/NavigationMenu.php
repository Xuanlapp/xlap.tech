<?php

namespace App\Http\Livewire\Components\Nav;

use App\Models\contact_location;
use Livewire\Component;
use Illuminate\Support\Facades\Route;
use App\Http\Livewire\Traits\MenuNavArray;

class NavigationMenu extends Component
{
    use MenuNavArray;


    public $list = '';

    public function render()
    {
        foreach ($this->getMenuNavArray() as $category => $routes) {
            if (array_key_exists(explode('.', Route::currentRouteName())[0], $routes)) {
                $this->list = $category;
                break;
            }
        }

        return view('livewire.components.nav.navigation-menu', [
            'links' => $this->getMenuNavArray(),
            'list' => $this->list,
        ]);
    }
}
