<div class="{{ $attributes->get('class', 'col-span-6') }}">
    <x-input class="block mt-1 w-full" :type="$type" :wire:model.defer="$model" {{ $attributes }} />
    @error($model)
    <span class="error text-red-400">{{ $message }}</span>
    @enderror
</div>