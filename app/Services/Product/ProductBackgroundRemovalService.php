<?php

namespace App\Services\Product;

use App\Models\Product;

class ProductBackgroundRemovalService
{
    /**
     * Determine whether generated images for this product should remove background automatically.
     */
    public function enabledFor(Product $product): bool
    {
        return (bool) config('services.background_removal.enabled', false)
            && (bool) $product->auto_remove_background;
    }
}
