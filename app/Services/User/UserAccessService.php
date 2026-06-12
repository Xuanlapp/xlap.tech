<?php

namespace App\Services\User;

use App\Actions\CreateUserWithProductAccess;
use App\Actions\ToggleUserProductAccess;
use App\Models\Product;
use App\Models\User;
use App\Repositories\Product\ProductRepository;
use App\Repositories\User\UserRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;

class UserAccessService
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly UserRepository $users,
        private readonly CreateUserWithProductAccess $createUserWithProductAccess,
        private readonly ToggleUserProductAccess $toggleUserProductAccess,
    ) {}

    /**
     * @return Collection<int, Product>
     */
    public function activeProducts(): Collection
    {
        return $this->products->activeOrderedByName();
    }

    /**
     * @return Collection<int, User>
     */
    public function users(): Collection
    {
        return $this->users->allWithActiveProductsOrderedByName();
    }

    /**
     * @param  array{name: string, email: string, password: string, status?: string, is_admin?: bool, can_generate_amazon_listing?: bool, can_generate_etsy_listing?: bool, selectedProducts?: array<int, int|string>}  $data
     */
    public function createUser(array $data): User
    {
        return ($this->createUserWithProductAccess)($data);
    }

    /**
     * Update account details and access for a managed user.
     *
     * @param  array{name: string, email: string, password?: string|null, status?: string, is_admin?: bool, can_generate_amazon_listing?: bool, can_generate_etsy_listing?: bool, selectedProducts?: array<int, int|string>}  $data
     */
    public function updateUser(User $targetUser, array $data): User
    {
        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'status' => $data['status'] ?? 'active',
            'is_admin' => (bool) ($data['is_admin'] ?? false),
            'can_generate_amazon_listing' => (bool) ($data['can_generate_amazon_listing'] ?? false),
            'can_generate_etsy_listing' => (bool) ($data['can_generate_etsy_listing'] ?? false),
        ];

        if (! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        $targetUser->update($payload);

        $productIds = collect($data['selectedProducts'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->all();

        $targetUser->products()->sync($productIds);

        return $targetUser->refresh();
    }

    public function toggleProduct(int $userId, int $productId): bool
    {
        $targetUser = $this->users->find($userId);
        $product = Product::findOrFail($productId);

        return ($this->toggleUserProductAccess)(
            targetUser: $targetUser,
            product: $product,
        );
    }

    public function toggleAmazonListing(int $userId): bool
    {
        $targetUser = $this->users->find($userId);
        $enabled = ! $targetUser->can_generate_amazon_listing;

        $targetUser->update([
            'can_generate_amazon_listing' => $enabled,
            'can_generate_etsy_listing' => $enabled ? false : $targetUser->can_generate_etsy_listing,
        ]);

        return $enabled;
    }

    public function toggleEtsyListing(int $userId): bool
    {
        $targetUser = $this->users->find($userId);
        $enabled = ! $targetUser->can_generate_etsy_listing;

        $targetUser->update([
            'can_generate_etsy_listing' => $enabled,
            'can_generate_amazon_listing' => $enabled ? false : $targetUser->can_generate_amazon_listing,
        ]);

        return $enabled;
    }

}
