<?php

namespace App\Livewire\Pages\Decal;

use App\Livewire\Concerns\ReportsUserActionErrors;
use App\Models\ProductDesignAsset;
use App\Services\Image\ImageLinkPreviewService;
use App\Services\Logging\ActivityLogService;
use App\Services\Decal\PsdMockupTemplateService;
use App\Services\Decal\DecalService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
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

    #[Reactive]
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

    #[On('decal-product-design-updated')]
    public function refreshWhenUpdated(int $assetId): void
    {
        if ($assetId !== $this->assetId) {
            return;
        }
    }

    public function generateRedesign(): void
    {
        try {
            $asset = app(DecalService::class)->generateRedesign(auth()->user(), $this->assetId, $this->providerKey, $this->imageModel);
            app(ActivityLogService::class)->record(
                event: 'decal.master_generated',
                description: 'User generated Decal master image.',
                subject: $asset,
                properties: ['item_number' => $asset->item_number, 'redesign' => $asset->redesign],
            );

            $this->dispatch('toast', type: 'success', title: 'Successfully saved!', message: 'Da tao anh master.');
        } catch (RuntimeException $exception) {
            $this->reportUserActionError($exception, 'decal.generate_redesign', ['asset_id' => $this->assetId]);
            $this->dispatch('toast', type: 'error', title: 'Action failed!', message: $exception->getMessage());
        } catch (Throwable $exception) {
            $this->reportUserActionError($exception, 'decal.generate_redesign', ['asset_id' => $this->assetId]);
            Log::error('Decal master generation failed unexpectedly.', [
                'asset_id' => $this->assetId,
                'message' => $exception->getMessage(),
            ]);

            $this->dispatch('toast', type: 'error', title: 'Action failed!', message: 'Loi he thong khi tao anh master. Hay xem log de biet chi tiet.');
        } finally {
            $this->dispatch('decal-generation-finished');
        }
    }

    public function generatePsdMockups(): void
    {
        try {
            $asset = app(DecalService::class)->generatePsdMockups(auth()->user(), $this->assetId);
            app(ActivityLogService::class)->record(
                event: 'decal.psd_mockups_generated',
                description: 'User rendered Decal PSD mockups.',
                subject: $asset,
                properties: ['item_number' => $asset->item_number],
            );

            $this->dispatch('toast', type: 'success', title: 'Da vao hang doi!', message: 'May local se render mockup va tu dong cap nhat khi xong.');
        } catch (RuntimeException $exception) {
            $this->reportUserActionError($exception, 'decal.generate_psd_mockups', ['asset_id' => $this->assetId]);
            $this->dispatch('toast', type: 'error', title: 'Action failed!', message: $exception->getMessage());
        } catch (Throwable $exception) {
            $this->reportUserActionError($exception, 'decal.generate_psd_mockups', ['asset_id' => $this->assetId]);
            Log::error('Decal PSD mockup generation failed unexpectedly.', [
                'asset_id' => $this->assetId,
                'message' => $exception->getMessage(),
            ]);

            $this->dispatch('toast', type: 'error', title: 'Action failed!', message: 'Loi he thong khi render PSD mockup. Hay xem log de biet chi tiet.');
        } finally {
            $this->dispatch('decal-generation-finished');
        }
    }

    public function toggleApproval(): void
    {
        try {
            $asset = app(DecalService::class)->toggleApproval(auth()->user(), $this->assetId);
            $message = $asset->is_approved ? 'Da duyet item.' : 'Da bo duyet item.';
            app(ActivityLogService::class)->record(
                event: $asset->is_approved ? 'decal.item_approved' : 'decal.item_unapproved',
                description: $asset->is_approved ? 'User approved Decal item.' : 'User unapproved Decal item.',
                subject: $asset,
                properties: ['item_number' => $asset->item_number],
            );

            $this->dispatch('decal-product-design-approval-updated')->to(ListDecal::class);
            $this->dispatch('decal-product-design-approval-updated')->to(DecalStatusPanel::class);
            $this->dispatch('decal-counts-updated')->to(ListDecal::class);
            $this->dispatch('decal-counts-updated')->to(DecalStatusPanel::class);
            $this->dispatch('toast', type: 'success', title: 'Successfully saved!', message: $message);
        } catch (RuntimeException $exception) {
            $this->reportUserActionError($exception, 'decal.toggle_approval', ['asset_id' => $this->assetId]);
            $this->dispatch('toast', type: 'error', title: 'Action failed!', message: $exception->getMessage());
        }
    }

    #[On('psd-mockup-template-updated')]
    public function refreshWhenPsdTemplateUpdated(): void
    {
        $this->activePsdTemplateName = app(PsdMockupTemplateService::class)
            ->activeDecalTemplateForUser(auth()->user())?->name;
    }

    public function refreshMockups(): void
    {
    }

    public function refreshMockupsAutomatically(): void
    {
        $asset = app(DecalService::class)->assetForUser(auth()->user(), $this->assetId);
        $job = app(DecalService::class)->latestLocalMockupJob($asset);
        if ($job?->status === 'completed') {
            $this->mockupAutoRefreshCount = min(2, $this->mockupAutoRefreshCount + 1);
        }
    }

    public function render(): View
    {
        $asset = app(DecalService::class)->assetForUser(auth()->user(), $this->assetId);
        $localMockupJob = app(DecalService::class)->latestLocalMockupJob($asset);
        $this->appendPreviewUrls($asset, $this->mockupPreviewVersion($localMockupJob));

        return view('livewire.pages.decal.product-design-card', [
            'asset' => $asset,
            'localMockupJob' => $localMockupJob,
        ]);
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
            $asset->setAttribute("mockup{$slot}_preview_url", $this->withPreviewVersion($previewUrl));
        }
    }

    private function mockupPreviewVersion(?\App\Models\GlassLocalMockupJob $job): ?string
    {
        if ($job?->status !== 'completed' || ! $job->completed_at) {
            return null;
        }

        // Keep polling briefly after local completion so files that are still syncing become visible without a page reload.
        return (string) $job->completed_at->timestamp;
    }

    private function withPreviewVersion(?string $url, ?string $version): ?string
    {
        if (! $url || ! $version) {
            return $url;
        }

        return $url;
    }
}
