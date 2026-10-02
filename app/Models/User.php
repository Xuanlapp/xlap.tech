<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\DataImportUser;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'username', 'email', 'password', 'avatar_path', 'status', 'role', 'admin_permissions', 'is_admin', 'can_generate_amazon_listing', 'can_generate_etsy_listing', 'can_access_wali', 'can_view_all_proxy', 'theme_mode'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    public const ADMIN_PERMISSIONS = [
        'manage_users' => 'Xem danh sach user',
        'create_users' => 'Tao user moi',
        'edit_users' => 'Chinh sua user',
        'manage_platform_settings' => 'Quan ly cau hinh he thong',
        'manage_api_credentials' => 'Quan ly API credentials',
        'cleanup_files' => 'Don dep anh va file rac',
    ];
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => 'string',
            'admin_permissions' => 'array',
            'is_admin' => 'boolean',
            'can_generate_amazon_listing' => 'boolean',
            'can_generate_etsy_listing' => 'boolean',
            'can_access_wali' => 'boolean',
            'can_view_all_proxy' => 'boolean',
            'theme_mode' => 'string',
        ];
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function hasAdminPermission(string $permission): bool
    {
        return $this->isSuperAdmin() || ($this->role === 'admin' && in_array($permission, $this->admin_permissions ?? [], true));
    }

    public function canManageUsers(): bool
    {
        return $this->hasAdminPermission('manage_users');
    }

    public function canCreateUsers(): bool
    {
        return $this->hasAdminPermission('create_users');
    }

    public function canEditUsers(): bool
    {
        return $this->hasAdminPermission('edit_users');
    }

    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    /**
     * Products this user can access.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)->withTimestamps();
    }

    /**
     * Proxy sources this user can view.
     */
    public function dataHubProxies(): BelongsToMany
    {
        return $this->belongsToMany(DataHubProxy::class, 'data_hub_proxy_user')->withTimestamps();
    }

    public function financialAccounts(): BelongsToMany
    {
        return $this->belongsToMany(FinancialAccount::class, 'financial_account_user')
            ->withPivot(['access_level', 'can_view', 'can_add', 'can_edit', 'can_delete'])
            ->withTimestamps();
    }

    public function accountFinancialViews(): BelongsToMany
    {
        return $this->belongsToMany(Account::class, 'account_user')->withTimestamps();
    }

    /**
     * Determine whether the user can access a product page.
     */
    public function canAccessProduct(string $slug): bool
    {
        if ($this->is_admin || in_array($this->role, ['admin', 'super_admin'], true)) {
            return true;
        }

        if ($slug === 'suncatcher') {
            return $this->products()
                ->whereIn('slug', ['suncatcher', 'ornament'])
                ->where('is_active', true)
                ->exists();
        }

        if ($this->isManager()) {
            return Product::query()
                ->where('slug', $slug)
                ->where('is_active', true)
                ->exists();
        }

        return $this->products()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Vertex API credential owned by the user.
     */
    public function vertexApiCredential(): HasOne
    {
        return $this->hasOne(VertexApiCredential::class)
            ->where('is_active', true)
            ->latestOfMany();
    }

    /**
     * AI providers this user can choose from.
     */
    public function aiProviders(): HasMany
    {
        return $this->hasMany(UserAiProvider::class);
    }

    /**
     * Enabled AI providers this user can choose from.
     */
    public function enabledAiProviders(): HasMany
    {
        return $this->aiProviders()->where('is_enabled', true);
    }

    /**
     * Determine whether the user can choose an AI provider.
     */
    public function canUseAiProvider(string $providerKey): bool
    {
        return $this->enabledAiProviders()
            ->where('provider_key', $providerKey)
            ->exists();
    }

    /**
     * Return the selected AI provider key, falling back to the first enabled provider.
     */
    public function activeAiProviderKey(): ?string
    {
        $providers = $this->relationLoaded('aiProviders')
            ? $this->aiProviders
            : $this->aiProviders()->get();

        $enabledProviders = $providers->where('is_enabled', true);

        return $enabledProviders->firstWhere('is_default', true)?->provider_key
            ?: $enabledProviders->first()?->provider_key;
    }

    /**
     * Persist the user's preferred AI provider for future sessions.
     */
    public function setDefaultAiProvider(string $providerKey): bool
    {
        $provider = $this->aiProviders()
            ->where('provider_key', $providerKey)
            ->where('is_enabled', true)
            ->first();

        if (! $provider) {
            return false;
        }

        $this->aiProviders()
            ->where('is_default', true)
            ->update(['is_default' => false]);

        $provider->forceFill(['is_default' => true])->save();

        return true;
    }

    /**
     * Active Google Drive OAuth connection owned by the user.
     */
    public function googleDriveConnection(): HasOne
    {
        return $this->hasOne(GoogleDriveConnection::class)->where('is_active', true)->latestOfMany();
    }

    /**
     * Prompts owned by the user.
     */
    public function prompts(): HasMany
    {
        return $this->hasMany(Prompt::class);
    }

    /**
     * Product design rows owned by the user.
     */
    public function productDesignAssets(): HasMany
    {
        return $this->hasMany(ProductDesignAsset::class);
    }

    /**
     * Data import sheet configs owned by the user.
     */
    public function dataImportUsers(): HasMany
    {
        return $this->hasMany(DataImportUser::class);
    }

    /**
     * PSD mockup templates uploaded by the user.
     */
    public function psdMockupTemplates(): HasMany
    {
        return $this->hasMany(PsdMockupTemplate::class);
    }
}

