<div class="{{ $attributes->get('class', 'col-span-6') }}">
    <!-- Label -->
    <div class="flex">
        <x-label class="mt-2" :value="$label"/>
        <x-label wire:dirty :wire:target="$model" class="mt-2 ml-1 text-red-600"
        >*
        </x-label>
    </div>
    <!-- Input -->
    <x-input class="block mt-1 w-full" :type="$type" :wire:model.defer="$model" {{ $attributes }} />

    <!-- Error Message -->
    @error($model)
    <span class="error text-red-400">{{ $message }}</span>
    @enderror
</div>