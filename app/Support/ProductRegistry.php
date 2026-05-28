<?php

namespace App\Support;

use App\Livewire\Pages\Mockup\Index as MockupPage;
use App\Livewire\Pages\Poster\Index as PosterPage;
use App\Livewire\Pages\Redesign\Index as RedesignPage;
use App\Livewire\Pages\Sticker\ListSticker;

class ProductRegistry
{
    /**
     * Product pages available in Offorest.
     *
     * @return array<int, array<string, string|int|bool>>
     */
    public static function all(): array
    {
        return [
            [
                'name' => 'Redesign',
                'slug' => 'redesign',
                'description' => 'Create redesign outputs from the source image.',
                'route_name' => 'offorest.products.redesign',
                'path' => 'redesign',
                'component' => RedesignPage::class,
                'sort_order' => 10,
                'is_active' => true,
            ],
            [
                'name' => 'Mockup',
                'slug' => 'mockup',
                'description' => 'Create product mockup images.',
                'route_name' => 'offorest.products.mockup',
                'path' => 'mockup',
                'component' => MockupPage::class,
                'sort_order' => 20,
                'is_active' => true,
            ],
            [
                'name' => 'Sticker',
                'slug' => 'sticker',
                'description' => 'Create sticker-ready artwork.',
                'route_name' => 'offorest.products.sticker',
                'path' => 'sticker',
                'component' => ListSticker::class,
                'sort_order' => 30,
                'is_active' => true,
            ],
            [
                'name' => 'Poster',
                'slug' => 'poster',
                'description' => 'Create poster-style assets.',
                'route_name' => 'offorest.products.poster',
                'path' => 'poster',
                'component' => PosterPage::class,
                'sort_order' => 40,
                'is_active' => true,
            ],
        ];
    }
}
