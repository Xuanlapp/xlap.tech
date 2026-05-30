<section class="min-h-[calc(100vh-4rem)] bg-[#111217] text-white">
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-medium text-cyan-300">Admin</p>
                    <h1 class="mt-2 text-3xl font-semibold">User access</h1>
                    <p class="mt-2 text-sm text-white/55">Tao user va gan san pham duoc phep mo.</p>
                </div>

                <div class="flex flex-col items-start gap-2 sm:items-end">
                    <div class="flex flex-wrap gap-2">
                        <a
                            href="{{ route('offorest.admin.google-drive.connect', [], false) }}"
                            class="inline-flex items-center justify-center rounded-md bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/15"
                        >
                            {{ $googleDriveConnection ? 'Reconnect Google Drive' : 'Connect Google Drive' }}
                        </a>

                        <button
                            type="button"
                            wire:click="uploadApprovedImagesToDrive"
                            wire:loading.attr="disabled"
                            wire:target="uploadApprovedImagesToDrive"
                            class="inline-flex items-center justify-center rounded-md bg-emerald-500 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-emerald-500/20 transition hover:bg-emerald-600 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            <span wire:loading.remove wire:target="uploadApprovedImagesToDrive">Upload images to Drive</span>
                            <span wire:loading wire:target="uploadApprovedImagesToDrive">Uploading...</span>
                        </button>
                    </div>

                    @if ($googleDriveConnection)
                        <p class="text-xs text-emerald-200">
                            Google Drive connected by {{ $googleDriveConnection->user?->email }}.
                        </p>
                    @else
                        <p class="text-xs text-amber-200">
                            Chua connect OAuth. Upload se fallback service account neu con cau hinh.
                        </p>
                    @endif

                    <p class="max-w-xl break-all text-xs text-white/45">
                        OAuth callback: {{ request()->getSchemeAndHttpHost() }}{{ route('offorest.admin.google-drive.callback', [], false) }}
                    </p>
                </div>
            </div>

            @if (session('google_drive_status'))
                <div class="mt-4 rounded-md border border-emerald-400/30 bg-emerald-400/10 px-3 py-2 text-sm text-emerald-200">
                    {{ session('google_drive_status') }}
                </div>
            @endif

            @if (session('google_drive_error'))
                <div class="mt-4 rounded-md border border-red-400/30 bg-red-400/10 px-3 py-2 text-sm text-red-200">
                    {{ session('google_drive_error') }}
                </div>
            @endif

            @if ($driveUploadStatus)
                <div class="mt-4 rounded-md border border-emerald-400/30 bg-emerald-400/10 px-3 py-2 text-sm text-emerald-200">
                    {{ $driveUploadStatus }}
                </div>
            @endif

            @if ($driveUploadError)
                <div class="mt-4 rounded-md border border-red-400/30 bg-red-400/10 px-3 py-2 text-sm text-red-200">
                    {{ $driveUploadError }}
                </div>
            @endif

            <div
                x-data="{ success: '', error: '' }"
                x-on:drive-upload-finished.window="success = $event.detail.message; error = ''; setTimeout(() => success = '', 5000)"
                x-on:drive-upload-failed.window="error = $event.detail.message; success = ''"
                class="mt-4 space-y-2"
            >
                <div x-show="success" x-cloak class="rounded-md border border-emerald-400/30 bg-emerald-400/10 px-3 py-2 text-sm text-emerald-200" x-text="success"></div>
                <div x-show="error" x-cloak class="rounded-md border border-red-400/30 bg-red-400/10 px-3 py-2 text-sm text-red-200" x-text="error"></div>
            </div>

            <div class="mt-8 grid gap-6 lg:grid-cols-[0.8fr_1.2fr]">
                <form wire:submit="createUser" class="rounded-lg border border-white/10 bg-white/[0.04] p-6">
                    <h2 class="text-lg font-semibold">Tao user</h2>

                    <div class="mt-5 space-y-4">
                        <div>
                            <label for="name" class="text-sm text-white/70">Ten</label>
                            <input id="name" wire:model="name" type="text" class="mt-1 w-full rounded-md border-white/10 bg-white text-gray-950" autocomplete="name">
                            @error('name') <p class="mt-1 text-sm text-red-300">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="email" class="text-sm text-white/70">Email</label>
                            <input id="email" wire:model="email" type="email" class="mt-1 w-full rounded-md border-white/10 bg-white text-gray-950" autocomplete="email">
                            @error('email') <p class="mt-1 text-sm text-red-300">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="password" class="text-sm text-white/70">Mat khau tam</label>
                            <input id="password" wire:model="password" type="text" class="mt-1 w-full rounded-md border-white/10 bg-white text-gray-950" autocomplete="new-password">
                            <p class="mt-1 text-xs text-white/45">Toi thieu 12 ky tu, co chu hoa/thuong va so.</p>
                            @error('password') <p class="mt-1 text-sm text-red-300">{{ $message }}</p> @enderror
                        </div>

                        <label class="flex items-center gap-2 rounded-md bg-white/[0.06] px-3 py-2 text-sm">
                            <input wire:model="is_admin" type="checkbox" class="rounded border-white/20 text-cyan-500">
                            <span>Cho phep vao Admin</span>
                        </label>
                    </div>

                    <div class="mt-5">
                        <p class="text-sm text-white/70">Quyen san pham</p>
                        <div class="mt-3 grid gap-2 sm:grid-cols-2">
                            @foreach ($products as $product)
                                <label class="flex items-center gap-2 rounded-md bg-white/[0.06] px-3 py-2 text-sm">
                                    <input wire:model="selectedProducts" type="checkbox" value="{{ $product->id }}" class="rounded border-white/20 text-cyan-500">
                                    <span>{{ $product->name }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('selectedProducts.*') <p class="mt-1 text-sm text-red-300">{{ $message }}</p> @enderror
                    </div>

                    <x-ui.button color="cyan" full type="submit" class="mt-6 shadow-lg shadow-cyan-500/20">
                        Tao user
                    </x-ui.button>

                    <div x-data="{ shown: false }" x-on:user-created.window="shown = true; setTimeout(() => shown = false, 2500)" x-show="shown" x-cloak class="mt-3 rounded-md border border-emerald-400/30 bg-emerald-400/10 px-3 py-2 text-sm text-emerald-200">
                        Da tao user va gan san pham.
                    </div>
                </form>

                <div class="rounded-lg border border-white/10 bg-white/[0.04] p-6">
                    <h2 class="text-lg font-semibold">Danh sach user</h2>

                    <div class="mt-5 overflow-x-auto">
                        <table class="min-w-full divide-y divide-white/10 text-sm">
                            <thead>
                                <tr class="text-left text-white/55">
                                    <th class="py-3 pr-4 font-medium">User</th>
                                    <th class="px-3 py-3 text-center font-medium">Admin</th>
                                    @foreach ($products as $product)
                                        <th class="px-3 py-3 text-center font-medium">{{ $product->name }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/10">
                                @foreach ($users as $user)
                                    <tr wire:key="user-access-{{ $user->id }}">
                                        <td class="py-4 pr-4">
                                            <p class="font-medium">{{ $user->name }}</p>
                                            <p class="mt-1 text-xs text-white/45">{{ $user->email }}</p>
                                        </td>
                                        <td class="px-3 py-4 text-center">
                                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-medium {{ $user->is_admin ? 'bg-cyan-400/20 text-cyan-200' : 'bg-white/10 text-white/45' }}">
                                                {{ $user->is_admin ? 'Yes' : 'No' }}
                                            </span>
                                        </td>
                                        @foreach ($products as $product)
                                            @php
                                                $enabled = $user->products->contains('id', $product->id);
                                            @endphp
                                            <td class="px-3 py-4 text-center">
                                                <button
                                                    type="button"
                                                    wire:click="toggleProduct({{ $user->id }}, {{ $product->id }})"
                                                    class="inline-flex h-7 w-12 items-center rounded-full border transition {{ $enabled ? 'border-cyan-400 bg-cyan-400/80' : 'border-white/15 bg-white/10' }}"
                                                    title="Bat/tat san pham"
                                                >
                                                    <span class="inline-block h-5 w-5 rounded-full bg-white transition {{ $enabled ? 'translate-x-5' : 'translate-x-1' }}"></span>
                                                </button>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
</section>
