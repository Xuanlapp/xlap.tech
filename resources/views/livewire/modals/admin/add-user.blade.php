<div>
    @if ($isOpen)
        <div
            class="fixed inset-0 z-50 flex h-full w-full items-center justify-center overflow-y-auto bg-slate-950/70 p-4 backdrop-blur-sm"
            role="dialog"
            aria-modal="true"
        >
            <button type="button" class="fixed inset-0 cursor-default focus:outline-none" wire:click="close" aria-label="Close add user modal"></button>

            <form wire:submit.prevent="save" class="relative my-6 w-full max-w-4xl overflow-hidden rounded-2xl border border-slate-200 bg-white text-slate-950 shadow-2xl">
                <div class="flex items-start justify-between border-b border-slate-200 px-6 py-5">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-cyan-600">Admin</p>
                        <h2 class="mt-1 text-xl font-bold">Add user</h2>
                        <p class="mt-1 text-sm text-slate-500">Tao account, gan workspace, tool va Vertex API trong mot luong.</p>
                    </div>
                    <button type="button" wire:click="close" class="rounded-full p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M18 6 6 18" />
                            <path d="m6 6 12 12" />
                        </svg>
                    </button>
                </div>

                <div class="max-h-[calc(100vh-12rem)] overflow-y-auto px-6 py-5">
                    <div class="grid gap-4 md:grid-cols-3">
                        <div>
                            <label for="addUserName" class="text-sm font-medium text-slate-700">Ten</label>
                            <input id="addUserName" wire:model="name" type="text" class="mt-1 w-full rounded-lg border-slate-300 text-sm text-slate-950 shadow-sm focus:border-cyan-500 focus:ring-cyan-500" autocomplete="name">
                            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="addUserEmail" class="text-sm font-medium text-slate-700">Email</label>
                            <input id="addUserEmail" wire:model="email" type="email" class="mt-1 w-full rounded-lg border-slate-300 text-sm text-slate-950 shadow-sm focus:border-cyan-500 focus:ring-cyan-500" autocomplete="email">
                            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="addUserPassword" class="text-sm font-medium text-slate-700">Mat khau tam</label>
                            <input id="addUserPassword" wire:model="password" type="text" class="mt-1 w-full rounded-lg border-slate-300 text-sm text-slate-950 shadow-sm focus:border-cyan-500 focus:ring-cyan-500" autocomplete="new-password">
                            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-slate-500">Mat khau toi thieu 12 ky tu, co chu hoa/thuong va so.</p>

                    <div class="mt-5 grid gap-4 lg:grid-cols-2">
                        <section class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <h3 class="text-sm font-bold text-slate-900">Account & marketplace</h3>
                            <div class="mt-3 grid gap-2">
                                <label class="flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-sm shadow-sm">
                                    <input wire:model="status" type="radio" value="active" class="border-slate-300 text-cyan-600">
                                    <span>Active</span>
                                </label>
                                <label class="flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-sm shadow-sm">
                                    <input wire:model="status" type="radio" value="inactive" class="border-slate-300 text-cyan-600">
                                    <span>Inactive</span>
                                </label>
                                <label class="flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-sm shadow-sm">
                                    <input wire:model="is_admin" type="checkbox" class="rounded border-slate-300 text-cyan-600">
                                    <span>Cho phep vao Admin</span>
                                </label>
                                <label class="flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-sm shadow-sm">
                                    <input wire:model="can_generate_amazon_listing" type="checkbox" class="rounded border-slate-300 text-cyan-600">
                                    <span>Amazon listing metadata</span>
                                </label>
                                <label class="flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-sm shadow-sm">
                                    <input wire:model="can_generate_etsy_listing" type="checkbox" class="rounded border-slate-300 text-cyan-600">
                                    <span>Etsy listing metadata</span>
                                </label>
                            </div>
                            @error('status') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                            @error('can_generate_amazon_listing') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                            @error('can_generate_etsy_listing') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                        </section>

                        <section class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <h3 class="text-sm font-bold text-slate-900">Products & tools</h3>
                            <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                @foreach ($products as $product)
                                    <label class="flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-sm shadow-sm">
                                        <input wire:model="selectedProducts" type="checkbox" value="{{ $product->id }}" class="rounded border-slate-300 text-cyan-600">
                                        <span>{{ $product->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('selectedProducts') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                            @error('selectedProducts.*') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                        </section>
                    </div>

                    <section class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <h3 class="text-sm font-bold text-slate-900">Vertex API</h3>
                        <div class="mt-3 grid gap-2 md:grid-cols-3">
                            <label class="flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-sm shadow-sm">
                                <input wire:model.live="vertexMode" type="radio" value="none" class="border-slate-300 text-cyan-600">
                                <span>Khong them</span>
                            </label>
                            <label class="flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-sm shadow-sm">
                                <input wire:model.live="vertexMode" type="radio" value="new" class="border-slate-300 text-cyan-600">
                                <span>Add new key</span>
                            </label>
                            <label class="flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-sm shadow-sm">
                                <input wire:model.live="vertexMode" type="radio" value="copy" class="border-slate-300 text-cyan-600">
                                <span>Copy tu user khac</span>
                            </label>
                        </div>

                        @if ($vertexMode === 'new')
                            <div class="mt-4 grid gap-4 md:grid-cols-[14rem_minmax(0,1fr)]">
                                <div>
                                    <label for="addUserVertexLocation" class="text-sm font-medium text-slate-700">Location</label>
                                    <input id="addUserVertexLocation" wire:model="vertexLocation" type="text" class="mt-1 w-full rounded-lg border-slate-300 text-sm text-slate-950" placeholder="global">
                                    @error('vertexLocation') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="addUserVertexJson" class="text-sm font-medium text-slate-700">Service account JSON</label>
                                    <textarea id="addUserVertexJson" wire:model="vertexJson" rows="6" class="mt-1 w-full rounded-lg border-slate-300 font-mono text-xs text-slate-950" placeholder='{"type":"service_account","project_id":"...","private_key":"-----BEGIN PRIVATE KEY-----\n...","client_email":"..."}'></textarea>
                                    @error('vertexJson') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        @endif

                        @if ($vertexMode === 'copy')
                            <div class="mt-4">
                                <label for="addUserVertexCopyUserId" class="text-sm font-medium text-slate-700">Copy tu user</label>
                                <select id="addUserVertexCopyUserId" wire:model="vertexCopyUserId" class="mt-1 w-full rounded-lg border-slate-300 text-sm text-slate-950">
                                    <option value="">Chon user co Vertex API</option>
                                    @foreach ($vertexCredentialUsers as $credentialUser)
                                        <option value="{{ $credentialUser->id }}">{{ $credentialUser->name }} - {{ $credentialUser->email }}</option>
                                    @endforeach
                                </select>
                                @error('vertexCopyUserId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        @endif
                    </section>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4">
                    <button type="button" wire:click="close" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                        Close
                    </button>
                    <button type="submit" class="rounded-lg bg-cyan-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-cyan-500/20 transition hover:bg-cyan-700" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">Create user</span>
                        <span wire:loading wire:target="save">Creating...</span>
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>
