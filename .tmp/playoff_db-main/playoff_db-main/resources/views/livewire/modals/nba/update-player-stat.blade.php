<div>
    <div class="bg-slate-600 text-center h-12 py-5 flex items-center justify-center text-xl text-white">Update NBA Players</div>
    <div wire:loading class="p-5 flex-col items-center justify-center">
        <p class="text-2xl text-center">Updating Players' stat, wait until it completed.
        </p>
        <svg class="w-24" version="1.1" id="L5" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 100 100" enable-background="new 0 0 0 0" xml:space="preserve">
            <circle fill="#000" stroke="none" cx="6" cy="50" r="6">
                <animateTransform
                attributeName="transform"
                dur="1s"
                type="translate"
                values="0 15 ; 0 -15; 0 15"
                repeatCount="indefinite"
                begin="0.1"/>
            </circle>
            <circle fill="#000" stroke="none" cx="30" cy="50" r="6">
                <animateTransform z
                attributeName="transform"
                dur="1s"
                type="translate"
                values="0 10 ; 0 -10; 0 10"
                repeatCount="indefinite"
                begin="0.2"/>
            </circle>
            <circle fill="#000" stroke="none" cx="54" cy="50" r="6">
                <animateTransform
                attributeName="transform"
                dur="1s"
                type="translate"
                values="0 5 ; 0 -5; 0 5"
                repeatCount="indefinite"
                begin="0.3"/>
            </circle>
        </svg>
    </div>
    <div class="px-6 py-4 bg-slate-100 space-y-3">
        {{-- {{$updating}} --}}
        @if($updating)
            <button class="bg-blue-500 inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-700 focus:outline-none focus:border-blue-700 focus:shadow-outline-gray disabled:opacity-25 transition ease-in-out duration-150" wire:click="updatePlayers">Continue Update</button>
            <button class="bg-blue-500 inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-700 focus:outline-none focus:border-blue-700 focus:shadow-outline-gray disabled:opacity-25 transition ease-in-out duration-150" wire:click="resetUpdate">Reset Update</button>
        @else
            <button class="bg-blue-500 inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-700 focus:outline-none focus:border-blue-700 focus:shadow-outline-gray disabled:opacity-25 transition ease-in-out duration-150" wire:click="updatePlayers">Start Update</button>
        @endif
    </div>
</div>
