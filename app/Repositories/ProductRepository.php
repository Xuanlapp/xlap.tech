<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class ProductRepository
{
    public function findActiveBySlug(string $slug): Product
    {
        return Product::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();
    }

    /**
     * @return Collection<int, Product>
     */
    public function activeOrderedByName(): Collection
    {
        return Product::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }
}
