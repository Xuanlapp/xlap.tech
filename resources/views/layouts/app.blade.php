<!DOCTYPE html>
@php($initialThemeMode = auth()->check() ? (auth()->user()->theme_mode ?? 'light') : 'light')
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ $initialThemeMode === 'dark' ? 'theme-dark dark' : 'theme-light' }}" data-theme-mode="{{ $initialThemeMode }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Offorest') }}</title>
        <link rel="icon" type="image/jpeg" href="{{ asset('images/offorest-logo.jpg') }}">

        <script>
            (() => {
                const saved = window.localStorage.getItem('offorest.theme');
                const mode = saved === 'dark' || saved === 'light' ? saved : @js($initialThemeMode === 'dark' ? 'dark' : 'light');
                document.documentElement.dataset.themeMode = mode;
                document.documentElement.classList.toggle('theme-dark', mode === 'dark');
                document.documentElement.classList.toggle('theme-light', mode !== 'dark');
                document.documentElement.classList.toggle('dark', mode === 'dark');
            })();
        </script>

        <!-- Fonts: keep first paint independent from the remote stylesheet. -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link rel="dns-prefetch" href="//fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
        <noscript><link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet"></noscript>

        <!-- Scripts -->
        @livewireStyles
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('scripts')
    </head>
    <body class="font-sans antialiased">
        <div class="app-shell min-h-screen bg-gray-200 dark:bg-[#111317]">
            <livewire:layout.navigation />

            <div>
                <!-- Page Heading -->
                @if (isset($header))
                    <header class="bg-white shadow dark:bg-[#181b21]">
                        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endif

                <!-- Page Content -->
                <main class="app-main">
                    {{ $slot }}
                </main>
            </div>

            <div
                x-data="{ showBackToTop: window.scrollY > 300 }"
                @scroll.window="showBackToTop = window.scrollY > 300"
                class="fixed bottom-5 right-5 z-50"
            >
                <button
                    type="button"
                    x-show="showBackToTop"
                    x-cloak
                    x-transition.opacity.duration.200ms
                    x-on:click="window.scrollTo({ top: 0, behavior: 'smooth' })"
                    class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 shadow-lg shadow-slate-400/30 transition-colors dark:border-white/10 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800"
                    aria-label="Len dau trang"
                    title="Len dau trang"
                >
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 19V5" />
                        <path d="m5 12 7-7 7 7" />
                    </svg>
                </button>
            </div>

            <div
                x-data="{ open: false, loading: true, src: null, title: '', show(event) { this.src = event.detail.src || event.detail.original || null; this.title = event.detail.title || 'Image preview'; this.loading = true; this.open = true; }, close() { this.open = false; } }"
                x-on:review-image.window="show($event)"
                x-on:review-image-ready.window="close()"
                x-on:keydown.escape.window="close()"
                x-show="open"
                x-cloak
                role="dialog"
                aria-modal="true"
                aria-label="Image preview"
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm"
            >
                <div class="relative flex h-[min(85vh,900px)] w-full max-w-5xl items-center justify-center rounded-2xl bg-white p-4 shadow-2xl dark:bg-slate-950">
                    <button type="button" x-on:click="close()" aria-label="Close image preview" class="absolute right-4 top-4 z-10 rounded-full bg-white p-2 text-slate-800 shadow dark:bg-slate-800 dark:text-white">&times;</button>
                    <span x-show="loading" role="status" class="absolute text-sm text-slate-600 dark:text-slate-300">Loading image...</span>
                    <img x-show="!loading" x-bind:src="open ? src : null" x-bind:alt="title" x-on:load="loading = false" x-on:error="loading = false" class="max-h-full max-w-full object-contain" />
                </div>
            </div>
            <livewire:modals.image.review-image />
            <livewire:modals.suncatcher.review-image />
            <livewire:modals.account-manager.data-form />
            <x-toast />
        </div>
        @livewireScripts
    </body>
</html>
