<div>
    <div class="bg-slate-600 text-center h-12 py-5 flex items-center justify-center text-xl text-white">Confirm Dialog</div>
    <div class="px-6 py-4 bg-slate-100 space-y-3">
        <div class="text-lg text-red-">{{$message}}</div>
    </div>
    <div class="px-6 py-4 bg-gray-100 text-right">
        <x-secondary-button wire:click="$emit('closeModal')" wire:loading.attr="disabled">
            Cancel
        </x-button>
        <x-atomics.button-danger wire:click="confirm()" wire:loading.attr="disabled">
            Confirm
        </x-atomics.button-danger>
    </div>
</div>
