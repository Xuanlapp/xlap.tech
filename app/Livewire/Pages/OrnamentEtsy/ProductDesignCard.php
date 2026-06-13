<?php

namespace App\Livewire\Pages\OrnamentEtsy;

use App\Livewire\Concerns\ReportsUserActionErrors;
use App\Models\ProductDesignAsset;
use App\Services\Image\ImageLinkPreviewService;
use App\Services\Logging\ActivityLogService;
use App\Services\OrnamentEtsy\PsdMockupTemplateService;
use App\Services\OrnamentEtsy\OrnamentEtsyService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;
use RuntimeException;
use Throwable;

class ProductDesignCard extends Component
{
    use ReportsUserActionErrors;

    public int $assetId;

    public ?string $activePsdTemplateName = null;

    public function mount(int $assetId, ?string $activePsdTemplateName = null): void
    {
        $this->assetId = $assetId;
        $this->activePsdTemplateName = $activePsdTemplateName;
    }

    #[On('ornament-etsy-product-design-updated')]
    public function refreshWhenUpdated(int $assetId): void
    {
        if ($assetId !== $this->assetId) {
            return;
        }
    }

    public function generateRedesign(): void
    {
        try {
            $asset = app(OrnamentEtsyService::class)->generateRedesign(auth()->user(), $this->assetId);
            app(ActivityLogService::class)->record(
                event: 'ornament_etsy.master_generated',
                description: 'User generated Ornament Etsy master image.',
                subject: $asset,
                properties: ['item_number' => $asset->item_number, 'redesign' => $asset->redesign],
            );

            $this->dispatch('ornament-etsy-product-design-workflow-updated')->to(ListOrnamentEtsy::class);
            $this->dispatch('ornament-etsy-product-design-workflow-updated')->to(OrnamentEtsyStatusPanel::class);
            $this->dispatch('toast', type: 'success', title: 'Successfully saved!', message: 'Da tao anh master.');
        } catch (RuntimeException $exception) {
            $this->reportUserActionError($exception, 'ornament_etsy.generate_redesign', ['asset_id' => $this->assetId]);
            $this->dispatch('toast', type: 'error', title: 'Action failed!', message: $exception->getMessage());
        } catch (Throwable $exception) {
            $this->reportUserActionError($exception, 'ornament_etsy.generate_redesign', ['asset_id' => $this->assetId]);
            Log::error('Ornament Etsy master generation failed unexpectedly.', [
                'asset_id' => $this->assetId,
                'message' => $exception->getMessage(),
            ]);

            $this->dispatch('toast', type: 'error', title: 'Action failed!', message: 'Loi he thong khi tao anh master. Hay xem log de biet chi tiet.');
        } finally {
            $this->dispatch('ornament-etsy-generation-finished');
        }
    }

    public function generateFinalImages(): void
    {
        try {
            $asset = app(OrnamentEtsyService::class)->generateFinalImages(auth()->user(), $this->assetId);
            app(ActivityLogService::class)->record(
                event: 'ornament_etsy.lifestyle_generated',
                description: 'User generated Ornament Etsy lifestyle images.',
                subject: $asset,
                properties: ['item_number' => $asset->item_number],
            );

            $this->dispatch('toast', type: 'success', title: 'Successfully saved!', message: 'Da tao anh lifestyle va mockup.');
        } catch (RuntimeException $exception) {
            $this->reportUserActionError($exception, 'ornament_etsy.generate_final_images', ['asset_id' => $this->assetId]);
            $this->dispatch('toast', type: 'error', title: 'Action failed!', message: $exception->getMessage());
        } catch (Throwable $exception) {
            $this->reportUserActionError($exception, 'ornament_etsy.generate_final_images', ['asset_id' => $this->assetId]);
            Log::error('Ornament Etsy final image generation failed unexpectedly.', [
                'asset_id' => $this->assetId,
                'message' => $exception->getMessage(),
            ]);

            $this->dispatch('toast', type: 'error', title: 'Action failed!', message: 'Loi he thong khi tao anh lifestyle. Hay xem log de biet chi tiet.');
        } finally {
            $this->dispatch('ornament-etsy-generation-finished');
        }
    }

    public function generatePsdMockups(): void
    {
        try {
            $asset = app(OrnamentEtsyService::class)->generatePsdMockups(auth()->user(), $this->assetId);
            app(ActivityLogService::class)->record(
                event: 'ornament_etsy.psd_mockups_generated',
                description: 'User rendered Ornament Etsy PSD mockups.',
                subject: $asset,
                properties: ['item_number' => $asset->item_number],
            );

            $this->dispatch('toast', type: 'success', title: 'Successfully saved!', message: 'Da render PSD mockup.');
        } catch (RuntimeException $exception) {
            $this->reportUserActionError($exception, 'ornament_etsy.generate_psd_mockups', ['asset_id' => $this->assetId]);
            $this->dispatch('toast', type: 'error', title: 'Action failed!', message: $exception->getMessage());
        } catch (Throwable $exception) {
            $this->reportUserActionError($exception, 'ornament_etsy.generate_psd_mockups', ['asset_id' => $this->assetId]);
            Log::error('Ornament Etsy PSD mockup generation failed unexpectedly.', [
                'asset_id' => $this->assetId,
                'message' => $exception->getMessage(),
            ]);

            $this->dispatch('toast', type: 'error', title: 'Action failed!', message: 'Loi he thong khi render PSD mockup. Hay xem log de biet chi tiet.');
        } finally {
            $this->dispatch('ornament-etsy-generation-finished');
        }
    }

    public function toggleApproval(): void
    {
        try {
            $asset = app(OrnamentEtsyService::class)->toggleApproval(auth()->user(), $this->assetId);
            $message = $asset->is_approved ? 'Da duyet item.' : 'Da bo duyet item.';
            app(ActivityLogService::class)->record(
                event: $asset->is_approved ? 'ornament_etsy.item_approved' : 'ornament_etsy.item_unapproved',
                description: $asset->is_approved ? 'User approved Ornament Etsy item.' : 'User unapproved Ornament Etsy item.',
                subject: $asset,
                properties: ['item_number' => $asset->item_number],
            );

            $this->dispatch('ornament-etsy-product-design-approval-updated')->to(ListOrnamentEtsy::class);
            $this->dispatch('ornament-etsy-product-design-approval-updated')->to(OrnamentEtsyStatusPanel::class);
            $this->dispatch('toast', type: 'success', title: 'Successfully saved!', message: $message);
        } catch (RuntimeException $exception) {
            $this->reportUserActionError($exception, 'ornament_etsy.toggle_approval', ['asset_id' => $this->assetId]);
            $this->dispatch('toast', type: 'error', title: 'Action failed!', message: $exception->getMessage());
        }
    }

    #[On('ornament-etsy-psd-mockup-template-updated')]
    public function refreshWhenPsdTemplateUpdated(): void
    {
        $this->activePsdTemplateName = app(PsdMockupTemplateService::class)
            ->activeOrnamentTemplateForUser(auth()->user())?->name;
    }

    public function render(): View
    {
        $asset = app(OrnamentEtsyService::class)->assetForUser(auth()->user(), $this->assetId);
        $this->appendPreviewUrls($asset);

        return view('livewire.pages.ornament-etsy.product-design-card', [
            'asset' => $asset,
        ]);
    }

    private function appendPreviewUrls(ProductDesignAsset $asset): void
    {
        $imagePreview = app(ImageLinkPreviewService::class);

        $asset->setAttribute('image_preview_url', $imagePreview->previewUrl($asset->image_link));
        $asset->setAttribute('redesign_preview_url', $imagePreview->previewUrl($asset->redesign));
        $asset->setAttribute('lifestyle1_preview_url', $imagePreview->previewUrl($asset->lifestyle1));
        $asset->setAttribute('lifestyle2_preview_url', $imagePreview->previewUrl($asset->lifestyle2));
        $asset->setAttribute('lifestyle3_preview_url', $imagePreview->previewUrl($asset->lifestyle3));

        for ($slot = 1; $slot <= 11; $slot++) {
            $asset->setAttribute("mockup{$slot}_preview_url", $imagePreview->previewUrl($asset->{"mockup{$slot}"}));
        }
    }
}
