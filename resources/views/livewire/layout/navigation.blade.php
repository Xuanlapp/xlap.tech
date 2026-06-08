<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }

    /**
     * Navigation data for the current user.
     *
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $products = auth()->user()
            ? auth()->user()->products()->where('is_active', true)->orderBy('name')->get()
            : collect();

        return [
            'products' => $products,
        ];
    }
}; ?>

<nav x-data="{ open: false }" class="border-b border-cyan-400/70 bg-[#111217] text-white">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex min-h-20 items-center gap-4">
            <a href="{{ route('dashboard') }}" wire:navigate class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-white">
                <x-application-logo class="h-9 w-9" />
            </a>

            <div class="hidden flex-1 items-center gap-2 rounded-full border border-white/10 bg-white/[0.06] p-2 shadow-inner shadow-white/5 lg:flex">
                @foreach ($products as $product)
                    <a
                        href="{{ route('offorest.products.'.$product->slug) }}"
                        wire:navigate
                        class="inline-flex items-center gap-3 rounded-full px-5 py-3 text-sm font-medium transition {{ request()->routeIs('offorest.products.'.$product->slug) ? 'bg-white/20 text-white shadow-sm' : 'text-white/75 hover:bg-white/10 hover:text-white' }}"
                    >
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            @if ($product->slug === 'redesign')
                                <path d="M12 20h9" />
                                <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
                            @else
                                <path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4Z" />
                            @endif
                        </svg>
                        <span>{{ $product->name }}</span>
                    </a>
                @endforeach
                <a
                    href="{{ route('offorest.listing-metadata') }}"
                    wire:navigate
                    class="inline-flex items-center gap-3 rounded-full px-5 py-3 text-sm font-medium transition {{ request()->routeIs('offorest.listing-metadata') ? 'bg-white/20 text-white shadow-sm' : 'text-white/75 hover:bg-white/10 hover:text-white' }}"
                >
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M4 5h16" />
                        <path d="M4 12h10" />
                        <path d="M4 19h7" />
                        <path d="m16 17 2 2 4-4" />
                    </svg>
                    <span>Listing</span>
                </a>
                <a
                    href="{{ route('offorest.drive-uploads') }}"
                    wire:navigate
                    class="inline-flex items-center gap-3 rounded-full px-5 py-3 text-sm font-medium transition {{ request()->routeIs('offorest.drive-uploads') ? 'bg-white/20 text-white shadow-sm' : 'text-white/75 hover:bg-white/10 hover:text-white' }}"
                >
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M12 3v12" />
                        <path d="m7 8 5-5 5 5" />
                        <path d="M5 15v4h14v-4" />
                    </svg>
                    <span>Uploads</span>
                </a>
                @if (auth()->user()->is_admin)
                    <a
                        href="{{ route('offorest.admin.users') }}"
                        wire:navigate
                        class="inline-flex items-center gap-3 rounded-full px-5 py-3 text-sm font-medium transition {{ request()->routeIs('offorest.admin.users') ? 'bg-white/20 text-white shadow-sm' : 'text-white/75 hover:bg-white/10 hover:text-white' }}"
                    >
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                            <circle cx="9" cy="7" r="4" />
                            <path d="M19 8v6" />
                            <path d="M22 11h-6" />
                        </svg>
                        <span>Admin</span>
                    </a>
                    <a
                        href="{{ route('offorest.admin.logs') }}"
                        wire:navigate
                        class="inline-flex items-center gap-3 rounded-full px-5 py-3 text-sm font-medium transition {{ request()->routeIs('offorest.admin.logs') ? 'bg-white/20 text-white shadow-sm' : 'text-white/75 hover:bg-white/10 hover:text-white' }}"
                    >
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M4 5h16" />
                            <path d="M4 12h16" />
                            <path d="M4 19h10" />
                        </svg>
                        <span>Logs</span>
                    </a>
                @endif
            </div>

            <div class="ml-auto hidden items-center gap-3 lg:flex">
                <a href="{{ route('profile') }}" wire:navigate class="rounded-full border border-white/10 px-4 py-2 text-sm text-white/70 hover:bg-white/10 hover:text-white">
                    {{ auth()->user()->name }}
                </a>
                <button wire:click="logout" class="rounded-full border border-white/10 px-4 py-2 text-sm text-white/70 hover:bg-white/10 hover:text-white">
                    Log out
                </button>
            </div>

            <button @click="open = ! open" class="ml-auto inline-flex h-10 w-10 items-center justify-center rounded-md border border-white/10 text-white/70 lg:hidden">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 6h16" />
                    <path d="M4 12h16" />
                    <path d="M4 18h16" />
                </svg>
            </button>
        </div>
    </div>

    <div x-show="open" x-cloak class="border-t border-white/10 px-4 py-3 lg:hidden">
        <div class="space-y-2">
            @foreach ($products as $product)
                <a href="{{ route('offorest.products.'.$product->slug) }}" wire:navigate class="block rounded-md px-3 py-2 text-sm {{ request()->routeIs('offorest.products.'.$product->slug) ? 'bg-white/15 text-white' : 'text-white/70' }}">
                    {{ $product->name }}
                </a>
            @endforeach
            <a href="{{ route('offorest.listing-metadata') }}" wire:navigate class="block rounded-md px-3 py-2 text-sm {{ request()->routeIs('offorest.listing-metadata') ? 'bg-white/15 text-white' : 'text-white/70' }}">
                Listing
            </a>
            <a href="{{ route('offorest.drive-uploads') }}" wire:navigate class="block rounded-md px-3 py-2 text-sm {{ request()->routeIs('offorest.drive-uploads') ? 'bg-white/15 text-white' : 'text-white/70' }}">
                Uploads
            </a>
            @if (auth()->user()->is_admin)
                <a href="{{ route('offorest.admin.users') }}" wire:navigate class="block rounded-md px-3 py-2 text-sm {{ request()->routeIs('offorest.admin.users') ? 'bg-white/15 text-white' : 'text-white/70' }}">Admin</a>
                <a href="{{ route('offorest.admin.logs') }}" wire:navigate class="block rounded-md px-3 py-2 text-sm {{ request()->routeIs('offorest.admin.logs') ? 'bg-white/15 text-white' : 'text-white/70' }}">Logs</a>
            @endif
            <a href="{{ route('profile') }}" wire:navigate class="block rounded-md px-3 py-2 text-sm text-white/70">Profile</a>
            <button wire:click="logout" class="block w-full rounded-md px-3 py-2 text-left text-sm text-white/70">Log out</button>
        </div>
    </div>
</nav>
