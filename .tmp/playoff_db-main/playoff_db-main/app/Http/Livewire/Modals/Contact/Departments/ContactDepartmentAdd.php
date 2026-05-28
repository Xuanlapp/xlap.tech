<?php

namespace App\Http\Livewire\Modals\Contact\Departments;

use App\Http\Livewire\Traits\Notification;
use App\Models\contact_department;
use App\Models\contact_location;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use LivewireUI\Modal\ModalComponent;

class ContactDepartmentAdd extends ModalComponent
{
    use WithPagination, LivewireAlert, Notification, WithFileUploads;

    public $department_name, $address, $location_id, $locations;

    protected $rules = [
        'department_name' => 'required|string|max:255',
        'location_id' => 'required',
    ];

    public function mount()
    {
        $this->locations = contact_location::all();
        $this->location_id = $this->locations->first()->id;
    }

    public function save()
    {
        $this->validate();
        contact_department::create([
            'department_name' => $this->department_name,
            'location_id' => $this->location_id,
        ]);
        $this->dispatchBrowserEvent('refresh-page');
        $this->closeModal(); // Đóng modal
        $this->showAlertMessage('success', 'Add Contact Location success!'); // Thông báo thành công

    }

    public function render()
    {
        return view('livewire.modals.contact.departments.contact-department-add');
    }
}

