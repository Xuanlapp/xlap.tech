<div {{ $attributes->merge(["class" => "flex flex-col"])}}>
    <div class="text-sm text-gray-400">{{ $description }}</div>
    <div class="{{ $attributes->get('item-class') ?? 'text-gray-600' }} text-lg ">{{ $item }}</div>
    {{ $slot }}
</div>