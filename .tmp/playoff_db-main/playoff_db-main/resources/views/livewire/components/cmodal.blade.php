<div>
    <x-modal wire:model="isOpen" id='my-modal'>
        @if($isOpen)
            @livewire($form, [
                'iid'=> $iid,
            ])
        @endif
    </x-modal>
</div>