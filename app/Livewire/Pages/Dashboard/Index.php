<?php

namespace App\Livewire\Pages\Dashboard;

use App\Services\Dashboard\DashboardStatsService;
use App\Models\ListingPromptOverride;
use App\Models\Product;
use App\Support\ProductRegistry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Livewire\Attributes\Session;
use Livewire\Component;

class Index extends Component
{
    #[Session(key: 'dashboard.selected-month')]
    public ?string $selectedMonth = null;

    #[Session(key: 'dashboard.selected-user')]
    public ?int $selectedUserId = null;

    #[Session(key: 'dashboard.selected-product')]
    public ?string $selectedProductSlug = null;

    public function mount(): void
    {
        $user = auth()->user();

        if ($user && (bool) $user->can_access_wali && ! ((bool) $user->is_admin || $user->role === 'admin' || $user->isManager())) {
            $this->redirectRoute('offorest.salary.wali', navigate: true);

            return;
        }

        $this->selectedMonth ??= now()->format('Y-m');
    }

    public function updatedSelectedMonth(?string $value): void
    {
        $this->selectedMonth = strtolower(trim((string) $value)) === 'all'
            ? 'all'
            : (preg_match('/^\d{4}-\d{2}$/', (string) $value) ? $value : now()->format('Y-m'));
    }

    public function updatedSelectedUserId(?int $value): void
    {
        $this->selectedUserId = $value ?: null;
    }

    public function updatedSelectedProductSlug(?string $value): void
    {
        $this->selectedProductSlug = $value ?: null;
    }

    public function render(DashboardStatsService $service): View
    {
        $data = $service->build(
            auth()->user(),
            $this->selectedUserId,
            $this->selectedProductSlug,
            $this->selectedMonth,
        );

        $this->selectedMonth = $data['selectedMonthValue'];
        $this->selectedUserId = $data['selectedUserId'];
        $this->selectedProductSlug = $data['selectedProductSlug'];

        $data['missingListingPrompts'] = [];
        if (auth()->user()?->isSuperAdmin() || auth()->user()?->role === 'admin' || auth()->user()?->is_admin) {
            $listingSlugs = collect(ProductRegistry::all())->reject(fn (array $product): bool => in_array($product['slug'], ['camp', 'proxy', 'ytrends', 'idea-amazon', 'idea-etsy'], true))->pluck('slug')->all();
            $products = Product::query()->whereIn('slug', $listingSlugs)->where('is_active', true)->get(['id', 'name', 'slug']);
            foreach ($products as $product) {
                foreach (['amazon', 'etsy'] as $marketplace) {
                    if (! ListingPromptOverride::query()->where('product_id', $product->id)->where('marketplace', $marketplace)->whereNotNull('content')->where('content', '<>', '')->exists()) {
                        $data['missingListingPrompts'][] = $product->name.' / '.ucfirst($marketplace);
                    }
                }
            }
        }

        return view('livewire.pages.dashboard.index', $data)->layout('layouts.app');
    }
}
