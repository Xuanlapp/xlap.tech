<?php

namespace App\Livewire\Pages\Ceramic;

use App\Livewire\Concerns\ReportsUserActionErrors;
use App\Models\ProductDesignAsset;
use App\Services\Image\ImageLinkPreviewService;
use App\Services\Logging\ActivityLogService;
use App\Services\Ceramic\PsdMockupTemplateService;
use App\Services\Ceramic\CeramicService;
use App\Services\Ceramic\CeramicBoundsGuideStorage;
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

    #[On('ceramic-product-design-updated')]
    public function refreshWhenUpdated(int $assetId): void
    {
        if ($assetId !== $this->assetId) {
            return;
        }
    }

    public function generateRedesign(): void
    {
        try {
            $asset = app(CeramicService::class)->generateRedesign(auth()->user(), $this->assetId, $this->providerKey, $this->imageModel);
            app(ActivityLogService::class)->record(
                event: 'ceramic.master_generated',
                description: 'User generated Ceramic master image.',
                subject: $asset,
                properties: ['item_number' => $asset->item_number, 'redesign' => $asset->redesign],
            );

            $this->dispatch('toast', type: 'success', title: 'Successfully saved!', message: 'Da tao anh master.');
        } catch (RuntimeException $exception) {
            $this->reportUserActionError($exception, 'ceramic.generate_redesign', ['asset_id' => $this->assetId]);
            $this->dispatch('toast', type: 'error', title: 'Action failed!', message: $exception->getMessage());
        } catch (Throwable $exception) {
            $this->reportUserActionError($exception, 'ceramic.generate_redesign', ['asset_id' => $this->assetId]);
            Log::error('Ceramic master generation failed unexpectedly.', [
                'asset_id' => $this->assetId,
                'message' => $exception->getMessage(),
            ]);

            $this->dispatch('toast', type: 'error', title: 'Action failed!', message: 'Loi he thong khi tao anh master. Hay xem log de biet chi tiet.');
        } finally {
            $this->dispatch('ceramic-generation-finished');
        }
    }

    public function generatePsdMockups(): void
    {
        try {
            $asset = app(CeramicService::class)->generatePsdMockups(auth()->user(), $this->assetId);
            $this->mockupAutoRefreshCount = 0;
            app(ActivityLogService::class)->record(
                event: 'ceramic.psd_mockups_generated',
                description: 'User rendered Ceramic PSD mockups.',
                subject: $asset,
                properties: ['item_number' => $asset->item_number],
            );

            $this->dispatch('toast', type: 'success', title: 'Da vao hang doi!', message: 'May local se render mockup va tu dong cap nhat khi xong.');
        } catch (RuntimeException $exception) {
            $this->reportUserActionError($exception, 'ceramic.generate_psd_mockups', ['asset_id' => $this->assetId]);
            $this->dispatch('toast', type: 'error', title: 'Action failed!', message: $exception->getMessage());
        } catch (Throwable $exception) {
            $this->reportUserActionError($exception, 'ceramic.generate_psd_mockups', ['asset_id' => $this->assetId]);
            Log::error('Ceramic PSD mockup generation failed unexpectedly.', [
                'asset_id' => $this->assetId,
                'message' => $exception->getMessage(),
            ]);

            $this->dispatch('toast', type: 'error', title: 'Action failed!', message: 'Loi he thong khi render PSD mockup. Hay xem log de biet chi tiet.');
        } finally {
            $this->dispatch('ceramic-generation-finished');
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
            $asset = app(CeramicService::class)->clearPsdMockups(auth()->user(), $this->assetId);
            app(ActivityLogService::class)->record(
                event: 'ceramic.psd_mockups_cleared',
                description: 'User deleted all generated Ceramic PSD mockups.',
                subject: $asset,
                properties: ['item_number' => $asset->item_number],
            );
            $this->showClearMockupsConfirmation = false;
            $this->dispatch('ceramic-product-design-updated')->to(ListCeramic::class);
            $this->dispatch('ceramic-product-design-workflow-updated')->to(ListCeramic::class);
            $this->dispatch('toast', type: 'success', title: 'Mockups deleted', message: 'Da xoa toan bo mockup cua item nay.');
        } catch (RuntimeException $exception) {
            $this->reportUserActionError($exception, 'ceramic.clear_psd_mockups', ['asset_id' => $this->assetId]);
            $this->dispatch('toast', type: 'error', title: 'Action failed!', message: $exception->getMessage());
        }
    }
    public function toggleApproval(): void
    {
        try {
            $asset = app(CeramicService::class)->toggleApproval(auth()->user(), $this->assetId);
            $message = $asset->is_approved ? 'Da duyet item.' : 'Da bo duyet item.';
            app(ActivityLogService::class)->record(
                event: $asset->is_approved ? 'ceramic.item_approved' : 'ceramic.item_unapproved',
                description: $asset->is_approved ? 'User approved Ceramic item.' : 'User unapproved Ceramic item.',
                subject: $asset,
                properties: ['item_number' => $asset->item_number],
            );

            $this->dispatch('ceramic-product-design-approval-updated')->to(ListCeramic::class);
            $this->dispatch('toast', type: 'success', title: 'Successfully saved!', message: $message);
        } catch (RuntimeException $exception) {
            $this->reportUserActionError($exception, 'ceramic.toggle_approval', ['asset_id' => $this->assetId]);
            $this->dispatch('toast', type: 'error', title: 'Action failed!', message: $exception->getMessage());
        }
    }

    #[On('psd-mockup-template-updated')]
    public function refreshWhenPsdTemplateUpdated(): void
    {
        $this->activePsdTemplateName = app(PsdMockupTemplateService::class)
            ->activeCeramicTemplateForUser(auth()->user())?->name;
    }

    public function refreshMockups(): void
    {
        $this->mockupAutoRefreshCount = 0;
    }

    public function refreshMockupsAutomatically(): void
    {
        $asset = app(CeramicService::class)->assetForUser(auth()->user(), $this->assetId);
        $job = app(CeramicService::class)->latestLocalMockupJob($asset);
        if ($job?->status === 'completed') {
            $this->mockupAutoRefreshCount = min(2, $this->mockupAutoRefreshCount + 1);
        }
    }

    public function render(): View
    {
        $asset = app(CeramicService::class)->assetForUser(auth()->user(), $this->assetId);
        $localMockupJob = app(CeramicService::class)->latestLocalMockupJob($asset);
        $this->appendPreviewUrls($asset, $this->mockupPreviewVersion($localMockupJob));

        return view('livewire.pages.ceramic.product-design-card', [
            'asset' => $asset,
            'localMockupJob' => $localMockupJob,
            'ceramicBoundsGuideUrl' => $this->ceramicBoundsGuideUrl(),
        ]);
    }

    private function ceramicBoundsGuideUrl(): ?string
    {
        return app(CeramicBoundsGuideStorage::class)->url(auth()->user());
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

    public function awaitingMockupFiles(ProductDesignAsset $asset, ?\App\Models\CeramicLocalMockupJob $job): bool
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

    private function mockupPreviewVersion(?\App\Models\CeramicLocalMockupJob $job): ?string
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


