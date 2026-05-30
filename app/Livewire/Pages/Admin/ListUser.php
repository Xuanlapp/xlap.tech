<?php

namespace App\Livewire\Pages\Admin;

use App\Services\Product\ApprovedAssetDriveExportService;
use App\Services\User\UserAccessService;
use App\Services\Logging\ActivityLogService;
use App\Services\Google\GoogleDriveOAuthService;
use Illuminate\Contracts\View\View;
use Throwable;
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
        ]);

        app(UserAccessService::class)->createUser($validated);
        app(ActivityLogService::class)->record(
            event: 'admin.user_created',
            description: 'Admin created a user account.',
            properties: [
                'email' => $validated['email'],
                'is_admin' => (bool) ($validated['is_admin'] ?? false),
                'selected_products' => $validated['selectedProducts'] ?? [],
            ],
            actor: auth()->user(),
            actorType: 'admin',
        );

        $this->reset(['name', 'email', 'password', 'is_admin', 'selectedProducts']);
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
            'googleDriveConnection' => app(GoogleDriveOAuthService::class)->activeConnection(),
        ])->layout('layouts.app');
    }
}
