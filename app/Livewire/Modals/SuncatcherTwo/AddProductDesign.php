<?php

namespace App\Livewire\Modals\SuncatcherTwo;

use App\Livewire\Pages\SuncatcherTwo\ListSuncatcherTwo;
use App\Livewire\Pages\SuncatcherTwo\SuncatcherTwoStatusPanel;
use App\Models\SuncatcherTwoDraft;
use App\Services\Image\ImageLinkPreviewService;
use App\Services\Prompt\PromptService;
use App\Services\SuncatcherTwo\SuncatcherTwoService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Throwable;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class AddProductDesign extends Component
{
    use WithFileUploads;

    public int $workflowStep = 1;
    public array $personPrompts = ['a' => '', 'b' => ''];
    public array $personUrls = ['a' => '', 'b' => ''];
    public $personAUpload;
    public $personBUpload;
    public bool $isOpen = false;
    public bool $embedded = false;
    public string $keyword = '';
    public string $imageLink = '';
    public ?bool $isImageLink = null;
    public array $competitorListing = [];
    public string $selectedImageUrl = '';
    public ?string $scrapeError = null;
    public int $step = 1;
    public bool $researchLoading = false;
    public bool $scrapeLoading = false;
    public bool $selectionLocked = false;
    public array $researchResults = [];
    public array $selectedResearchUrls = [];
    public array $scrapedCompetitors = [];
    #[\Livewire\Attributes\Reactive]
    public ?string $providerKey = null;
    #[\Livewire\Attributes\Reactive]
    public ?string $textModel = null;
    #[\Livewire\Attributes\Reactive]
    public ?string $imageModel = null;
    public array $draftWorkflow = [];
    public string $mainImagePrompt = '';
    public string $sourceImageUrl = '';
    public string $mainImageUrl = '';
    public string $draftKey = '';

    public function mount(bool $embedded = false, ?string $providerKey = null, ?string $textModel = null, ?string $imageModel = null): void
    {
        $this->embedded = $embedded;
        $this->isOpen = $embedded;
        $this->providerKey = $providerKey;
        $this->textModel = $textModel;
        $this->imageModel = $imageModel;
        $this->workflowStep = max(1, min(2, (int) request()->query('step', 1)));
        $savedDraft = auth()->check() && Schema::hasTable('product_data_search')
            ? SuncatcherTwoDraft::query()->where('user_id', auth()->id())->where('status', 'draft')->latest('updated_at')->first()
            : null;
        $this->draftKey = $savedDraft?->draft_key ?: (string) Str::uuid();
        if ($savedDraft) $this->restoreDraft($savedDraft->payload);
    }

    #[On('openModal')]
    public function openModal(string $component, array $arguments = []): void
    {
        if ($component === 'modals.suncatcher-two.add-product-design') {
            $this->open();
        }
    }

    #[On('open-add-product-design')]
    public function open(): void
    {
        $this->resetValidation();
        $this->resetExcept('providerKey', 'textModel', 'imageModel');
        $this->isOpen = true;
        $this->embedded = false;
    }

    #[On('open-suncatcher-two-add-inline')]
    public function openInline(): void
    {
        $this->resetValidation();
        $this->resetExcept('providerKey', 'textModel', 'imageModel');
        $this->embedded = true;
        $this->isOpen = true;
    }

    public function close(): void
    {
        $this->resetValidation();
        $this->resetExcept('providerKey', 'textModel', 'imageModel');
        $this->embedded = true;
        $this->isOpen = true;
    }

    public function persistDraft(array $draft): void
    {
        if (! auth()->check() || ! Schema::hasTable('product_data_search')) return;
        $this->draftKey = $this->draftKey !== '' ? $this->draftKey : (string) Str::uuid();
        SuncatcherTwoDraft::updateOrCreate(
            ['user_id' => auth()->id(), 'draft_key' => $this->draftKey],
            ['workflow_step' => max(1, min(2, (int) ($draft['workflowStep'] ?? 1))), 'status' => 'draft', 'payload' => $draft],
        );
    }

    public function restoreDraft(array $draft): void
    {
        $this->keyword = (string) ($draft['keyword'] ?? '');
        $this->step = (int) ($draft['step'] ?? 1);
        $this->researchResults = is_array($draft['researchResults'] ?? null) ? $draft['researchResults'] : [];
        $this->selectedResearchUrls = is_array($draft['selectedResearchUrls'] ?? null) ? array_values($draft['selectedResearchUrls']) : [];
        $this->scrapedCompetitors = is_array($draft['scrapedCompetitors'] ?? null) ? $draft['scrapedCompetitors'] : [];
        $this->selectionLocked = $this->scrapedCompetitors !== [];
        $this->competitorListing = is_array($draft['competitorListing'] ?? null) ? $draft['competitorListing'] : [];
        $this->imageLink = (string) ($draft['imageLink'] ?? '');
        $this->selectedImageUrl = (string) ($draft['selectedImageUrl'] ?? $this->imageLink);
        $this->sourceImageUrl = (string) ($draft['sourceImageUrl'] ?? '');
        $this->mainImageUrl = (string) ($draft['mainImageUrl'] ?? '');
        $this->mainImagePrompt = (string) ($draft['mainImagePrompt'] ?? '');
        $this->workflowStep = max(1, min(3, (int) ($draft['workflowStep'] ?? 1)));
        foreach (['a', 'b'] as $person) {
            $this->personPrompts[$person] = is_string($draft['personPrompts'][$person] ?? null) ? $draft['personPrompts'][$person] : '';
            $this->personUrls[$person] = is_string($draft['personUrls'][$person] ?? null) ? $draft['personUrls'][$person] : '';
        }
        $workflow = is_array($draft['draftWorkflow'] ?? null) ? $draft['draftWorkflow'] : [];
        $script = is_array($workflow['script'] ?? null) ? $workflow['script'] : [];
        $workflow['script'] = array_filter($script, 'is_string');
        $this->draftWorkflow = $workflow;
        $this->scrapeLoading = false;
        $this->researchLoading = false;
    }
    public function selectResearchUrl(string $url): void
    {
        if (in_array($url, $this->selectedResearchUrls, true)) {
            $this->selectedResearchUrls = array_values(array_diff($this->selectedResearchUrls, [$url]));
        } elseif (count($this->selectedResearchUrls) < 5) {
            $this->selectedResearchUrls[] = $url;
        }
    }

    public function setResearchResults(array $results): void
    {
        $this->researchLoading = false;
        $this->researchResults = array_values(array_slice($results, 0, 20));
        $this->selectedResearchUrls = [];
        if ($this->researchResults === []) {
            $this->addError('keyword', 'Extension khong tra ve ket qua research.');
        }
    }

    public function backToResearch(): void
    {
        $this->step = 1;
        $this->scrapeLoading = false;
    }

    public function continueToScrape(): void
    {
        if (count($this->selectedResearchUrls) !== 5) {
            $this->addError('keyword', 'Hay chon dung 5 doi thu.');
            return;
        }
        $this->resetValidation();
        $this->step = 2;
        $this->selectionLocked = true;
        $this->scrapedCompetitors = [];
        $this->competitorListing = [];
        $this->imageLink = '';
        $this->selectedImageUrl = '';
        $this->scrapeError = null;
        $this->scrapeLoading = true;
    }


    public function editSelection(): void
    {
        $this->selectionLocked = false;
        $this->step = 1;
        $this->scrapedCompetitors = [];
        $this->competitorListing = [];
        $this->imageLink = '';
        $this->selectedImageUrl = '';
        $this->scrapeError = null;
        $this->dispatch('suncatcher-two-scrape-data-cleared');
    }

    public function setError(string $message): void
    {
        $this->researchLoading = false;
        $this->scrapeLoading = false;
        $this->scrapeError = $message;
        $this->addError('keyword', $message);
    }

    public function setScrapedResults(array $jobs): void
    {
        $this->scrapeLoading = false;
        $done = array_values(array_filter($jobs, fn (array $job): bool => ($job['status'] ?? '') === 'done'));
        if ($done === []) {
            $this->addError('keyword', 'Chua scrape duoc doi thu nao, vui long thu lai.');
            return;
        }
        $this->scrapeError = count($done) === 5 ? null : 'Da scrape du '.count($done).'/5 doi thu.';
        $this->resetErrorBag('keyword');
        $this->scrapedCompetitors = $done;
        $first = $done[0]['result'] ?? [];
        $images = [];
        foreach ($done as $job) {
            $listing = $job['result'] ?? [];
            $images = array_merge($images, [$listing['mainImage'] ?? null], (array) ($listing['images'] ?? []));
        }
        $images = array_values(array_filter(array_unique($images), 'is_string'));
        $this->competitorListing = [
            'platform' => $first['platform'] ?? 'amazon',
            'productTitle' => $first['title'] ?? $first['productTitle'] ?? $this->keyword,
            'link' => $first['url'] ?? '',
            'images' => $images,
            'bulletPoints' => $first['bulletPoints'] ?? [],
            'productDescription' => $first['description'] ?? $first['productDescription'] ?? '',
            'competitors' => array_values(array_map(fn (array $job): array => (array) ($job['result'] ?? []), $done)),
        ];
        $this->imageLink = $images[0] ?? '';
        $this->selectedImageUrl = $this->imageLink;
    }

    public function selectPrimaryImage(string $url): void
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) return;
        $this->selectedImageUrl = $url;
        $this->imageLink = $url;
    }

    public function saveAndGenerateData(): void
    {
        $lock = Cache::lock('suncatcher-two:generate-script-draft:'.auth()->id(), 120);
        if (! $lock->get()) return;
        try {
            $validated = $this->validate(['keyword' => ['required', 'string', 'max:255'], 'imageLink' => ['required', 'url', 'max:1000']]);
            $result = app(SuncatcherTwoService::class)->generateWorkflowScriptDraft(auth()->user(), $validated['keyword'], $validated['imageLink'], $this->competitorListing, $this->providerKey, $this->textModel);
            $this->draftWorkflow = $result['workflow'];
            $this->dispatch('toast', type: 'success', title: 'Script ready', message: 'Da tao draft B1, chua tao item.');
        } catch (Throwable $exception) { $this->addError('keyword', $exception->getMessage()); }
        finally { $lock->release(); }
    }

    public function createItemFromDraft(): void
    {
        $validated = $this->validate(['keyword' => ['required', 'string', 'max:255'], 'imageLink' => ['required', 'url', 'max:1000']]);
        try {
            $service = app(SuncatcherTwoService::class);
            $asset = $service->createAsset(auth()->user(), $validated['keyword'], $validated['imageLink'], array_values(array_diff($this->competitorListing['images'] ?? [], [$validated['imageLink']])), $this->competitorListing);
            if ($this->draftWorkflow !== []) $service->attachWorkflowDraft(auth()->user(), $asset->id, $this->draftWorkflow);
            $this->dispatch('product-design-created')->to(ListSuncatcherTwo::class);
            $this->dispatch('product-design-created')->to(SuncatcherTwoStatusPanel::class);
            $this->dispatch('toast', type: 'success', title: 'Item created', message: 'Da tao item va luu draft B1.');
            $this->dispatch('suncatcher-two-draft-clear'); $this->close();
        } catch (Throwable $exception) { $this->addError('keyword', $exception->getMessage()); }
    }
    public function updatedImageLink(): void
    {
        $this->isImageLink = $this->imageLink === '' ? null : app(ImageLinkPreviewService::class)->looksLikeImageUrl($this->imageLink);
    }

    public function nextWorkflowStep(): void
    {
        if (empty($this->draftWorkflow['script'])) return;
        foreach (['a', 'b'] as $person) {
            if (trim($this->personPrompts[$person] ?? '') === '') {
                $this->personPrompts[$person] = $this->draftWorkflow['b2']["person_{$person}_prompt"] ?? $this->draftWorkflow['script']["person_{$person}"] ?? '';
            }
        }
        $this->workflowStep = 2;
    }

    public function previousWorkflowStep(): void
    {
        $this->workflowStep = max(1, $this->workflowStep - 1);
    }

    public function nextToMainImages(): void
    {
        if ($this->workflowStep !== 2 || empty($this->personUrls['a']) || empty($this->personUrls['b'])) return;
        $this->sourceImageUrl = $this->selectedImageUrl ?: $this->imageLink;
        $this->mainImagePrompt = (string) ($this->draftWorkflow['b3']['main_prompt'] ?? app(PromptService::class)->promptsForUserProduct(auth()->user(), 'suncatcher-two')->first()?->content ?? 'Create a clean product main image using the selected source image.');
        $this->workflowStep = 2;
    }

    public function generateMainImage(): void
    {
        $this->validate(['mainImagePrompt' => ['required', 'string', 'max:5000'], 'sourceImageUrl' => ['required', 'string', 'max:2000']]);
        $this->mainImageUrl = app(SuncatcherTwoService::class)->generateWorkflowMainImageDraft(auth()->user(), $this->sourceImageUrl, $this->mainImagePrompt, $this->providerKey, $this->imageModel);
        $this->draftWorkflow['b3'] = ['source_image' => $this->sourceImageUrl, 'main_prompt' => $this->mainImagePrompt, 'main_image' => $this->mainImageUrl];
    }
    public function generatePersonDraft(string $person): void
    {
        if (! in_array($person, ['a', 'b'], true)) return;
        $this->validate(["personPrompts.$person" => ['required', 'string', 'max:5000']]);
        try {
            $url = app(SuncatcherTwoService::class)->generateWorkflowPersonDraft(auth()->user(), $person, $this->personPrompts[$person], $this->providerKey, $this->imageModel);
            $this->personUrls[$person] = $url;
            $this->draftWorkflow['b2']["person_{$person}_ref"] = $url;
            $this->draftWorkflow['b2']["person_{$person}_prompt"] = $this->personPrompts[$person];
        } catch (Throwable $exception) { $this->addError("personPrompts.$person", $exception->getMessage()); }
    }

    public function savePersonUrl(string $person): void
    {
        if (! in_array($person, ['a', 'b'], true)) return;
        $oldUrl = (string) ($this->draftWorkflow['b2']["person_{$person}_ref"] ?? '');
        $newUrl = trim((string) ($this->personUrls[$person] ?? ''));
        if ($newUrl === '') {
            if (str_starts_with($oldUrl, '/storage/generated/suncatcher-two/drafts/'.auth()->id().'/refs/')) {
                Storage::disk('public')->delete(ltrim(str_replace('/storage/', '', $oldUrl), '/'));
            }
            $this->personUrls[$person] = '';
            $this->draftWorkflow['b2']["person_{$person}_ref"] = '';
            return;
        }
        $this->validate(["personUrls.$person" => ['url', 'max:2000']]);
        if ($oldUrl !== $newUrl && str_starts_with($oldUrl, '/storage/generated/suncatcher-two/drafts/'.auth()->id().'/refs/')) {
            Storage::disk('public')->delete(ltrim(str_replace('/storage/', '', $oldUrl), '/'));
        }
        $this->personUrls[$person] = $newUrl;
        $this->draftWorkflow['b2']["person_{$person}_ref"] = $newUrl;
    }
    public function updatedPersonAUpload(): void { $this->savePersonUpload('a'); }
    public function updatedPersonBUpload(): void { $this->savePersonUpload('b'); }

    private function savePersonUpload(string $person): void
    {
        $property = $person === 'a' ? 'personAUpload' : 'personBUpload';
        $this->validate([$property => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240']]);
        $oldUrl = (string) ($this->personUrls[$person] ?? '');
        $path = $this->{$property}->store('generated/suncatcher-two/drafts/'.auth()->id().'/refs', 'public');
        if (str_starts_with($oldUrl, '/storage/generated/suncatcher-two/drafts/'.auth()->id().'/refs/')) {
            Storage::disk('public')->delete(ltrim(str_replace('/storage/', '', $oldUrl), '/'));
        }
        $url = '/storage/'.$path;
        $this->personUrls[$person] = $url;
        $this->draftWorkflow['b2']["person_{$person}_ref"] = $url;
        $this->reset($property);
    }

    public function save(): void
    {
        $validated = $this->validate(['keyword' => ['required', 'string', 'max:255'], 'imageLink' => ['required', 'url', 'max:1000']]);
        app(SuncatcherTwoService::class)->createAsset(auth()->user(), $validated['keyword'], $validated['imageLink'], array_values(array_diff($this->competitorListing['images'] ?? [], [$validated['imageLink']])), $this->competitorListing);
        $this->dispatch('product-design-created')->to(ListSuncatcherTwo::class);
        $this->dispatch('product-design-created')->to(SuncatcherTwoStatusPanel::class);
        $this->dispatch('toast', type: 'success', title: 'Successfully saved!', message: 'Da them item Suncatcher 2 moi.');
        $this->dispatch('suncatcher-two-draft-clear');
        $this->close();
    }

    public function render(): View
    {
        return view('livewire.modals.suncatcher-two.add-product-design');
    }
}



