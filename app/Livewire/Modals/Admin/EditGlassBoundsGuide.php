<?php

namespace App\Livewire\Modals\Admin;

use App\Services\Glass\GlassBoundsGuideAnalyzer;
use App\Services\Logging\ActivityLogService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class EditGlassBoundsGuide extends Component
{
    use WithFileUploads;

    public bool $isOpen = false;

    public $boundsGuide;

    public ?string $currentUrl = null;

    public ?array $currentConfig = null;

    #[On('openModal')]
    public function openModal(string $component, array $arguments = []): void
    {
        if ($component !== 'modals.admin.edit-glass-bounds-guide') {
            return;
        }

        abort_unless($this->isAdmin(), 403);
        $this->resetValidation();
        $this->reset('boundsGuide');
        $this->currentUrl = $this->guideUrl();
        $this->currentConfig = $this->guideConfig();
        $this->isOpen = true;
    }

    public function save(): void
    {
        abort_unless($this->isAdmin(), 403);

        $validated = $this->validate([
            'boundsGuide' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:10240'],
        ]);

        try {
            $result = app(GlassBoundsGuideAnalyzer::class)->analyze(
                file_get_contents($validated['boundsGuide']->getRealPath())
            );
        } catch (InvalidArgumentException $exception) {
            $this->addError('boundsGuide', $exception->getMessage());

            return;
        }
        $path = 'admin/glass/bounds-guide.png';
        $configPath = 'admin/glass/bounds-guide.json';
        $disk = Storage::disk('public');
        $disk->put($path, $result['png']);
        $disk->put($configPath, json_encode($result['config'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

        app(ActivityLogService::class)->record(
            event: 'admin.glass_bounds_guide_updated',
            description: 'Admin replaced the shared Glass bounds guide image.',
            properties: [
                'path' => $path,
                'config_path' => $configPath,
                'original_name' => $validated['boundsGuide']->getClientOriginalName(),
                'bounds' => $result['config'],
            ],
            actor: auth()->user(),
            actorType: 'admin',
        );

        $this->currentUrl = $this->guideUrl();
        $this->currentConfig = $result['config'];
        $this->dispatch('glass-bounds-guide-updated');
        $this->dispatch('toast', type: 'success', title: 'Successfully saved!', message: 'Da thay anh bounds Glass dung chung.');
        $this->close();
    }

    public function close(): void
    {
        $this->resetValidation();
        $this->reset(['isOpen', 'boundsGuide', 'currentUrl', 'currentConfig']);
    }

    public function render(): View
    {
        return view('livewire.modals.admin.edit-glass-bounds-guide');
    }

    private function isAdmin(): bool
    {
        return (bool) auth()->user()?->is_admin || auth()->user()?->role === 'admin';
    }

    private function guideUrl(): ?string
    {
        $disk = Storage::disk('public');
        $path = 'admin/glass/bounds-guide.png';
        if (! $disk->exists($path)) {
            return null;
        }

        return $disk->url($path).'?v='.((string) $disk->lastModified($path));
    }

    private function guideConfig(): ?array
    {
        $disk = Storage::disk('public');

        try {
            return app(GlassBoundsGuideAnalyzer::class)->loadOrCreate($disk);
        } catch (InvalidArgumentException) {
            return null;
        }
    }
}
