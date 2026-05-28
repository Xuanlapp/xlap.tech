<?php

namespace App\Http\Livewire\Pages\Contact;

use App\Http\Livewire\Traits\Notification;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\contact_department;


class ContactDepartment extends Component
{
    use Notification, LivewireAlert;

    public $edit = [
        'permission' => 'admin|auditor',
    ];

    public $departments;

    public function deletedepartments($id)
    {
        $department = contact_department::find($id);
        if ($department) {
            $department->delete();
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

    public function mount()
    {
        $this->departments = contact_department::with('location')->get();
    }

    public function render()
    {
        return view('livewire.pages.contact.contact-departments', [
            'departments' => $this->departments
        ]);
    }
}
