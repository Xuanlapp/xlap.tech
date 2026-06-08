<?php

namespace App\Services\User;

use App\Actions\CreateUserWithProductAccess;
use App\Actions\ToggleUserProductAccess;
use App\Models\Product;
use App\Models\User;
use App\Repositories\Product\ProductRepository;
use App\Repositories\User\UserRepository;
use Illuminate\Database\Eloquent\Collection;

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
     * @param  array{name: string, email: string, password: string, is_admin?: bool, can_generate_amazon_listing?: bool, can_generate_etsy_listing?: bool, selectedProducts?: array<int, int|string>}  $data
     */
    public function createUser(array $data): User
    {
        return ($this->createUserWithProductAccess)($data);
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
