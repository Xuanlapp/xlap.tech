<section class="min-h-[calc(100vh-4rem)] bg-[#111217] text-white">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8" wire:poll.30s>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-medium text-cyan-300">Marketplace</p>
                <h1 class="mt-2 text-3xl font-semibold">Listing metadata logs</h1>
                <p class="mt-2 text-sm text-white/55">
                    Theo doi item nao dang doi, dang chay, da xong hoac bi loi khi tao Amazon/Etsy title metadata.
                </p>
            </div>

            <button type="button" wire:click="$refresh" class="inline-flex items-center justify-center rounded-md bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/15">
                Refresh
            </button>
        </div>

        <div class="mt-6 grid gap-3 sm:grid-cols-5">
            @foreach ($statusOptions as $option)
                @php
                    $labels = [
                        'all' => 'All',
                        'waiting' => 'Waiting',
                        'processing' => 'Running',
                        'completed' => 'Done',
                        'failed' => 'Failed',
                    ];
                @endphp
                <button
                    type="button"
                    wire:click="$set('status', '{{ $option }}')"
                    class="rounded-lg border px-4 py-3 text-left transition {{ $status === $option ? 'border-cyan-300 bg-cyan-300/15 text-cyan-100' : 'border-white/10 bg-white/[0.04] text-white/70 hover:bg-white/[0.07]' }}"
                >
                    <div class="text-xs font-semibold uppercase">{{ $labels[$option] }}</div>
                    <div class="mt-1 text-2xl font-bold">{{ $statusCounts[$option] ?? 0 }}</div>
                </button>
            @endforeach
        </div>

        <div class="mt-6 rounded-lg border border-white/10 bg-white/[0.04] p-4">
            <label for="listing-search" class="text-sm text-white/70">Search</label>
            <input
                id="listing-search"
                wire:model.live.debounce.400ms="search"
                type="text"
                class="mt-1 w-full rounded-md border-white/10 bg-white text-gray-950"
                placeholder="Keyword, title{{ auth()->user()->is_admin ? ', user email' : '' }}..."
            >
        </div>

        <div class="mt-6 overflow-hidden rounded-lg border border-white/10 bg-white/[0.04]">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-white/10 text-sm">
                    <thead class="bg-white/[0.03] text-left text-white/55">
                        <tr>
                            <th class="px-4 py-3 font-medium">Item</th>
                            @if (auth()->user()->is_admin)
                                <th class="px-4 py-3 font-medium">User</th>
                            @endif
                            <th class="px-4 py-3 font-medium">Marketplace</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 font-medium">Timing</th>
                            <th class="px-4 py-3 font-medium">Output</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        @forelse ($assets as $asset)
                            @php
                                $derivedStatus = $asset->marketplace_listing_status
                                    ?: ($asset->title ? 'completed' : 'waiting');
                                $marketplace = $asset->marketplace_listing_marketplace
                                    ?: ($asset->user?->can_generate_amazon_listing ? 'amazon' : ($asset->user?->can_generate_etsy_listing ? 'etsy' : 'none'));
                                $statusClasses = [
                                    'waiting' => 'bg-amber-400/15 text-amber-200',
                                    'processing' => 'bg-blue-400/15 text-blue-200',
                                    'completed' => 'bg-emerald-400/15 text-emerald-200',
                                    'failed' => 'bg-red-400/15 text-red-200',
                                ];
                            @endphp
                            <tr wire:key="listing-metadata-row-{{ $asset->id }}">
                                <td class="px-4 py-4 align-top">
                                    <p class="font-semibold text-white">#{{ $asset->id }} - {{ $asset->keyword }}</p>
                                    <p class="mt-1 text-xs text-white/45">{{ $asset->product?->name }} | approved {{ optional($asset->approved_at)->format('Y-m-d H:i') }}</p>
                                </td>
                                @if (auth()->user()->is_admin)
                                    <td class="px-4 py-4 align-top">
                                        <p class="font-medium text-white/90">{{ $asset->user?->name }}</p>
                                        <p class="mt-1 text-xs text-white/45">{{ $asset->user?->email }}</p>
                                    </td>
                                @endif
                                <td class="px-4 py-4 align-top">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $marketplace === 'amazon' ? 'bg-orange-400/15 text-orange-200' : ($marketplace === 'etsy' ? 'bg-purple-400/15 text-purple-200' : 'bg-white/10 text-white/45') }}">
                                        {{ ucfirst($marketplace) }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 align-top">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $statusClasses[$derivedStatus] ?? 'bg-white/10 text-white/45' }}">
                                        {{ $derivedStatus === 'processing' ? 'Running' : ucfirst($derivedStatus) }}
                                    </span>
                                    <p class="mt-2 text-xs text-white/45">Attempts: {{ $asset->marketplace_listing_attempts ?? 0 }}</p>
                                    @if ($asset->marketplace_listing_error)
                                        <p class="mt-2 max-w-xs text-xs text-red-200">{{ $asset->marketplace_listing_error }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-4 align-top text-xs text-white/55">
                                    <p>Started: {{ optional($asset->marketplace_listing_started_at)->format('Y-m-d H:i:s') ?: '-' }}</p>
                                    <p class="mt-1">Done: {{ optional($asset->marketplace_listing_completed_at)->format('Y-m-d H:i:s') ?: '-' }}</p>
                                </td>
                                <td class="px-4 py-4 align-top">
                                    @if ($asset->title)
                                        <p class="max-w-md font-medium text-white">{{ $asset->title }}</p>
                                        @if ($asset->generic_keyword)
                                            <p class="mt-2 max-w-md text-xs text-white/45">Generic: {{ $asset->generic_keyword }}</p>
                                        @endif
                                        @if ($asset->tags)
                                            <p class="mt-2 max-w-md text-xs text-white/45">Tags: {{ $asset->tags }}</p>
                                        @endif
                                    @else
                                        <span class="text-white/35">No output yet</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ auth()->user()->is_admin ? 6 : 5 }}" class="px-4 py-10 text-center text-white/45">
                                    Chua co listing metadata nao trong filter nay.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-white/10 px-4 py-3">
                {{ $assets->links() }}
            </div>
        </div>
    </div>
</section>
