<?php

namespace App\Http\Livewire\Modals\Customer;

use App\Http\Livewire\Traits\Notification;
use Illuminate\Support\Facades\Mail;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use LivewireUI\Modal\ModalComponent;
use App\Models\sport_customers;

class AddCustomer extends ModalComponent
{
    use WithPagination, LivewireAlert, Notification, WithFileUploads;

    public $customer_name;

    protected $rules = [
        'customer_name' => 'required|string|max:255',
    ];

    public function save()
    {
        $this->validate();
//        dd($this->customer_name);

        sport_customers::create([
            'customer_name' => $this->customer_name,
        ]);
        $this->emit('refreshCustomerList'); // Emit sự kiện để refresh danh sách khách hàng
        $this->closeModal(); // Đóng modal
        $this->showAlertMessage('success', 'Add Customer Name success!'); // Thông báo thành công

    }

    public function render()
    {
        return view('livewire.modals.customer.add-customer-name');
    }
}

