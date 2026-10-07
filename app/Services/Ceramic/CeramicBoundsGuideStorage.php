<?php

namespace App\Services\Ceramic;

use App\Models\User;
use Illuminate\Support\Facades\Storage;

class CeramicBoundsGuideStorage
{
    private const SHARED_META_PATH = 'admin/ceramic/bounds-guide-meta.json';

    public function paths(User $user): array
    {
        $disk = Storage::disk('public');
        $personal = 'users/'.$user->id.'/ceramic/bounds-guide.png';
        $image = $disk->exists($personal) ? $personal : 'admin/ceramic/bounds-guide.png';

        return [
            'image' => $image,
            'config' => str_replace('.png', '.json', $image),
            'personal' => $image === $personal,
        ];
    }

    public function url(User $user): ?string
    {
        $disk = Storage::disk('public');
        $path = $this->paths($user)['image'];

        return $disk->exists($path) ? route('image-preview.show', [
            'path' => '/storage/'.$path,
            'v' => $disk->lastModified($path),
        ], false) : null;
    }

    /** URL for the shared Super Admin guide, regardless of the user's active bounds. */
    public function sharedUrl(): ?string
    {
        $disk = Storage::disk('public');
        $path = 'admin/ceramic/bounds-guide.png';

        return $disk->exists($path) ? route('image-preview.show', [
            'path' => '/storage/'.$path,
            'v' => $disk->lastModified($path),
        ], false) : null;
    }

    public function guideLink(): ?string
    {
        $disk = Storage::disk('public');

        if (! $disk->exists(self::SHARED_META_PATH)) {
            return null;
        }

        $meta = json_decode((string) $disk->get(self::SHARED_META_PATH), true);

        return is_array($meta) && filter_var($meta['guide_url'] ?? null, FILTER_VALIDATE_URL)
            ? $meta['guide_url']
            : null;
    }

    public function saveGuideLink(?string $url): void
    {
        Storage::disk('public')->put(self::SHARED_META_PATH, json_encode([
            'guide_url' => $url ?: null,
            'updated_at' => now()->toIso8601String(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    public function config(User $user): ?array
    {
        $paths = $this->paths($user);

        return app(CeramicBoundsGuideAnalyzer::class)->loadOrCreate(
            Storage::disk('public'), $paths['image'], $paths['config'],
        );
    }
}


