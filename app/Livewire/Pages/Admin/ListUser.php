<?php

namespace App\Livewire\Pages\Admin;

use App\Models\User;
use App\Models\VertexApiCredential;
use App\Services\Product\ApprovedAssetDriveExportService;
use App\Services\User\UserAccessService;
use App\Services\Logging\ActivityLogService;
use App\Services\Google\GoogleDriveOAuthService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Throwable;
use JsonException;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Session;
use Livewire\Component;

class ListUser extends Component
{
    #[Session(key: 'admin.users.name')]
    public string $name = '';

    #[Session(key: 'admin.users.email')]
    public string $email = '';

    public string $password = '';

    #[Session(key: 'admin.users.is-admin')]
    public bool $is_admin = false;

    /** @var array<int, int|string> */
    #[Session(key: 'admin.users.selected-products')]
    public array $selectedProducts = [];

    public string $vertexMode = 'none';

    public string $vertexJson = '';

    public string $vertexLocation = 'global';

    public ?int $vertexCopyUserId = null;

    public ?string $driveUploadStatus = null;

    public ?string $driveUploadError = null;

    public function createUser(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::min(12)->mixedCase()->numbers()],
            'is_admin' => ['boolean'],
            'selectedProducts' => ['array'],
            'selectedProducts.*' => ['integer', 'exists:products,id'],
            'vertexMode' => ['required', Rule::in(['none', 'new', 'copy'])],
            'vertexJson' => ['nullable', 'string', 'max:30000', 'required_if:vertexMode,new'],
            'vertexLocation' => ['nullable', 'string', 'max:100'],
            'vertexCopyUserId' => ['nullable', 'integer', 'exists:users,id', 'required_if:vertexMode,copy'],
        ]);

        $vertexCredentialPayload = $this->validatedVertexCredentialPayload();

        $user = DB::transaction(function () use ($validated, $vertexCredentialPayload): User {
            $user = app(UserAccessService::class)->createUser($validated);

            if ($vertexCredentialPayload !== null) {
                $user->vertexApiCredential()->create($vertexCredentialPayload);
            }

            return $user;
        });

        app(ActivityLogService::class)->record(
            event: 'admin.user_created',
            description: 'Admin created a user account.',
            properties: [
                'email' => $validated['email'],
                'is_admin' => (bool) ($validated['is_admin'] ?? false),
                'selected_products' => $validated['selectedProducts'] ?? [],
                'vertex_mode' => $validated['vertexMode'],
                'vertex_configured' => $vertexCredentialPayload !== null,
            ],
            actor: auth()->user(),
            actorType: 'admin',
        );

        $this->reset([
            'name',
            'email',
            'password',
            'is_admin',
            'selectedProducts',
            'vertexMode',
            'vertexJson',
            'vertexLocation',
            'vertexCopyUserId',
        ]);
        $this->vertexMode = 'none';
        $this->vertexLocation = 'global';
        $this->dispatch('user-created');
    }

    public function toggleProduct(int $userId, int $productId): void
    {
        $enabled = app(UserAccessService::class)->toggleProduct($userId, $productId);

        app(ActivityLogService::class)->record(
            event: 'admin.product_access_toggled',
            description: $enabled ? 'Admin granted product access.' : 'Admin revoked product access.',
            properties: [
                'target_user_id' => $userId,
                'product_id' => $productId,
                'enabled' => $enabled,
            ],
            actor: auth()->user(),
            actorType: 'admin',
        );
    }

    public function uploadApprovedImagesToDrive(): void
    {
        $this->driveUploadStatus = null;
        $this->driveUploadError = null;

        try {
            $result = app(ApprovedAssetDriveExportService::class)->exportApprovedImages(auth()->user(), 'manual');
            $message = $result['images'] > 0
                ? "Da upload {$result['images']} anh tu {$result['assets']} item da duyet len Drive."
                : 'Khong co anh local da duyet nao can upload len Drive.';

            $this->driveUploadStatus = $message;

            $this->dispatch(
                'drive-upload-finished',
                message: $message,
            );
        } catch (Throwable $exception) {
            $this->driveUploadError = $exception->getMessage();
            $this->dispatch('drive-upload-failed', message: $exception->getMessage());
        }
    }

    public function render(): View
    {
        $service = app(UserAccessService::class);

        return view('livewire.pages.admin.list-user', [
            'products' => $service->activeProducts(),
            'users' => $service->users(),
            'vertexCredentialUsers' => User::query()
                ->whereHas('vertexApiCredential')
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
            'googleDriveConnection' => app(GoogleDriveOAuthService::class)->activeConnection(),
        ])->layout('layouts.app');
    }

    /**
     * @return array{project_id: string|null, location: string, client_email: string|null, private_key: string|null, credentials_json: array<string, mixed>|null, is_active: bool}|null
     */
    private function validatedVertexCredentialPayload(): ?array
    {
        if ($this->vertexMode === 'none') {
            return null;
        }

        if ($this->vertexMode === 'copy') {
            return $this->copiedVertexCredentialPayload();
        }

        try {
            $credentials = json_decode($this->vertexJson, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw ValidationException::withMessages([
                'vertexJson' => 'Vertex service account JSON khong hop le.',
            ]);
        }

        if (array_is_list($credentials) && isset($credentials[0]) && is_array($credentials[0])) {
            $credentials = $credentials[0];
        }

        if (! is_array($credentials)) {
            throw ValidationException::withMessages([
                'vertexJson' => 'Vertex service account JSON khong hop le.',
            ]);
        }

        $clientEmail = $credentials['client_email'] ?? null;
        $privateKey = $credentials['private_key'] ?? null;
        $projectId = $credentials['project_id'] ?? null;

        if (! is_string($clientEmail) || $clientEmail === '' || ! is_string($privateKey) || $privateKey === '') {
            throw ValidationException::withMessages([
                'vertexJson' => 'JSON phai co client_email va private_key.',
            ]);
        }

        return [
            'project_id' => is_string($projectId) && $projectId !== '' ? $projectId : null,
            'location' => $this->normalizedVertexLocation(),
            'client_email' => $clientEmail,
            'private_key' => str_replace('\\n', "\n", $privateKey),
            'credentials_json' => $credentials,
            'is_active' => true,
        ];
    }

    /**
     * @return array{project_id: string|null, location: string, client_email: string|null, private_key: string|null, credentials_json: array<string, mixed>|null, is_active: bool}|null
     */
    private function copiedVertexCredentialPayload(): ?array
    {
        $source = VertexApiCredential::query()
            ->where('user_id', $this->vertexCopyUserId)
            ->where('is_active', true)
            ->first();

        if (! $source) {
            throw ValidationException::withMessages([
                'vertexCopyUserId' => 'User duoc chon chua co Vertex API active.',
            ]);
        }

        return [
            'project_id' => $source->project_id,
            'location' => $source->location ?: $this->normalizedVertexLocation(),
            'client_email' => $source->client_email,
            'private_key' => $source->private_key,
            'credentials_json' => $source->credentials_json,
            'is_active' => true,
        ];
    }

    private function normalizedVertexLocation(): string
    {
        $location = trim($this->vertexLocation);

        return $location !== '' ? $location : 'global';
    }
}
