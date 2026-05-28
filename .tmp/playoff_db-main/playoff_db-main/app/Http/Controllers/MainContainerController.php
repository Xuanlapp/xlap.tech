<?php

namespace App\Http\Controllers;

use App\Models\contact_location;
use Illuminate\Http\Request;

class MainContainerController extends Controller
{
    public $page_title = [
        'exports' => 'Import/Export Execution File',
        'mlb' => 'MLB Database Management',
        'nba' => 'NBA Database Management',
        'program' => 'Program List',
        'wnba' => 'WNBA Database Management',
        'contact' => 'Contact Database Management',
        'location' => 'Location Database Management',
        'departments' => 'Departments Database Management',
        'employees' => 'Employees Database Management',
        'logo' => 'Logo Database Management',
        'dashboard' => 'Dashboard',
    ];
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

        ],
        'location' => [

        ],
        'contact' => [

        ],
        'logo' => [

        ],
        'dashboard' => [
           
        ],

    ];
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($page, $id = null)
    {

        return view('layouts.main_container_layout',
            ['page' => $page, 'iid' => $id, 'page_title' => $this->page_title, 'links' => $this->links]
        );
    }
}
