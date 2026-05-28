<?php

namespace App\Http\Livewire\Pages\Contact;

use App\Http\Livewire\Traits\Notification;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

use App\Models\contact_location;


class ContactList extends Component
{
    use Notification, LivewireAlert;

    public $edit = [
        'permission' => 'admin|auditor',
    ];

    public $locations;

    public function mount()
    {
        $this->locations = contact_location::with('departments.employees')->get();

    }

    public function render()
    {
        return view('livewire.pages.contact.contact-list', [
            'locations' => $this->locations
        ]);
    }
}
