<div
    x-data="{
        visible: false,
        type: 'success',
        title: '',
        message: '',
        timeout: null,
        show(event) {
            this.type = event.detail.type || 'success';
            this.title = event.detail.title || '';
            this.message = event.detail.message || '';
            this.visible = true;

            clearTimeout(this.timeout);
            this.timeout = setTimeout(() => {
                this.visible = false;
            }, 3000);
        },
    }"
    x-on:toast.window="show($event)"
    class="pointer-events-none fixed right-4 top-4 z-[80] w-[calc(100%-2rem)] max-w-sm sm:right-6 sm:top-6"
>
    <div
        x-show="visible"
        x-transition:enter="transform ease-out duration-200 transition"
        x-transition:enter-start="translate-y-2 opacity-0 sm:translate-x-2 sm:translate-y-0"
        x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="pointer-events-auto overflow-hidden rounded-lg border bg-white shadow-lg ring-1 ring-black/5"
        x-bind:class="type === 'error' ? 'border-red-200' : 'border-emerald-200'"
        style="display: none;"
    >
        <div class="p-4">
            <div class="flex items-start gap-3">
                <div class="shrink-0">
                    <div
                        class="flex h-6 w-6 items-center justify-center rounded-full"
                        x-bind:class="type === 'error' ? 'text-red-600' : 'text-emerald-600'"
                    >
                        <svg x-show="type !== 'error'" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.86-9.8a.75.75 0 0 0-1.22-.88l-3.48 4.84-1.87-1.87a.75.75 0 1 0-1.06 1.06l2.5 2.5a.75.75 0 0 0 1.14-.1l4-5.55Z" clip-rule="evenodd" />
                        </svg>

                        <svg x-show="type === 'error'" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" style="display: none;">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16ZM8.28 7.22a.75.75 0 0 0-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 1 0 1.06 1.06L10 11.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L11.06 10l1.72-1.72a.75.75 0 0 0-1.06-1.06L10 8.94 8.28 7.22Z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>

                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-gray-900" x-text="title"></p>
                    <p class="mt-1 text-sm text-gray-600" x-show="message" x-text="message"></p>
                </div>

                <button
                    type="button"
                    x-on:click="visible = false"
                    class="shrink-0 rounded-md text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-300"
                    aria-label="Close notification"
                >
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>
