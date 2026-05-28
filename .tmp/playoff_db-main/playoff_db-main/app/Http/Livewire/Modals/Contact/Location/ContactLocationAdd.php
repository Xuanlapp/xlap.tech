<?php

namespace App\Http\Livewire\Modals\Contact\Location;

use App\Http\Livewire\Traits\Notification;
use App\Models\contact_location;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use LivewireUI\Modal\ModalComponent;

class ContactLocationAdd extends ModalComponent
{
    use WithPagination, LivewireAlert, Notification, WithFileUploads;

    public $location_name, $address;

    protected $rules = [
        'location_name' => 'required|string|max:255',
        'address' => 'required|string|max:255',
    ];

    public function save()
    {
        $this->validate();
        contact_location::create([
            'location_name' => $this->location_name,
            'address' => $this->address,
        ]);
        $this->dispatchBrowserEvent('refresh-page');
        $this->closeModal(); // Đóng modal
        $this->showAlertMessage('success', 'Add Contact Location success!'); // Thông báo thành công

    }

    public function render()
    {
        return view('livewire.modals.contact.location.contact-location-add');
    }
}

