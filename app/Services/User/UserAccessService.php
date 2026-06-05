<?php

namespace App\Services\User;

use App\Actions\CreateUserWithProductAccess;
use App\Actions\ToggleUserProductAccess;
use App\Models\Product;
use App\Models\User;
use App\Repositories\Product\ProductRepository;
use App\Repositories\User\UserRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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
        $this->ensureUserNameMatchesSelectedProducts(
            name: $data['name'],
            productIds: $data['selectedProducts'] ?? [],
        );

        return ($this->createUserWithProductAccess)($data);
    }

    public function toggleProduct(int $userId, int $productId): bool
    {
        $targetUser = $this->users->find($userId);
        $product = Product::findOrFail($productId);

        if (! $targetUser->products()->whereKey($product->id)->exists()) {
            $this->ensureUserNameMatchesProduct($targetUser->name, $product);
        }

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

    /**
     * A user name must include every product slug that is granted.
     *
     * @param  array<int, int|string>  $productIds
     */
    private function ensureUserNameMatchesSelectedProducts(string $name, array $productIds): void
    {
        $products = Product::query()
            ->whereIn('id', collect($productIds)->map(fn ($id): int => (int) $id)->unique()->all())
            ->get();

        $missingSlugs = $products
            ->filter(fn (Product $product): bool => ! $this->userNameContainsProductSlug($name, $product))
            ->pluck('slug')
            ->values()
            ->all();

        if ($missingSlugs === []) {
            return;
        }

        throw ValidationException::withMessages([
            'name' => 'Ten user phai chua tu khoa cua moi trang duoc chon: '.implode(', ', $missingSlugs).'.',
            'selectedProducts' => 'Ten user dang thieu tu khoa: '.implode(', ', $missingSlugs).'.',
        ]);
    }

    private function ensureUserNameMatchesProduct(string $name, Product $product): void
    {
        if ($this->userNameContainsProductSlug($name, $product)) {
            return;
        }

        throw ValidationException::withMessages([
            'selectedProducts' => "Ten user phai co tu '{$product->slug}' truoc khi bat quyen {$product->name}.",
        ]);
    }

    private function userNameContainsProductSlug(string $name, Product $product): bool
    {
        return Str::contains(Str::lower($name), Str::lower($product->slug));
    }
}
