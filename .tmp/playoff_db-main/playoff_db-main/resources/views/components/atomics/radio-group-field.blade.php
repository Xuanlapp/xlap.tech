<div class="{{ $attributes->get('class', 'col-span-3') }}">
    <!-- Label -->
    <x-label :value="$label" />

    <!-- Radio Group -->
    <div class="flex items-center">
        @foreach ($options as $option)
            <label class="mr-4">
                <input 
                    type="radio" 
                    name="{{ $name }}" 
                    value="{{ $option['value'] }}" 
                    wire:model.defer="{{ $model }}">
                {{ $option['label'] }}
            </label>
        @endforeach
    </div>

    <!-- Error Message -->
    @error($model)
        <span class="error text-red-400">{{ $message }}</span>
    @enderror
</div>