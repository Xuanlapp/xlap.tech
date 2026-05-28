<?php

namespace App\Http\Livewire\Modals\Contact\Departments;

use App\Http\Livewire\Traits\Notification;
use App\Models\contact_department;
use App\Models\contact_location;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use LivewireUI\Modal\ModalComponent;

class ContactDepartmentDetail extends ModalComponent
{
    use Notification, LivewireAlert;

    public $departments_id, $department, $department_name, $location_id, $locations;

    public function mount()
    {
        $this->department = contact_department::find($this->departments_id);
        $this->department_name = $this->department->department_name;
        $this->location_id = $this->department->location_id;
        $this->locations = contact_location::all();
    }

    public function rules()
    {
        return [
            'department_name' => 'required',
            'location_id' => 'required',
        ];
    }

    public function updateContactDepartment()
    {
        $this->validate();

        try {
            $this->department->update([
                'department_name' => $this->department_name,
                'location_id' => $this->location_id,
            ]);
            $this->showAlertMessage('success', 'The Contact Location has been updated successfully!');
            $this->closeModal();
            $this->dispatchBrowserEvent('refresh-page');
        } catch (\Exception $e) {
            $this->showAlertMessage('error', 'An error occurred while updating the Contact Location: ');
        }
    }

    public function render()
    {
        return view('livewire.modals.contact.departments.contact-department-detail', [

        ]);
    }

    public static function modalMaxWidth(): string
    {
        return '4xl';
    }

    /**
     * This is so important, after selected option
     * Select2 still active
     *
     * @return void
     */
    public function hydrate()
    {
        $this->emit('select2');
    }
}
