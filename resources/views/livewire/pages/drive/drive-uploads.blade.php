<section class="min-h-[calc(100vh-4rem)] bg-[#111217] text-white">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8" wire:poll.30s>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-medium text-cyan-300">Google Drive</p>
                <h1 class="mt-2 text-3xl font-semibold">Image upload logs</h1>
                <p class="mt-2 text-sm text-white/55">
                    Theo doi cac anh local da upload len Drive theo tung item da duyet.
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
            <label for="drive-upload-search" class="text-sm text-white/70">Search</label>
            <input
                id="drive-upload-search"
                wire:model.live.debounce.400ms="search"
                type="text"
                class="mt-1 w-full rounded-md border-white/10 bg-white text-gray-950"
                placeholder="Asset id, keyword{{ auth()->user()->is_admin ? ', user email' : '' }}..."
            >
        </div>

        <div class="mt-6 overflow-hidden rounded-lg border border-white/10 bg-white/[0.04]">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-white/10 text-sm">
                    <thead class="bg-white/[0.03] text-left text-white/55">
                        <tr>
                            <th class="px-4 py-3 font-medium">ID</th>
                            @if (auth()->user()->is_admin)
                                <th class="px-4 py-3 font-medium">User</th>
                            @endif
                            <th class="px-4 py-3 font-medium">File info</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 font-medium">Drive link</th>
                            <th class="px-4 py-3 font-medium">Last updated</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        @forelse ($uploads as $upload)
                            @php
                                $files = collect($upload->drive_files ?: []);
                                $fileInfo = collect($upload->file_info ?: []);
                                $statusClasses = [
                                    'waiting' => 'bg-amber-400/15 text-amber-200',
                                    'processing' => 'bg-blue-400/15 text-blue-200',
                                    'completed' => 'bg-emerald-400/15 text-emerald-200',
                                    'failed' => 'bg-red-400/15 text-red-200',
                                ];
                            @endphp
                            <tr wire:key="drive-upload-row-{{ $upload->id }}">
                                <td class="px-4 py-4 align-top">
                                    <p class="font-semibold text-white">#{{ $upload->product_design_asset_id }}</p>
                                    <p class="mt-1 text-xs text-white/45">{{ $upload->product?->name }} | {{ $upload->asset?->keyword }}</p>
                                </td>
                                @if (auth()->user()->is_admin)
                                    <td class="px-4 py-4 align-top">
                                        <p class="font-medium text-white/90">{{ $upload->user?->name }}</p>
                                        <p class="mt-1 text-xs text-white/45">{{ $upload->user?->email }}</p>
                                    </td>
                                @endif
                                <td class="px-4 py-4 align-top">
                                    <p class="font-medium text-white">{{ $files->count() }} uploaded / {{ $fileInfo->count() }} files</p>
                                    @if ($upload->drive_folder_link)
                                        <a href="{{ $upload->drive_folder_link }}" target="_blank" rel="noopener" class="mt-1 inline-flex text-xs font-semibold text-cyan-200 hover:text-cyan-100">
                                            Open folder
                                        </a>
                                    @endif
                                    <div class="mt-2 space-y-1 text-xs text-white/45">
                                        @foreach ($fileInfo->take(3) as $file)
                                            <p>{{ $file['item'] ?? '-' }} | {{ $file['field'] ?? '-' }} | {{ $file['filename'] ?? '-' }}</p>
                                        @endforeach
                                        @if ($fileInfo->count() > 3)
                                            <p>+{{ $fileInfo->count() - 3 }} more</p>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-4 align-top">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $statusClasses[$upload->status] ?? 'bg-white/10 text-white/45' }}">
                                        {{ $upload->status === 'processing' ? 'Running' : ucfirst($upload->status) }}
                                    </span>
                                    @if ($upload->error)
                                        <p class="mt-2 max-w-xs text-xs text-red-200">{{ $upload->error }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-4 align-top">
                                    @if ($files->isNotEmpty())
                                        <button type="button" wire:click="openLinks({{ $upload->id }})" class="rounded-md bg-cyan-400/15 px-3 py-2 text-xs font-semibold text-cyan-100 transition hover:bg-cyan-400/25">
                                            View {{ $files->count() }} links
                                        </button>
                                    @else
                                        <span class="text-white/35">No Drive files</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 align-top text-xs text-white/55">
                                    <p>{{ optional($upload->updated_at)->format('Y-m-d H:i:s') ?: '-' }}</p>
                                    @if ($upload->completed_at)
                                        <p class="mt-1">Done {{ $upload->completed_at->format('Y-m-d H:i:s') }}</p>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ auth()->user()->is_admin ? 6 : 5 }}" class="px-4 py-10 text-center text-white/45">
                                    Chua co upload Drive nao trong filter nay.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-white/10 px-4 py-3">
                {{ $uploads->links() }}
            </div>
        </div>
    </div>

    @if ($selectedUpload)
        <div class="fixed inset-0 z-[80] flex items-center justify-center bg-black/70 p-4" wire:key="drive-upload-modal-{{ $selectedUpload->id }}">
            <button type="button" class="fixed inset-0 cursor-default" wire:click="closeLinks" aria-label="Close Drive links"></button>
            <div class="relative z-10 max-h-[80vh] w-full max-w-4xl overflow-hidden rounded-lg border border-white/10 bg-[#171922] shadow-2xl">
                <div class="flex items-center justify-between border-b border-white/10 px-5 py-4">
                    <div>
                        <h2 class="text-lg font-semibold text-white">Drive links for #{{ $selectedUpload->product_design_asset_id }}</h2>
                        <p class="mt-1 text-xs text-white/45">{{ $selectedUpload->asset?->keyword }}</p>
                    </div>
                    <button type="button" wire:click="closeLinks" class="rounded-md px-3 py-2 text-sm text-white/55 hover:bg-white/10 hover:text-white">Close</button>
                </div>

                <div class="max-h-[65vh] overflow-y-auto p-5">
                    <div class="grid gap-3">
                        @foreach (($selectedUpload->drive_files ?: []) as $file)
                            <div class="grid gap-3 rounded-md border border-white/10 bg-white/[0.04] p-3 sm:grid-cols-[minmax(0,1fr)_120px]">
                                <div class="min-w-0">
                                    <a href="{{ $file['drive_url'] ?? '#' }}" target="_blank" rel="noopener" class="block truncate font-semibold text-cyan-200 hover:text-cyan-100">
                                        {{ $file['filename'] ?? ($file['drive_url'] ?? 'Drive file') }}
                                    </a>
                                    <p class="mt-1 break-all text-xs text-white/45">{{ $file['drive_url'] ?? '-' }}</p>
                                    <p class="mt-2 text-xs text-white/45">{{ $file['item'] ?? '-' }} | {{ $file['field'] ?? '-' }}</p>
                                </div>
                                <div class="flex h-24 items-center justify-center overflow-hidden rounded-md bg-white">
                                    @if (! empty($file['preview_url']))
                                        <img src="{{ $file['preview_url'] }}" alt="{{ $file['filename'] ?? 'Drive preview' }}" class="max-h-24 max-w-full object-contain">
                                    @else
                                        <span class="text-xs text-gray-500">No preview</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif
</section>
