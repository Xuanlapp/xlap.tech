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

    #[Session(key: 'admin.users.amazon-listing')]
    public bool $can_generate_amazon_listing = false;

    #[Session(key: 'admin.users.etsy-listing')]
    public bool $can_generate_etsy_listing = false;

    /** @var array<int, int|string> */
    #[Session(key: 'admin.users.selected-products')]
    public array $selectedProducts = [];

    public string $vertexMode = 'none';

    public string $vertexJson = '';

    public string $vertexLocation = 'global';

    public ?int $vertexCopyUserId = null;

    public string $marketplaceVertexJson = '';

    public string $marketplaceVertexLocation = 'global';

    public ?string $driveUploadStatus = null;

    public ?string $driveUploadError = null;

    public function createUser(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::min(12)->mixedCase()->numbers()],
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

        if (
            (bool) ($validated['can_generate_amazon_listing'] ?? false)
            && (bool) ($validated['can_generate_etsy_listing'] ?? false)
        ) {
            throw ValidationException::withMessages([
                'can_generate_amazon_listing' => 'Moi user chi duoc chon Amazon hoac Etsy, khong duoc chon ca hai.',
                'can_generate_etsy_listing' => 'Moi user chi duoc chon Amazon hoac Etsy, khong duoc chon ca hai.',
            ]);
        }

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
                'can_generate_amazon_listing' => (bool) ($validated['can_generate_amazon_listing'] ?? false),
                'can_generate_etsy_listing' => (bool) ($validated['can_generate_etsy_listing'] ?? false),
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
            'can_generate_amazon_listing',
            'can_generate_etsy_listing',
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
        try {
            $enabled = app(UserAccessService::class)->toggleProduct($userId, $productId);
        } catch (ValidationException $exception) {
            $this->dispatch(
                'toast',
                type: 'error',
                title: 'Action failed!',
                message: collect($exception->errors())->flatten()->first() ?? 'Khong the bat quyen san pham.',
            );

            return;
        }

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

    public function toggleAmazonListing(int $userId): void
    {
        $enabled = app(UserAccessService::class)->toggleAmazonListing($userId);

        app(ActivityLogService::class)->record(
            event: 'admin.marketplace_listing_access_toggled',
            description: $enabled ? 'Admin granted Amazon listing generation.' : 'Admin revoked Amazon listing generation.',
            properties: [
                'target_user_id' => $userId,
                'marketplace' => 'amazon',
                'enabled' => $enabled,
            ],
            actor: auth()->user(),
            actorType: 'admin',
        );
    }

    public function toggleEtsyListing(int $userId): void
    {
        $enabled = app(UserAccessService::class)->toggleEtsyListing($userId);

        app(ActivityLogService::class)->record(
            event: 'admin.marketplace_listing_access_toggled',
            description: $enabled ? 'Admin granted Etsy listing generation.' : 'Admin revoked Etsy listing generation.',
            properties: [
                'target_user_id' => $userId,
                'marketplace' => 'etsy',
                'enabled' => $enabled,
            ],
            actor: auth()->user(),
            actorType: 'admin',
        );
    }

    public function saveMarketplaceVertexCredential(): void
    {
        $validated = $this->validate([
            'marketplaceVertexJson' => ['required', 'string', 'max:30000'],
            'marketplaceVertexLocation' => ['nullable', 'string', 'max:100'],
        ]);

        $payload = $this->vertexCredentialPayloadFromJson(
            json: $validated['marketplaceVertexJson'],
            location: $this->normalizedLocation($validated['marketplaceVertexLocation'] ?? 'global'),
        );

        VertexApiCredential::query()
            ->where('function_key', 'marketplace_listing')
            ->update(['is_active' => false]);

        VertexApiCredential::query()->create(
            [
                ...$payload,
                'user_id' => null,
                'function_key' => 'marketplace_listing',
                'is_active' => true,
            ],
        );

        app(ActivityLogService::class)->record(
            event: 'admin.marketplace_vertex_configured',
            description: 'Admin configured the Marketplace listing Vertex credential.',
            properties: [
                'project_id' => $payload['project_id'],
                'location' => $payload['location'],
                'client_email' => $payload['client_email'],
            ],
            actor: auth()->user(),
            actorType: 'admin',
        );

        $this->reset('marketplaceVertexJson');
        $this->marketplaceVertexLocation = 'global';
        $this->dispatch('toast', type: 'success', title: 'Successfully saved!', message: 'Da luu Vertex API cho title/listing.');
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
            'marketplaceVertexCredential' => VertexApiCredential::query()
                ->whereNull('user_id')
                ->where('function_key', 'marketplace_listing')
                ->where('is_active', true)
                ->first(),
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

        return [
            'function_key' => 'image_generation',
            ...$this->vertexCredentialPayloadFromJson($this->vertexJson, $this->normalizedVertexLocation()),
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
            ->where('function_key', 'image_generation')
            ->where('is_active', true)
            ->first();

        if (! $source) {
            throw ValidationException::withMessages([
                'vertexCopyUserId' => 'User duoc chon chua co Vertex API active.',
            ]);
        }

        return [
            'function_key' => 'image_generation',
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
        return $this->normalizedLocation($this->vertexLocation);
    }

    private function normalizedLocation(string $location): string
    {
        $location = trim($location);

        return $location !== '' ? $location : 'global';
    }

    /**
     * @return array{project_id: string|null, location: string, client_email: string, private_key: string, credentials_json: array<string, mixed>}
     */
    private function vertexCredentialPayloadFromJson(string $json, string $location): array
    {
        try {
            $credentials = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw ValidationException::withMessages([
                'vertexJson' => 'Vertex service account JSON khong hop le.',
                'marketplaceVertexJson' => 'Vertex service account JSON khong hop le.',
            ]);
        }

        if (array_is_list($credentials) && isset($credentials[0]) && is_array($credentials[0])) {
            $credentials = $credentials[0];
        }

        if (! is_array($credentials)) {
            throw ValidationException::withMessages([
                'vertexJson' => 'Vertex service account JSON khong hop le.',
                'marketplaceVertexJson' => 'Vertex service account JSON khong hop le.',
            ]);
        }

        $clientEmail = $credentials['client_email'] ?? null;
        $privateKey = $credentials['private_key'] ?? null;
        $projectId = $credentials['project_id'] ?? null;

        if (! is_string($clientEmail) || $clientEmail === '' || ! is_string($privateKey) || $privateKey === '') {
            throw ValidationException::withMessages([
                'vertexJson' => 'JSON phai co client_email va private_key.',
                'marketplaceVertexJson' => 'JSON phai co client_email va private_key.',
            ]);
        }

        return [
            'project_id' => is_string($projectId) && $projectId !== '' ? $projectId : null,
            'location' => $location,
            'client_email' => $clientEmail,
            'private_key' => str_replace('\\n', "\n", $privateKey),
            'credentials_json' => $credentials,
        ];
    }
}
