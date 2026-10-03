<?php

namespace App\Livewire\Pages\Glass;

use App\Livewire\Concerns\ReportsUserActionErrors;
use App\Models\ProductDesignAsset;
use App\Services\Image\ImageLinkPreviewService;
use App\Services\Logging\ActivityLogService;
use App\Services\Glass\PsdMockupTemplateService;
use App\Services\Glass\GlassService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use Livewire\Component;
use RuntimeException;
use Throwable;

class ProductDesignCard extends Component
{
    use ReportsUserActionErrors;

    public int $assetId;

    public int $mockupAutoRefreshCount = 0;

    public ?string $activePsdTemplateName = null;

    #[Reactive]
    public ?string $providerKey = null;

    #[Reactive]
    public ?string $imageModel = null;

    public function mount(int $assetId, ?string $activePsdTemplateName = null, ?string $providerKey = null, ?string $imageModel = null): void
    {
        $this->assetId = $assetId;
        $this->activePsdTemplateName = $activePsdTemplateName;
        $this->providerKey = $providerKey;
        $this->imageModel = $imageModel;
    }

    #[On('glass-product-design-updated')]
    public function refreshWhenUpdated(int $assetId): void
    {
        if ($assetId !== $this->assetId) {
            return;
        }
    }

    public function generateRedesign(): void
    {
        try {
            $asset = app(GlassService::class)->generateRedesign(auth()->user(), $this->assetId, $this->providerKey, $this->imageModel);
            app(ActivityLogService::class)->record(
                event: 'glass.master_generated',
                description: 'User generated Glass master image.',
                subject: $asset,
                properties: ['item_number' => $asset->item_number, 'redesign' => $asset->redesign],
            );

            $this->dispatch('toast', type: 'success', title: 'Successfully saved!', message: 'Da tao anh master.');
        } catch (RuntimeException $exception) {
            $this->reportUserActionError($exception, 'glass.generate_redesign', ['asset_id' => $this->assetId]);
            $this->dispatch('toast', type: 'error', title: 'Action failed!', message: $exception->getMessage());
        } catch (Throwable $exception) {
            $this->reportUserActionError($exception, 'glass.generate_redesign', ['asset_id' => $this->assetId]);
            Log::error('Glass master generation failed unexpectedly.', [
                'asset_id' => $this->assetId,
                'message' => $exception->getMessage(),
            ]);

            $this->dispatch('toast', type: 'error', title: 'Action failed!', message: 'Loi he thong khi tao anh master. Hay xem log de biet chi tiet.');
        } finally {
            $this->dispatch('glass-generation-finished');
        }
    }

    public function generatePsdMockups(): void
    {
        try {
            $asset = app(GlassService::class)->generatePsdMockups(auth()->user(), $this->assetId);
            $this->mockupAutoRefreshCount = 0;
            app(ActivityLogService::class)->record(
                event: 'glass.psd_mockups_generated',
                description: 'User rendered Glass PSD mockups.',
                subject: $asset,
                properties: ['item_number' => $asset->item_number],
            );

            $this->dispatch('toast', type: 'success', title: 'Da vao hang doi!', message: 'May local se render mockup va tu dong cap nhat khi xong.');
        } catch (RuntimeException $exception) {
            $this->reportUserActionError($exception, 'glass.generate_psd_mockups', ['asset_id' => $this->assetId]);
            $this->dispatch('toast', type: 'error', title: 'Action failed!', message: $exception->getMessage());
        } catch (Throwable $exception) {
            $this->reportUserActionError($exception, 'glass.generate_psd_mockups', ['asset_id' => $this->assetId]);
            Log::error('Glass PSD mockup generation failed unexpectedly.', [
                'asset_id' => $this->assetId,
                'message' => $exception->getMessage(),
            ]);

            $this->dispatch('toast', type: 'error', title: 'Action failed!', message: 'Loi he thong khi render PSD mockup. Hay xem log de biet chi tiet.');
        } finally {
            $this->dispatch('glass-generation-finished');
        }
    }

    public bool $showClearMockupsConfirmation = false;

    public function requestClearPsdMockups(): void
    {
        $this->showClearMockupsConfirmation = true;
    }

    public function cancelClearPsdMockups(): void
    {
        $this->showClearMockupsConfirmation = false;
    }

    public function clearPsdMockups(): void
    {
        try {
            $asset = app(GlassService::class)->clearPsdMockups(auth()->user(), $this->assetId);
            app(ActivityLogService::class)->record(
                event: 'glass.psd_mockups_cleared',
                description: 'User deleted all generated Glass PSD mockups.',
                subject: $asset,
                properties: ['item_number' => $asset->item_number],
            );
            $this->showClearMockupsConfirmation = false;
            $this->dispatch('glass-product-design-updated')->to(ListGlass::class);
            $this->dispatch('glass-product-design-workflow-updated')->to(ListGlass::class);
            $this->dispatch('toast', type: 'success', title: 'Mockups deleted', message: 'Da xoa toan bo mockup cua item nay.');
        } catch (RuntimeException $exception) {
            $this->reportUserActionError($exception, 'glass.clear_psd_mockups', ['asset_id' => $this->assetId]);
            $this->dispatch('toast', type: 'error', title: 'Action failed!', message: $exception->getMessage());
        }
    }
    public function toggleApproval(): void
    {
        try {
            $asset = app(GlassService::class)->toggleApproval(auth()->user(), $this->assetId);
            $message = $asset->is_approved ? 'Da duyet item.' : 'Da bo duyet item.';
            app(ActivityLogService::class)->record(
                event: $asset->is_approved ? 'glass.item_approved' : 'glass.item_unapproved',
                description: $asset->is_approved ? 'User approved Glass item.' : 'User unapproved Glass item.',
                subject: $asset,
                properties: ['item_number' => $asset->item_number],
            );

            $this->dispatch('glass-product-design-approval-updated')->to(ListGlass::class);
            $this->dispatch('glass-product-design-approval-updated')->to(GlassStatusPanel::class);
            $this->dispatch('glass-counts-updated')->to(ListGlass::class);
            $this->dispatch('glass-counts-updated')->to(GlassStatusPanel::class);
            $this->dispatch('toast', type: 'success', title: 'Successfully saved!', message: $message);
        } catch (RuntimeException $exception) {
            $this->reportUserActionError($exception, 'glass.toggle_approval', ['asset_id' => $this->assetId]);
            $this->dispatch('toast', type: 'error', title: 'Action failed!', message: $exception->getMessage());
        }
    }

    #[On('psd-mockup-template-updated')]
    public function refreshWhenPsdTemplateUpdated(): void
    {
        $this->activePsdTemplateName = app(PsdMockupTemplateService::class)
            ->activeGlassTemplateForUser(auth()->user())?->name;
    }

    public function refreshMockups(): void
    {
        $this->mockupAutoRefreshCount = 0;
    }

    public function refreshMockupsAutomatically(): void
    {
        $asset = app(GlassService::class)->assetForUser(auth()->user(), $this->assetId);
        $job = app(GlassService::class)->latestLocalMockupJob($asset);
        if ($job?->status === 'completed') {
            $this->mockupAutoRefreshCount = min(2, $this->mockupAutoRefreshCount + 1);
        }
    }

    public function render(): View
    {
        $asset = app(GlassService::class)->assetForUser(auth()->user(), $this->assetId);
        $localMockupJob = app(GlassService::class)->latestLocalMockupJob($asset);
        $this->appendPreviewUrls($asset, $this->mockupPreviewVersion($localMockupJob));

        return view('livewire.pages.glass.product-design-card', [
            'asset' => $asset,
            'localMockupJob' => $localMockupJob,
            'glassBoundsGuideUrl' => $this->glassBoundsGuideUrl(),
        ]);
    }

    private function glassBoundsGuideUrl(): ?string
    {
        $disk = Storage::disk('public');
        $path = 'admin/glass/bounds-guide.png';

        if (! $disk->exists($path)) {
            return null;
        }

        return route('image-preview.show', [
            'path' => '/storage/'.$path,
            'v' => $disk->lastModified($path),
        ], false);
    }

    private function appendPreviewUrls(ProductDesignAsset $asset, ?string $mockupPreviewVersion = null): void
    {
        $imagePreview = app(ImageLinkPreviewService::class);

        $asset->setAttribute('image_preview_url', $imagePreview->previewUrl($asset->image_link));
        $asset->setAttribute('redesign_preview_url', $imagePreview->previewUrl($asset->redesign));
        $asset->setAttribute('redesign_gallery', collect($asset->redesign_candidates ?: [])
            ->push($asset->redesign)
            ->filter()
            ->unique()
            ->values()
            ->map(fn (string $url, int $index): array => [
                'src' => $imagePreview->previewUrl($url),
                'original' => $url,
                'title' => 'Create Master '.($index + 1),
            ])
            ->all());

        for ($slot = 1; $slot <= 11; $slot++) {
            $previewUrl = $imagePreview->previewUrl($asset->{"mockup{$slot}"});
            $asset->setAttribute("mockup{$slot}_preview_url", $this->withPreviewVersion($previewUrl, $mockupPreviewVersion));
        }
    }

    public function awaitingMockupFiles(ProductDesignAsset $asset, ?\App\Models\GlassLocalMockupJob $job): bool
    {
        if ($job?->status !== 'completed' || ! $job->completed_at || $job->completed_at->lt(now()->subMinutes(5))) {
            return false;
        }

        foreach (range(1, 11) as $slot) {
            $url = $asset->{"mockup{$slot}"};
            if (! is_string($url) || ! str_starts_with($url, '/storage/')) {
                continue;
            }

            $path = parse_url($url, PHP_URL_PATH) ?: '';
            if (! is_file(public_path(ltrim($path, '/')))) {
                return true;
            }
        }

        return false;
    }

    private function mockupPreviewVersion(?\App\Models\GlassLocalMockupJob $job): ?string
    {
        if ($job?->status !== 'completed' || ! $job->completed_at) {
            return null;
        }

        // Keep polling briefly after local completion so files that are still syncing become visible without a page reload.
        return (string) $job->completed_at->timestamp;
    }

    private function withPreviewVersion(?string $url, ?string $version = null): ?string
    {
        if (! $url || ! $version) {
            return $url;
        }

        // Laravel signed preview URLs already include the file mtime as `v`.
        // Appending a query after signing invalidates the signature and causes
        // freshly rendered mockups to show as broken images.
        if (str_contains($url, 'signature=')) {
            return $url;
        }

        return $url.(str_contains($url, '?') ? '&' : '?').'mockup_job='.$version;
    }
}
