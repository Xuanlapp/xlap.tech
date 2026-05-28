<?php

namespace App\Http\Livewire\Modals\Contact\Employees;

use App\Http\Livewire\Traits\Notification;
use App\Models\contact_employees;
use App\Models\contact_department;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use LivewireUI\Modal\ModalComponent;

class ContactEmployeesDetail extends ModalComponent
{
    use WithPagination, LivewireAlert, Notification, WithFileUploads;

    public $employees_id, $employees, $name, $email, $phone, $position, $department_id, $departments, $profile_image, $change_image;

    public function mount()
    {
        $this->employees = contact_employees::with('department')->find($this->employees_id);
        $this->name = $this->employees->name;
        $this->email = $this->employees->email;
        $this->phone = $this->employees->phone;
        $this->position = $this->employees->position;
        $this->department_id = $this->employees->department_id;
        $this->profile_image = $this->employees->profile_image;

        $this->departments = contact_department::all();
    }

    public function rules()
    {
        return [
            'name' => 'required',
            'email' => 'required|email',
            'phone' => 'required',
            'position' => 'required',
            'department_id' => 'required',
            'profile_image' => 'nullable|image|max:1024',
            'change_image' => 'nullable|image|max:1024',


        ];
    }

    public function updateContactEmployees()
    {
        $this->validate();

        try {
            if ($this->profile_image && $this->profile_image instanceof \Livewire\TemporaryUploadedFile) {
                $imagePath = $this->profile_image->store('profiles', 'public');  // Store the uploaded image
            } else {
                // If no new image is uploaded, retain the old image or set it to null if needed
                $imagePath = $this->employees->profile_image ?: null;
            }
            $this->employees->update([
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone,
                'position' => $this->position,
                'department_id' => $this->department_id,
                'profile_image' => $imagePath,
            ]);
            $this->showAlertMessage('success', 'The Contact Employees has been updated successfully!');
            $this->closeModal();
            $this->dispatchBrowserEvent('refresh-page');
        } catch (\Exception $e) {
            $this->showAlertMessage('error', 'An error occurred while updating the Contact Employees: ');
        }
    }

    public function render()
    {
        return view('livewire.modals.contact.employees.contact-employees-detail', []);
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
