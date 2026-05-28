<?php

namespace App\Http\Livewire\Components;

use Livewire\Component;

class SubNavbar extends Component
{

    public $links = [
        'nba' => [
            'nba-new-player' => [
                'name' => '🆕 New',
                'permission' => 'admin|viewer|auditor'
            ],
            'on-hold' => [
                'name' => '❌ On Hold',
                'permission' => 'admin|viewer|auditor'
            ],
            'no-nba-id' => [
                'name' => '🆔 No NBA ID',
                'permission' => 'admin|auditor|viewer'
            ],
            'approved' => [
                'name' => '✅ Approved',
                'permission' => 'admin|viewer|auditor'
            ],
        ],
        'mlb' => [
            'new-player' => [
                'name' => '🆕 New',
                'permission' => 'admin'
            ],
            'on-hold' => [
                'name' => '❌ On Hold',
                'permission' => 'admin|auditor'
            ],
            'approved' => [
                'name' => '✅ Approved',
                'permission' => 'admin|auditor|viewer'
            ],
        ],
        'wnba' => [
            'new-player' => [
                'name' => '🆕 New',
                'permission' => 'admin|viewer|auditor'
            ],
            'approved' => [
                'name' => '✅ Approved',
                'permission' => 'admin|viewer|auditor'
            ],
        ],
        'program' => [
            'new-player' => [
                'name' => '🆕 New',
                'permission' => 'admin|viewer|auditor'
            ],
            'approved' => [
                'name' => '✅ Approved',
                'permission' => 'admin|viewer|auditor'
            ],
        ],

    ];

    public function render()
    {
        return view('livewire.components.sub-navbar');
    }
}
