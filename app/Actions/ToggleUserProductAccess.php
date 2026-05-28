<?php

namespace App\Actions;

use App\Models\Product;
use App\Models\User;

class ToggleUserProductAccess
{
    /**
     * Toggle one product page for a user.
     */
    public function __invoke(User $targetUser, Product $product): void
    {
        if ($targetUser->products()->whereKey($product->id)->exists()) {
            $targetUser->products()->detach($product->id);

            return;
        }

        $targetUser->products()->attach($product->id);
    }
}
