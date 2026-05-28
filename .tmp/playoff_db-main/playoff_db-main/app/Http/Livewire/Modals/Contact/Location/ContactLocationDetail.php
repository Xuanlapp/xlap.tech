<?php

namespace App\Http\Livewire\Modals\Contact\Location;

use App\Http\Livewire\Traits\Notification;
use App\Models\contact_location;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use LivewireUI\Modal\ModalComponent;

class ContactLocationDetail extends ModalComponent
{
    use Notification, LivewireAlert;

    public $location_id, $location, $location_name, $address;

    public function mount()
    {
        $this->location = contact_location::find($this->location_id);
        $this->location_name = $this->location->location_name;
        $this->address = $this->location->address;
    }

    public function rules()
    {
        return [
            'location_name' => 'required',
            'address' => 'required',

        ];
    }

    public function updateContactLocation()
    {
        $this->validate();

        try {
            $this->location->update([
                'location_name' => $this->location_name,
                'address' => $this->address,
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
        return view('livewire.modals.contact.location.contact-location-detail', [

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
