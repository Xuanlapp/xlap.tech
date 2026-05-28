<?php

namespace App\Http\Livewire\Pages\Contact;

use App\Http\Livewire\Traits\Notification;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

use App\Models\contact_location;


class ContactLocation extends Component
{
    use Notification, LivewireAlert;

    public $edit = [
        'permission' => 'admin|auditor',
    ];

    public $locations;

    public function mount()
    {
        $this->locations = contact_location::all();
    }

    public function deletelocation($id)
    {
        $location = contact_location::find($id);
        if ($location) {
            $location->delete();
            $success = true;
        } else {
            $success = false;
        }
        if ($success) {
            $this->showAlertMessage('success', 'Program deleted successfully.');
            $this->dispatchBrowserEvent('refresh-page');
        } else {
            $this->showAlertMessage('error', 'Program not found.');
        }
    }

    public function render()
    {
        return view('livewire.pages.contact.contact-location', [
            'locations' => $this->locations
        ]);
    }
}
