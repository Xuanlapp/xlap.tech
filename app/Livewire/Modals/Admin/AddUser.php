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

class AddUser extends Component
{
    use BuildsVertexCredentialPayload;

    public bool $isOpen = false;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $status = 'active';

    public bool $is_admin = false;

    public bool $can_generate_amazon_listing = false;

    public bool $can_generate_etsy_listing = false;

    /** @var array<int, int|string> */
    public array $selectedProducts = [];

    public string $vertexMode = 'none';

    public string $vertexJson = '';

    public string $vertexLocation = 'global';

    public ?int $vertexCopyUserId = null;

    /**
     * Open this modal through the shared openModal event pattern.
     *
     * @param  array<string, mixed>  $arguments
     */
    #[On('openModal')]
    public function openModal(string $component, array $arguments = []): void
    {
        if ($component !== 'modals.admin.add-user') {
            return;
        }

        $this->resetForm();
        $this->isOpen = true;
    }

    public function close(): void
    {
        $this->isOpen = false;
    }

    /**
     * Create a user account and optional Vertex credential.
     */
    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::min(12)->mixedCase()->numbers()],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'is_admin' => ['boolean'],
            'can_generate_amazon_listing' => ['boolean'],
            'can_generate_etsy_listing' => ['boolean'],
            'selectedProducts' => ['array'],
            'selectedProducts.*' => ['integer', 'exists:products,id'],
            'vertexMode' => ['required', Rule::in(['none', 'new', 'copy'])],
            'vertexJson' => ['nullable', 'string', 'max:30000', 'required_if:vertexMode,new'],
            'vertexLocation' => ['nullable', 'string', 'max:100'],
            'vertexCopyUserId' => ['nullable', 'integer', 'exists:users,id', 'required_if:vertexMode,copy'],
        ]);

        $this->ensureSingleMarketplace();

        $vertexCredentialPayload = $this->validatedVertexCredentialPayload();

        DB::transaction(function () use ($validated, $vertexCredentialPayload): void {
            $user = app(UserAccessService::class)->createUser($validated);

            if ($vertexCredentialPayload !== null) {
                $user->vertexApiCredential()->create($vertexCredentialPayload);
            }
        });

        app(ActivityLogService::class)->record(
            event: 'admin.user_created',
            description: 'Admin created a user account.',
            properties: [
                'email' => $validated['email'],
                'is_admin' => (bool) ($validated['is_admin'] ?? false),
                'can_generate_amazon_listing' => (bool) ($validated['can_generate_amazon_listing'] ?? false),
                'can_generate_etsy_listing' => (bool) ($validated['can_generate_etsy_listing'] ?? false),
                'selected_products' => $validated['selectedProducts'] ?? [],
                'vertex_mode' => $validated['vertexMode'],
                'vertex_configured' => $vertexCredentialPayload !== null,
            ],
            actor: auth()->user(),
            actorType: 'admin',
        );

        $this->isOpen = false;
        $this->resetForm();
        $this->dispatch('users-updated');
        $this->dispatch('toast', type: 'success', title: 'Successfully saved!', message: 'Da tao user moi.');
    }

    public function render(): View
    {
        $service = app(UserAccessService::class);

        return view('livewire.modals.admin.add-user', [
            'products' => $service->activeProducts(),
            'vertexCredentialUsers' => User::query()
                ->whereHas('vertexApiCredential')
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
        ]);
    }

    private function resetForm(): void
    {
        $this->reset([
            'name',
            'email',
            'password',
            'is_admin',
            'can_generate_amazon_listing',
            'can_generate_etsy_listing',
            'selectedProducts',
            'vertexMode',
            'vertexJson',
            'vertexLocation',
            'vertexCopyUserId',
        ]);

        $this->status = 'active';
        $this->vertexMode = 'none';
        $this->vertexLocation = 'global';
        $this->resetValidation();
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
        if ($this->vertexMode === 'none') {
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
