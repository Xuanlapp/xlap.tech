@props(['align' => 'right', 'width' => '48', 'contentClasses' => 'py-1 bg-white', 'dropdownClasses' => ''])

@php
switch ($align) {
    case 'left':
        $alignmentClasses = 'origin-top-left left-0';
        break;
    case 'top':
        $alignmentClasses = 'origin-top';
        break;
    case 'none':
    case 'false':
        $alignmentClasses = '';
        break;
    case 'right':
    default:
        $alignmentClasses = 'origin-top-right right-0';
        break;
}

switch ($width) {
    case '48':
        $width = 'w-64';
        break;
}
@endphp

<div class="relative" 
    x-data="{ 
        open: false,
        init() {
            this.$watch('open', value => {
                if (value) {
                    this.$nextTick(() => {
                        const dropdown = this.$refs.dropdown;
                        const trigger = this.$refs.trigger;
                        const rect = trigger.getBoundingClientRect();
                        
                        // 檢查是否會超出視窗底部
                        const dropdownHeight = dropdown.offsetHeight;
                        const spaceBelow = window.innerHeight - rect.bottom;
                        
                        if (spaceBelow < dropdownHeight && rect.top > dropdownHeight) {
                            // 如果下方空間不足且上方空間足夠，則顯示在上方
                            dropdown.style.bottom = `${window.innerHeight - rect.top}px`;
                            dropdown.style.top = 'auto';
                        } else {
                            // 否則顯示在下方
                            dropdown.style.top = `${rect.bottom}px`;
                            dropdown.style.bottom = 'auto';
                        }
                        
                        dropdown.style.right = `${window.innerWidth - rect.right}px`;
                    })
                }
            })
        }
    }" 
    @click.away="open = false" 
    @close.stop="open = false">
    
    <div @click="open = ! open" x-ref="trigger">
        {{ $trigger }}
    </div>

    <div x-show="open"
        x-ref="dropdown"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="fixed z-[99999] {{ $width }} rounded-md shadow-lg {{ $dropdownClasses }}"
        style="display: none;"
        @click="open = false">
        <div class="rounded-md ring-1 ring-black ring-opacity-5 {{ $contentClasses }}">
            {{ $content }}
        </div>
    </div>
</div>
