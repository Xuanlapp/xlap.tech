<?php

namespace App\Http\Livewire\Modals\Program;

use App\Http\Livewire\Traits\Notification;
use App\Models\Nba_team;
use App\Models\Programs;
use App\Models\Program_subforms;
use App\Models\sport_customers;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use LivewireUI\Modal\ModalComponent;

class ProgramDetail extends ModalComponent
{
    use Notification, LivewireAlert;

    public $program;
    public $showDropdown = false;
    public $customers = [];
    public $selected_customer = '';
    public $selected_customer_object = '';
    public $searchCustomer = '';

    public $code = '';

    public $sport = '';

    public $year = '';

    public $collection = '';
    protected $rules = [
        'sport_customers' => 'nullable|exists:sport_customers,id',
    ];

    public $sport_customers = '';

    public $auto_build_workflow = '';
    public $outsourced_job = '';
    public $bk_pa_legal = '';
    public $date_name = '';
    public $legal_line = '';
    public $licensed_bb_product = '';
    protected $listeners = [
        'refreshCustomerList',
        'selectedCustomerItem' => 'selectedCustomerItem',
    ];


    public function mount($program)
    {
        $this->program = Programs::findOrFail($program);
        $this->program = Programs::with('sportCustomer')->find($program);
        $this->code = $this->program->code;
        $this->sport = $this->program->sp;
        $this->year = $this->program->year;
        $this->collection = $this->program->collection;
        $this->auto_build_workflow = $this->program->auto_build_workflow;
        $this->outsourced_job = $this->program->outsourced_job;
        $this->bk_pa_legal = $this->program->bk_pa_legal;
        $this->date_name = $this->program->date_name;
        $this->licensed_bb_product = $this->program->licensed_bb_product;
        $this->legal_line = $this->program->legal_line;
//        $this->sport_customers = sport_customers::get();
        $this->sport_customers = $this->program->customer_id;
    }

    public function refreshCustomerList()
    {
        $this->sport_customers = sport_customers::all();
    }

    /**
     * This trigger by emit
     *
     * @param mixed $item
     * @return void
     */
    public function selectedCustomerItem($item)
    {
        if ($item) {
            $this->selected_customer = $item;
            $this->selected_customer_object = sport_customers::where('customer_name', $item)->first();
        } else {
            $this->selected_customer = "";
        }
    }

    public function updateProgramDetail()
    {
        try {
            $this->program->update([
                'code' => $this->code,
                'sp' => $this->sport,
                'year' => $this->year,
                'collection' => $this->collection,
                'auto_build_workflow' => $this->auto_build_workflow,
                'outsourced_job' => $this->outsourced_job,
                'bk_pa_legal' => $this->bk_pa_legal,
                'date_name' => $this->date_name,
                'legal_line' => $this->legal_line,
                'licensed_bb_product' => $this->licensed_bb_product,
                'customer_id' => $this->sport_customers,
            ]);
            $this->showAlertMessage('success', 'The program has been updated successfully!');
            $this->closeModal();
            $this->dispatchBrowserEvent('refresh-page');
        } catch (\Exception $e) {
            $this->showAlertMessage('error', 'An error occurred while updating the program: ');
        }
    }

    public function toggleDropdown()
    {
        $this->showDropdown = !$this->showDropdown;
        if ($this->showDropdown) {
            $this->getCustomers();
        }
    }

    public function getCustomers()
    {
        $this->customers = sport_customers::where('customer_id', $this->program->customer_id)
            ->where('name', 'like', '%' . $this->searchCustomer . '%')
            ->get();
    }

    public function selectCustomer($customerId)
    {
        $this->program->customer_id = $customerId;
        $this->program->save();
        $this->showDropdown = false;
    }


    public function addNewCustomer()
    {
        $newCustomer = sport_customers::create(['customer_name' => $this->newCustomerName]);
        $this->program->customer_id = $newCustomer->id;
        $this->program->save();
        $this->showModal = false;
    }

    public function render()
    {
        $customer_name = sport_customers::query();

        // Tìm kiếm theo tên khách hàng
        if (!empty($this->searchCustomer)) {
            $customer_name = $customer_name->where('customer_name', 'like', '%' . $this->searchCustomer . '%');
        }

        $customer_name = $customer_name->get();
//        $customer_name = sport_customers::all();

        return view('livewire.modals.program.program-detail', [
            'sport_customers' => $this->sport_customers,
            'customer_name' => $customer_name,
        ]);
    }

    public static function modalMaxWidth(): string
    {
        return '5xl';
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
