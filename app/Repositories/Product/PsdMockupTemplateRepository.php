<?php

namespace App\Repositories\Product;

use App\Models\PsdMockupTemplate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class PsdMockupTemplateRepository
{
    public function uploadActive(
        int $userId,
        int $productId,
        string $functionKey,
        string $name,
        UploadedFile $file,
        string $directory,
    ): PsdMockupTemplate {
        $path = $file->storeAs($directory, \Illuminate\Support\Str::uuid().'.psd', 'public');

        if (! $path) {
            throw new \RuntimeException('Khong the luu file PSD. Vui long thu lai.');
        }

        try {
            return $this->createActive($userId, $productId, $functionKey, $name, $file->getClientOriginalName(), $path);
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($path);
            throw $exception;
        }
    }

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
        $removedPaths = [];

        $template = DB::transaction(function () use ($userId, $productId, $functionKey, $name, $originalFilename, $storagePath, &$removedPaths): PsdMockupTemplate {
            // Serialize simultaneous uploads for the same owner across products.
            DB::table('users')->where('id', $userId)->lockForUpdate()->first();

            PsdMockupTemplate::query()
                ->where('user_id', $userId)
                ->where('product_id', $productId)
                ->where('function_key', $functionKey)
                ->update(['is_active' => false]);

            $template = PsdMockupTemplate::create([
                'user_id' => $userId,
                'product_id' => $productId,
                'function_key' => $functionKey,
                'name' => $name,
                'original_filename' => $originalFilename,
                'storage_path' => $storagePath,
                'is_active' => true,
            ]);

            $obsolete = PsdMockupTemplate::query()
                ->where('user_id', $userId)
                ->where('product_id', $productId)
                ->where('function_key', $functionKey)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get()
                ->slice(4);

            foreach ($obsolete as $old) {
                $removedPaths[] = $old->storage_path;
                $old->delete();
            }

            return $template;
        });

        // Only delete bytes once the new row is committed. Never delete a
        // path still referenced elsewhere (e.g. imported legacy rows).
        foreach (array_unique($removedPaths) as $path) {
            if (PsdMockupTemplate::query()->where('storage_path', $path)->exists()) {
                continue;
            }

            try {
                if ($path && Storage::disk('public')->exists($path) && ! Storage::disk('public')->delete($path)) {
                    Log::warning('Could not remove obsolete PSD file', ['path' => $path]);
                }
            } catch (\Throwable $exception) {
                Log::warning('Could not remove obsolete PSD file', ['path' => $path, 'error' => $exception->getMessage()]);
            }
        }

        return $template;
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
