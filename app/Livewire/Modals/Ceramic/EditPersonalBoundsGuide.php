<?php

namespace App\Livewire\Modals\Ceramic;

use App\Services\Ceramic\CeramicBoundsGuideAnalyzer;
use App\Services\Ceramic\CeramicBoundsGuideStorage;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class EditPersonalBoundsGuide extends Component
{
    use WithFileUploads;

    public bool $isOpen = false;

    public $boundsGuide;

    public ?string $currentUrl = null;

    public ?string $newUrl = null;

    public ?string $guideUrl = null;

    public ?string $guideLink = null;

    public bool $usingPersonal = false;

    #[On('openModal')]
    public function openModal(string $component, array $arguments = []): void
    {
        if ($component !== 'modals.ceramic.edit-personal-bounds-guide') {
            return;
        }

        abort_unless(auth()->check(), 403);
        $this->resetValidation();
        $this->reset('boundsGuide');
        $guide = app(CeramicBoundsGuideStorage::class);
        $this->currentUrl = $guide->url(auth()->user());
        $this->guideUrl = $guide->sharedUrl();
        $this->guideLink = $guide->guideLink();
        $this->usingPersonal = $guide->paths(auth()->user())['personal'];
        $this->isOpen = true;
    }

    public function save(): void
    {
        abort_unless(auth()->check(), 403);

        $validated = $this->validate([
            'boundsGuide' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:10240'],
        ]);

        try {
            $result = app(CeramicBoundsGuideAnalyzer::class)->analyze(
                file_get_contents($validated['boundsGuide']->getRealPath())
            );
        } catch (InvalidArgumentException $exception) {
            $this->addError('boundsGuide', $exception->getMessage());

            return;
        }

        $disk = Storage::disk('public');
        $base = 'users/'.auth()->id().'/ceramic/bounds-guide';
        $temporaryPath = $base.'-'.bin2hex(random_bytes(6)).'.png';
        $disk->put($temporaryPath, $result['png']);
        try {
            $disk->put($base.'.json', json_encode($result['config'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
            if (! $disk->move($temporaryPath, $base.'.png')) {
                throw new \RuntimeException('Không thể lưu ảnh bounds. Vui lòng thử lại.');
            }
        } finally {
            $disk->delete($temporaryPath);
        }

        $this->dispatch('ceramic-bounds-guide-updated');
        $this->dispatch('toast', type: 'success', title: 'Đã lưu bounds', message: 'Ảnh bounds riêng của bạn đã thay ảnh cũ.');
        $this->close();
    }

    public function updatedBoundsGuide(): void
    {
        $this->resetValidation('boundsGuide');
        $this->newUrl = null;

        if ($this->boundsGuide) {
            $this->newUrl = $this->boundsGuide->temporaryUrl();
        }
    }

    public function close(): void
    {
        $this->resetValidation();
        $this->reset(['isOpen', 'boundsGuide', 'currentUrl', 'newUrl', 'guideUrl', 'guideLink', 'usingPersonal']);
    }

    public function render(): View
    {
        return view('livewire.modals.ceramic.edit-personal-bounds-guide');
    }
}


