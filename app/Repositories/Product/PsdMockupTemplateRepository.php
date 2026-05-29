<?php

namespace App\Repositories\Product;

use App\Models\PsdMockupTemplate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PsdMockupTemplateRepository
{
    /**
     * @return Collection<int, PsdMockupTemplate>
     */
    public function forUserProductAndFunction(int $userId, int $productId, string $functionKey): Collection
    {
        return PsdMockupTemplate::query()
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->where('function_key', $functionKey)
            ->latest()
            ->get();
    }

    public function activeForUserProductAndFunction(int $userId, int $productId, string $functionKey): ?PsdMockupTemplate
    {
        return PsdMockupTemplate::query()
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->where('function_key', $functionKey)
            ->where('is_active', true)
            ->latest()
            ->first();
    }

    public function createActive(
        int $userId,
        int $productId,
        string $functionKey,
        string $name,
        string $originalFilename,
        string $storagePath,
    ): PsdMockupTemplate {
        return DB::transaction(function () use ($userId, $productId, $functionKey, $name, $originalFilename, $storagePath): PsdMockupTemplate {
            PsdMockupTemplate::query()
                ->where('user_id', $userId)
                ->where('product_id', $productId)
                ->where('function_key', $functionKey)
                ->update(['is_active' => false]);

            return PsdMockupTemplate::create([
                'user_id' => $userId,
                'product_id' => $productId,
                'function_key' => $functionKey,
                'name' => $name,
                'original_filename' => $originalFilename,
                'storage_path' => $storagePath,
                'is_active' => true,
            ]);
        });
    }

    public function activate(PsdMockupTemplate $template): PsdMockupTemplate
    {
        return DB::transaction(function () use ($template): PsdMockupTemplate {
            PsdMockupTemplate::query()
                ->where('user_id', $template->user_id)
                ->where('product_id', $template->product_id)
                ->where('function_key', $template->function_key)
                ->whereKeyNot($template->id)
                ->update(['is_active' => false]);

            $template->update(['is_active' => true]);

            return $template->refresh();
        });
    }

    public function findForUserProductAndFunction(int $templateId, int $userId, int $productId, string $functionKey): PsdMockupTemplate
    {
        return PsdMockupTemplate::query()
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->where('function_key', $functionKey)
            ->findOrFail($templateId);
    }
}
