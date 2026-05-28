<?php

namespace App\Http\Livewire\Pages\Contact;

use App\Http\Livewire\Traits\Notification;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

use App\Models\contact_employees;


class ContactEmployees extends Component
{
    use Notification, LivewireAlert;

    public $edit = [
        'permission' => 'admin|auditor',
    ];

    public $employees;

    public function mount()
    {
        $this->employees = contact_employees::with('department')->get();
    }

    public function deleteemployees($id)
    {
        $employees = contact_employees::find($id);
        if ($employees) {
            $employees->delete();
            $success = true;
        } else {
            $success = false;
        }
        if ($success) {
            $this->showAlertMessage('success', 'Employees deleted successfully.');
            $this->dispatchBrowserEvent('refresh-page');
        } else {
            $this->showAlertMessage('error', 'Employees not found.');
        }
    }

    public function render()
    {
        return view('livewire.pages.contact.contact-employees', [
            'employeess' => $this->employees
        ]);
    }
}
