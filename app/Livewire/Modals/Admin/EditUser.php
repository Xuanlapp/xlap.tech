<?php

namespace App\Livewire\Modals\Admin;

use App\Models\User;
use App\Services\Logging\ActivityLogService;
use App\Services\User\UserAccessService;
use App\Support\Traits\BuildsVertexCredentialPayload;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

class EditUser extends Component
{
    use BuildsVertexCredentialPayload;

    public bool $isOpen = false;

    public ?int $userId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $status = 'active';

    public bool $is_admin = false;

    public bool $can_generate_amazon_listing = false;

    public bool $can_generate_etsy_listing = false;

    /** @var array<int, int|string> */
    public array $selectedProducts = [];

    public string $vertexMode = 'keep';

    public string $vertexJson = '';

    public string $vertexLocation = 'global';

    public ?int $vertexCopyUserId = null;

    public ?string $currentVertexLabel = null;

    /**
     * Open this modal through the shared openModal event pattern.
     *
     * @param  array<string, mixed>  $arguments
     */
    #[On('openModal')]
    public function openModal(string $component, array $arguments = []): void
    {
        if ($component !== 'modals.admin.edit-user') {
            return;
        }

        $this->open((int) ($arguments['userId'] ?? 0));
    }

    public function open(int $userId): void
    {
        $user = User::query()
            ->with(['products', 'vertexApiCredential'])
            ->findOrFail($userId);

        $this->resetValidation();
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->status = $user->status ?: 'active';
        $this->is_admin = (bool) $user->is_admin;
        $this->can_generate_amazon_listing = (bool) $user->can_generate_amazon_listing;
        $this->can_generate_etsy_listing = (bool) $user->can_generate_etsy_listing;
        $this->selectedProducts = $user->products->pluck('id')->map(fn ($id) => (int) $id)->all();
        $this->vertexMode = 'keep';
        $this->vertexJson = '';
        $this->vertexLocation = $user->vertexApiCredential?->location ?: 'global';
        $this->vertexCopyUserId = null;
        $this->currentVertexLabel = $user->vertexApiCredential
            ? $user->vertexApiCredential->client_email.' | '.($user->vertexApiCredential->project_id ?: 'no project_id')
            : null;
        $this->isOpen = true;
    }

    public function close(): void
    {
        $this->isOpen = false;
    }

    /**
     * Update user account, access, and optional Vertex credential.
     */
    public function save(): void
    {
        if (! $this->userId) {
            return;
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->userId)],
            'password' => ['nullable', 'string', Password::min(12)->mixedCase()->numbers()],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'is_admin' => ['boolean'],
            'can_generate_amazon_listing' => ['boolean'],
            'can_generate_etsy_listing' => ['boolean'],
            'selectedProducts' => ['array'],
            'selectedProducts.*' => ['integer', 'exists:products,id'],
            'vertexMode' => ['required', Rule::in(['keep', 'new', 'copy', 'remove'])],
            'vertexJson' => ['nullable', 'string', 'max:30000', 'required_if:vertexMode,new'],
            'vertexLocation' => ['nullable', 'string', 'max:100'],
            'vertexCopyUserId' => ['nullable', 'integer', 'exists:users,id', 'required_if:vertexMode,copy'],
        ]);

        $this->ensureSingleMarketplace();

        $user = User::findOrFail($this->userId);
        $vertexCredentialPayload = $this->validatedVertexCredentialPayload();

        DB::transaction(function () use ($user, $validated, $vertexCredentialPayload): void {
            app(UserAccessService::class)->updateUser($user, $validated);

            if ($this->vertexMode === 'remove') {
                $user->vertexApiCredential()->update(['is_active' => false]);
            }

            if ($vertexCredentialPayload !== null) {
                $user->vertexApiCredential()->update(['is_active' => false]);
                $user->vertexApiCredential()->create($vertexCredentialPayload);
            }
        });

        app(ActivityLogService::class)->record(
            event: 'admin.user_updated',
            description: 'Admin updated a user account.',
            properties: [
                'target_user_id' => $user->id,
                'email' => $validated['email'],
                'status' => $validated['status'],
                'is_admin' => (bool) ($validated['is_admin'] ?? false),
                'can_generate_amazon_listing' => (bool) ($validated['can_generate_amazon_listing'] ?? false),
                'can_generate_etsy_listing' => (bool) ($validated['can_generate_etsy_listing'] ?? false),
                'selected_products' => $validated['selectedProducts'] ?? [],
                'vertex_mode' => $validated['vertexMode'],
            ],
            actor: auth()->user(),
            actorType: 'admin',
        );

        $this->isOpen = false;
        $this->dispatch('users-updated');
        $this->dispatch('toast', type: 'success', title: 'Successfully saved!', message: 'Da cap nhat user.');
    }

    public function render(): View
    {
        $service = app(UserAccessService::class);

        return view('livewire.modals.admin.edit-user', [
            'products' => $service->activeProducts(),
            'vertexCredentialUsers' => User::query()
                ->whereHas('vertexApiCredential')
                ->when($this->userId, fn ($query) => $query->whereKeyNot($this->userId))
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
        ]);
    }

    private function ensureSingleMarketplace(): void
    {
        if ($this->can_generate_amazon_listing && $this->can_generate_etsy_listing) {
            throw ValidationException::withMessages([
                'can_generate_amazon_listing' => 'Moi user chi duoc chon Amazon hoac Etsy, khong duoc chon ca hai.',
                'can_generate_etsy_listing' => 'Moi user chi duoc chon Amazon hoac Etsy, khong duoc chon ca hai.',
            ]);
        }
    }

    /**
     * @return array{function_key: string, project_id: string|null, location: string, client_email: string|null, private_key: string|null, credentials_json: array<string, mixed>|null, is_active: bool}|null
     */
    private function validatedVertexCredentialPayload(): ?array
    {
        if ($this->vertexMode === 'keep' || $this->vertexMode === 'remove') {
            return null;
        }

        if ($this->vertexMode === 'copy') {
            return $this->copiedImageVertexCredentialPayload($this->vertexCopyUserId, $this->vertexLocation);
        }

        return [
            'function_key' => 'image_generation',
            ...$this->vertexCredentialPayloadFromJson(
                json: $this->vertexJson,
                location: $this->normalizedLocation($this->vertexLocation),
            ),
            'is_active' => true,
        ];
    }
}
