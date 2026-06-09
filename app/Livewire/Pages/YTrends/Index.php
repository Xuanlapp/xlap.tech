<?php

namespace App\Livewire\Pages\YTrends;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class Index extends Component
{
    /**
     * Render the YTrends MCP integration page.
     */
    public function render(): View
    {
        return view('livewire.pages.ytrends.index')->layout('layouts.app');
    }
}
