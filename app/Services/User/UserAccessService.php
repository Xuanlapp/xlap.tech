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
     * @param  array{name: string, email: string, password: string, is_admin?: bool, selectedProducts?: array<int, int|string>}  $data
     */
    public function createUser(array $data): User
    {
        return ($this->createUserWithProductAccess)($data);
    }

    public function toggleProduct(int $userId, int $productId): void
    {
        ($this->toggleUserProductAccess)(
            targetUser: $this->users->find($userId),
            product: Product::findOrFail($productId),
        );
    }
}
