<div x-data="{ 
        selectNum: @entangle('selectNum'), 
        init() {
            let selectElement = this.$refs.select2;

            // 先清除舊的 Select2，避免重複初始化
            if ($(selectElement).hasClass('select2-hidden-accessible')) {
                $(selectElement).select2('destroy');
            }

            // 初始化 Select2
            $(selectElement).select2({
                placeholder: 'Please Select...',
                allowClear: true
            }).on('change', (event) => {
                this.selectNum = $(event.target).val(); // 讓 Alpine.js 變數更新
                Livewire.emit('updateSelect', this.selectNum); // 讓 Livewire 更新值
            });

            // 監聽 Alpine.js `selectNum` 變更，確保 UI 正確同步
            this.$watch('selectNum', (value) => {
                $(selectElement).val(value).trigger('change');
                $(selectElement).select2({
                    placeholder: 'Please Select...',
                    allowClear: true
                }).on('change', (event) => {
                    this.selectNum = $(event.target).val(); // 讓 Alpine.js 變數更新
                    Livewire.emit('updateSelect', this.selectNum); // 讓 Livewire 更新值
                });
            });
        }
    }">

    <!-- Select2 選單 -->
    <select x-ref="select2" class="block mt-1 w-full select" x-model="selectNum">
        <option value="">Select Team</option>
        <option value="1">1</option>
        <option value="2">2</option>
        <option value="3">3</option>
    </select>


    <div class="bg-slate-600 text-center h-12 py-5 flex items-center justify-center text-xl text-white">{{ ($new)?'Assign':'Change' }} NBA ID for {{ $player->player }}</div>

    <div class="px-6 py-4 bg-slate-100 space-y-3">
        <div class="mt-4">
            <div class="mt-2 space-y-2">
                <x-label class="mt-2" value="{{ ($new)?'Assign':'Change' }} NBA Player ID" />
                <x-input class="block mt-1 w-full" type="text" wire:model="nba_player_id" autofocus />
                @if(!$new)
                <div class="text-red-500 flex justify-center space-x-3">
                    <div class="h-16 w-16">
                        <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"></path>
                        </svg>
                    </div>
                    <div>
                        If you change to a new NBA ID, the old stats of this player will be removed before download new stats, after download stats, player will be approved!
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
    <div class="px-6 py-4 bg-gray-100 text-right">
        <x-secondary-button wire:click="$emit('closeModal')" wire:loading.attr="disabled">
            Cancel
        </x-button>
        <x-button wire:click="changeNbaId()" wire:loading.attr="disabled">
            {{ ($new)?'Assign':'Change' }}
        </x-button>
    </div>
</div>
