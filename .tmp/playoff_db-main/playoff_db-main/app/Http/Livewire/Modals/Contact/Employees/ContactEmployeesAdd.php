<?php

namespace App\Http\Livewire\Modals\Contact\Employees;

use App\Http\Livewire\Traits\Notification;
use App\Models\contact_department;
use App\Models\contact_employees;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use LivewireUI\Modal\ModalComponent;

class ContactEmployeesAdd extends ModalComponent
{
    use WithPagination, LivewireAlert, Notification, WithFileUploads;

    public $employees, $name, $email, $phone, $position, $department_id, $departments, $profile_image;

    protected $rules = [
        'name' => 'required',
        'email' => 'required|email',
        'phone' => 'required',
        'position' => 'required',
        'department_id' => 'required',
        'profile_image' => 'nullable|image|max:1024',

    ];

    public function mount()
    {
        $this->departments = contact_department::all();
        $this->department_id = $this->departments->first()->id;
    }

    public function save()
    {
        $this->validate();
        try {
            if ($this->profile_image) {
                $imagePath = $this->profile_image->store('profiles', 'public'); // Lưu ảnh và lấy đường dẫn
            } else {
                $imagePath = null; // Nếu không có ảnh, lưu giá trị null
            }
            contact_employees::create([
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone,
                'position' => $this->position,
                'department_id' => $this->department_id,
                'profile_image' => $imagePath,
            ]);
            $this->dispatchBrowserEvent('refresh-page');
            $this->closeModal(); // Đóng modal
            $this->showAlertMessage('success', 'Add Contact Employees success!'); // Thông báo thành công
        } catch (\Exception $e) {
            $this->showAlertMessage('error', 'An error occurred while adding the Contact Employees: ');
        }
    }

    public function render()
    {
        return view('livewire.modals.contact.employees.contact-employees-add');
    }
}
