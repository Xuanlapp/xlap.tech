<?php

namespace App\Http\Livewire\Traits;

use App\Models\MlbPlayer;

trait MenuNavArray
{
    public function getMenuNavArray()
    {
        return [
            'dashboard' => [
                'dashboard' => [
                    'name' => 'Dashboard',
                    'router' => 'dashboard',
                    'permission' => 'admin|viewer|auditor',
                    'img' => 'https://media.istockphoto.com/id/1555616375/vector/basketball-club-logo-basketball-club-emblem-design-template.jpg?s=612x612&w=0&k=20&c=0gCiSZRbVE5KBPpYoV9TDAgSeMaFTqwumL4XA3XRxG4=',

                ],
            ],
            'stats' => [
                'mlb' => [
                    'name' => 'MLB',
                    'router' => 'new-player',
                    'permission' => 'admin|viewer|auditor',
                    'img' => 'https://www.mlbstatic.com/team-logos/league-on-dark/1.svg',
                ],
                'nba' => [
                    'name' => 'NBA',
                    'router' => 'nba-new-player',
                    'permission' => 'admin|viewer|auditor',
                    'img' => 'https://cdn.nba.com/logos/leagues/logo-nba.svg',
                ],
                'wnba' => [
                    'name' => 'WNBA',
                    'router' => 'new-player',
                    'permission' => 'admin|viewer|auditor',
                    'img' => 'https://cdn.wnba.com/static/next/images/logos/wnba-secondary-logo.svg',
                ],
            ],
            'program' => [
                'program' => [
                    'router' => 'program-list',
                    'name' => 'Program',
                    'permission' => 'admin|viewer|auditor',
                    'img' => 'https://www.mlbstatic.com/team-logos/league-on-dark/1.svg',
                ],
            ],
            'contact' => [
                'employees' => [
                    'router' => '',
                    'name' => 'Employees',
                    'permission' => 'admin|viewer|auditor',
                    'img' => 'https://t4.ftcdn.net/jpg/05/48/53/43/360_F_548534349_xTgLJIHPva4ln5xwa0wXNCPCYaA7kfrr.jpg',
                ],
                'departments' => [
                    'router' => '',
                    'name' => 'Departments',
                    'permission' => 'admin|viewer|auditor',
                    'img' => 'https://png.pngtree.com/png-clipart/20230915/original/pngtree-jurors-icon-symbol-simple-design-score-office-symbol-vector-png-image_12182592.png',
                ],
                'location' => [
                    'router' => '',
                    'name' => 'Location',
                    'permission' => 'admin|viewer|auditor',
                    'img' => 'https://png.pngtree.com/png-clipart/20210824/ourmid/pngtree-location-icon-png-image_3819857.jpg',
                ],
            ],
            'LOGO' => [
                'logo' => [
                    'name' => 'BASKETBALL LOGOS',
                    'router' => 'logo-basketball',
                    'permission' => 'admin|viewer|auditor',
                    'img' => 'https://media.istockphoto.com/id/1555616375/vector/basketball-club-logo-basketball-club-emblem-design-template.jpg?s=612x612&w=0&k=20&c=0gCiSZRbVE5KBPpYoV9TDAgSeMaFTqwumL4XA3XRxG4=',

                ],
            ],
            'admin' => [
                'admin.users.index' => [
                    'name' => 'Users',
                    'router' => '',
                    'permission' => 'admin',
                    'img' => '',
                ],
                'admin.roles.index' => [
                    'name' => 'Roles',
                    'router' => '',
                    'permission' => 'admin',
                    'img' => '',
                ],
                'admin.permissions.index' => [
                    'name' => 'Permissions',
                    'router' => '',
                    'permission' => 'admin',
                    'img' => '',
                ],
            ],

        ];
    }

    // groupProgram 函数已移动到 PressColorArray trait 中，并重命名为 pressColorGroupArray
}
