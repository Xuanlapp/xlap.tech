<?php

namespace App\Services\Dashboard;

use App\Models\Product;
use App\Models\ProductDesignAsset;
use App\Models\User;
use App\Support\ProductRegistry;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DashboardStatsService
{
    public function build(User $viewer, ?int $selectedUserId = null, ?string $selectedProductSlug = null, ?string $month = null): array
    {
        $isPrivileged = $this->isPrivileged($viewer);
        $ownerId = $isPrivileged ? $selectedUserId : $viewer->id;
        $availableUsers = $isPrivileged
            ? User::query()->whereNull('deleted_at')->orderBy('name')->get(['id', 'name', 'email', 'role', 'status'])
            : collect([$viewer]);
        $owner = $ownerId ? ($availableUsers->firstWhere('id', $ownerId) ?? $viewer) : null;

        $visibleProducts = $this->visibleProducts($viewer, $owner);
        $selectedProduct = $selectedProductSlug ? $visibleProducts->firstWhere('slug', $selectedProductSlug) : null;
        $monthOptions = $this->monthOptions($viewer, $owner?->id, $selectedProduct?->id);
        $validMonthValues = $monthOptions->pluck('value');
        $allMonthsSelected = strtolower(trim((string) $month)) === 'all';
        $requestedMonth = $validMonthValues->contains($month) ? $month : null;
        $selectedMonth = $this->normalizeMonth($requestedMonth, $monthOptions->first()['value'] ?? null);
        $previousMonth = $selectedMonth->subMonth();

        $currentQuery = $allMonthsSelected
            ? $this->allTimeAssetQuery($viewer, $owner?->id, $selectedProduct?->id)
            : $this->baseAssetQuery($viewer, $owner?->id, $selectedProduct?->id, $selectedMonth);
        $previousQuery = $this->baseAssetQuery($viewer, $owner?->id, $selectedProduct?->id, $previousMonth);

        return [
            'isPrivileged' => $isPrivileged,
            'selectedMonth' => $selectedMonth,
            'selectedMonthValue' => $allMonthsSelected ? 'all' : $selectedMonth->format('Y-m'),
            'selectedUserId' => $owner?->id,
            'selectedProductSlug' => $selectedProduct?->slug,
            'availableUsers' => $availableUsers,
            'visibleProducts' => $visibleProducts,
            'monthOptions' => $monthOptions,
            'overviewCards' => $allMonthsSelected
                ? $this->allTimeOverviewCards($viewer, $owner?->id, $selectedProduct?->id)
                : $this->overviewCards($viewer, $selectedMonth, $previousMonth, $owner?->id, $selectedProduct?->id),
            'productCards' => $allMonthsSelected
                ? $this->allTimeProductCards($viewer, $owner?->id, $visibleProducts)
                : $this->productCards($viewer, $owner?->id, $selectedMonth, $previousMonth, $visibleProducts),
            'monthlySeries' => $this->monthlySeries($viewer, $owner?->id, $selectedProduct?->id, 12),
            'yearlySeries' => $this->yearlySeries($viewer, $owner?->id, $selectedProduct?->id, now()->year),
            'topUsers' => $isPrivileged ? ($allMonthsSelected ? $this->topUsersAllTime($owner?->id, $selectedProduct?->id) : $this->topUsers($selectedMonth, $selectedProduct?->id)) : collect(),
            'currentTotals' => $this->totals($currentQuery),
            'previousTotals' => $this->totals($previousQuery),
            'allTimeTotals' => $this->allTimeTotals($viewer, $owner?->id, $selectedProduct?->id),
            'allTimeProductCards' => $this->allTimeProductCards($viewer, $owner?->id, $visibleProducts),
        ];
    }

    private function isPrivileged(User $viewer): bool
    {
        return (bool) ($viewer->is_admin || $viewer->role === 'admin' || $viewer->isManager());
    }

    private function visibleProducts(User $viewer, ?User $owner): Collection
    {
        $sortMap = collect(ProductRegistry::all())->pluck('sort_order', 'slug');
        $pageSlugs = ['decal', 'suncatcher', 'ornament-etsy', 'ornament-amazon-2', 'sticker', 'proxy'];

        if ($owner) {
            $products = $owner->products()->where('is_active', true)->whereIn('slug', $pageSlugs)->orderBy('name')->get();
        } elseif ($this->isPrivileged($viewer)) {
            $products = Product::query()->where('is_active', true)->whereIn('slug', $pageSlugs)->orderBy('name')->get();
        } else {
            $products = $viewer->products()->where('is_active', true)->whereIn('slug', $pageSlugs)->orderBy('name')->get();
        }

        return $products
            ->sortBy(fn (Product $product) => [($sortMap[$product->slug] ?? 9999), mb_strtolower((string) $product->name)])
            ->values();
    }

    private function normalizeMonth(?string $month, ?string $fallbackMonth): CarbonImmutable
    {
        $candidate = is_string($month) && preg_match('/^\d{4}-\d{2}$/', $month) ? $month : $fallbackMonth;

        if (is_string($candidate) && preg_match('/^\d{4}-\d{2}$/', $candidate)) {
            return CarbonImmutable::createFromFormat('Y-m', $candidate)->startOfMonth();
        }

        return CarbonImmutable::now()->startOfMonth();
    }

    private function allTimeAssetQuery(User $viewer, ?int $ownerId, ?int $productId): Builder
    {
        return ProductDesignAsset::query()
            ->when(! $this->isPrivileged($viewer), fn (Builder $query) => $query->where('user_id', $viewer->id))
            ->when($this->isPrivileged($viewer) && $ownerId, fn (Builder $query) => $query->where('user_id', $ownerId))
            ->when($productId, fn (Builder $query) => $query->where('product_id', $productId));
    }

    private function yearAssetQuery(User $viewer, ?int $ownerId, ?int $productId, int $year): Builder
    {
        return ProductDesignAsset::query()
            ->when(! $this->isPrivileged($viewer), fn (Builder $query) => $query->where('user_id', $viewer->id))
            ->when($this->isPrivileged($viewer) && $ownerId, fn (Builder $query) => $query->where('user_id', $ownerId))
            ->when($productId, fn (Builder $query) => $query->where('product_id', $productId))
            ->whereYear('created_at', $year);
    }

    private function baseAssetQuery(User $viewer, ?int $ownerId, ?int $productId, CarbonImmutable $month): Builder
    {
        return ProductDesignAsset::query()
            ->when(! $this->isPrivileged($viewer), fn (Builder $query) => $query->where('user_id', $viewer->id))
            ->when($this->isPrivileged($viewer) && $ownerId, fn (Builder $query) => $query->where('user_id', $ownerId))
            ->when($productId, fn (Builder $query) => $query->where('product_id', $productId))
            ->whereBetween('created_at', [$month->startOfMonth()->toDateTimeString(), $month->endOfMonth()->toDateTimeString()]);
    }

    private function allTimeOverviewCards(User $viewer, ?int $ownerId, ?int $productId): array
    {
        $totals = $this->allTimeTotals($viewer, $ownerId, $productId);

        return array_values(array_filter([
            $this->isPrivileged($viewer) ? ['label' => 'Users', 'value' => User::query()->whereNull('deleted_at')->count(), 'note' => 'Total system users', 'delta' => null, 'tone' => 'slate'] : null,
            ['label' => 'Pending', 'value' => $totals['pending'], 'note' => 'All months since launch', 'delta' => null, 'tone' => 'amber'],
            ['label' => 'Approved', 'value' => $totals['approved'], 'note' => 'All months since launch', 'delta' => null, 'tone' => 'emerald'],
            ['label' => 'Total Items', 'value' => $totals['total'], 'note' => 'All months since launch', 'delta' => null, 'tone' => 'blue'],
        ]));
    }

    private function overviewCards(User $viewer, CarbonImmutable $selectedMonth, CarbonImmutable $previousMonth, ?int $ownerId, ?int $productId): array
    {
        $currentTotal = $this->baseAssetQuery($viewer, $ownerId, $productId, $selectedMonth)->count();
        $previousTotal = $this->baseAssetQuery($viewer, $ownerId, $productId, $previousMonth)->count();
        $currentApproved = $this->baseAssetQuery($viewer, $ownerId, $productId, $selectedMonth)->where('is_approved', true)->count();
        $previousApproved = $this->baseAssetQuery($viewer, $ownerId, $productId, $previousMonth)->where('is_approved', true)->count();
        $currentPending = $this->baseAssetQuery($viewer, $ownerId, $productId, $selectedMonth)->where(function (Builder $query): void {
            $query->where('is_approved', false)->orWhereNull('is_approved');
        })->count();
        $previousPending = $this->baseAssetQuery($viewer, $ownerId, $productId, $previousMonth)->where(function (Builder $query): void {
            $query->where('is_approved', false)->orWhereNull('is_approved');
        })->count();

        return array_values(array_filter([
            $this->isPrivileged($viewer) ? [
                'label' => 'Users',
                'value' => User::query()->whereNull('deleted_at')->count(),
                'note' => 'Total system users',
                'delta' => null,
                'tone' => 'slate',
            ] : null,
            [
                'label' => 'Pending',
                'value' => $currentPending,
                'note' => 'Need review in '.$selectedMonth->format('m/Y'),
                'delta' => $this->percentChange($currentPending, $previousPending),
                'tone' => 'amber',
            ],
            [
                'label' => 'Approved',
                'value' => $currentApproved,
                'note' => 'Completed in month',
                'delta' => $this->percentChange($currentApproved, $previousApproved),
                'tone' => 'emerald',
            ],
            [
                'label' => 'Total Items',
                'value' => $currentTotal,
                'note' => 'Pending + approved',
                'delta' => $this->percentChange($currentTotal, $previousTotal),
                'tone' => 'blue',
            ],
        ]));
    }

    private function allTimeTotals(User $viewer, ?int $ownerId, ?int $productId): array
    {
        return $this->totals($this->allTimeAssetQuery($viewer, $ownerId, $productId));
    }

    private function yearTotals(User $viewer, ?int $ownerId, ?int $productId, int $year): array
    {
        return $this->totals($this->yearAssetQuery($viewer, $ownerId, $productId, $year));
    }

    private function allTimeProductCards(User $viewer, ?int $ownerId, Collection $visibleProducts): array
    {
        return $visibleProducts->map(function (Product $product) use ($viewer, $ownerId): array {
            $totals = $this->allTimeTotals($viewer, $ownerId, $product->id);

            return [
                'name' => $product->name,
                'slug' => $product->slug,
                'users' => $this->productUsersCount($viewer, $product),
                ...$totals,
            ];
        })->values()->all();
    }

    private function productCards(User $viewer, ?int $ownerId, CarbonImmutable $selectedMonth, CarbonImmutable $previousMonth, Collection $visibleProducts): array
    {
        return $visibleProducts->map(function (Product $product) use ($viewer, $ownerId, $selectedMonth, $previousMonth): array {
            $currentTotal = $this->baseAssetQuery($viewer, $ownerId, $product->id, $selectedMonth)->count();
            $previousTotal = $this->baseAssetQuery($viewer, $ownerId, $product->id, $previousMonth)->count();
            $approved = $this->baseAssetQuery($viewer, $ownerId, $product->id, $selectedMonth)->where('is_approved', true)->count();
            $pending = $this->baseAssetQuery($viewer, $ownerId, $product->id, $selectedMonth)->where(function (Builder $query): void {
                $query->where('is_approved', false)->orWhereNull('is_approved');
            })->count();
            $uploaded = $this->baseAssetQuery($viewer, $ownerId, $product->id, $selectedMonth)->whereNotNull('drive_uploaded_at')->count();

            return [
                'name' => $product->name,
                'slug' => $product->slug,
                'users' => $this->productUsersCount($viewer, $product),
                'total' => $currentTotal,
                'pending' => $pending,
                'approved' => $approved,
                'uploaded' => $uploaded,
                'delta' => $this->percentChange($currentTotal, $previousTotal),
            ];
        })->values()->all();
    }

    private function yearlySeries(User $viewer, ?int $ownerId, ?int $productId, int $year): array
    {
        $query = ProductDesignAsset::query()
            ->selectRaw('MONTH(created_at) as month, COUNT(*) as total, SUM(CASE WHEN is_approved = 1 THEN 1 ELSE 0 END) as approved')
            ->whereYear('created_at', $year)
            ->when($this->isPrivileged($viewer) && $ownerId, fn (Builder $query) => $query->where('user_id', $ownerId))
            ->when(! $this->isPrivileged($viewer), fn (Builder $query) => $query->where('user_id', $viewer->id))
            ->when($productId, fn (Builder $query) => $query->where('product_id', $productId))
            ->groupByRaw('MONTH(created_at)')
            ->get()
            ->keyBy(fn (object $row): int => (int) $row->month);

        return collect(range(1, 12))->map(function (int $month) use ($query, $year): array {
            $row = $query->get($month);
            $total = (int) ($row->total ?? 0);
            $approved = (int) ($row->approved ?? 0);

            return [
                'label' => sprintf('%02d/%04d', $month, $year),
                'total' => $total,
                'approved' => $approved,
                'pending' => max(0, $total - $approved),
            ];
        })->all();
    }

    private function monthlySeries(User $viewer, ?int $ownerId, ?int $productId, int $limit): array
    {
        $query = ProductDesignAsset::query()
            ->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as total, SUM(CASE WHEN is_approved = 1 THEN 1 ELSE 0 END) as approved')
            ->groupByRaw('YEAR(created_at), MONTH(created_at)')
            ->orderByRaw('YEAR(created_at) desc, MONTH(created_at) desc');

        if ($this->isPrivileged($viewer) && $ownerId) {
            $query->where('user_id', $ownerId);
        } elseif (! $this->isPrivileged($viewer)) {
            $query->where('user_id', $viewer->id);
        }

        if ($productId) {
            $query->where('product_id', $productId);
        }

        return $query->limit($limit)->get()->reverse()->values()->map(function (object $row): array {
            $total = (int) $row->total;
            $approved = (int) $row->approved;

            return [
                'label' => sprintf('%02d/%04d', $row->month, $row->year),
                'total' => $total,
                'approved' => $approved,
                'pending' => max(0, $total - $approved),
            ];
        })->all();
    }

    private function topUsersAllTime(?int $ownerId, ?int $productId, ?int $year = null): Collection
    {
        return ProductDesignAsset::query()
            ->selectRaw('user_id, COUNT(*) as total, SUM(CASE WHEN is_approved = 1 THEN 1 ELSE 0 END) as approved')
            ->with('user:id,name')
            ->when($ownerId, fn (Builder $query) => $query->where('user_id', $ownerId))
            ->when($productId, fn (Builder $query) => $query->where('product_id', $productId))
            ->when($year, fn (Builder $query) => $query->whereYear('created_at', $year))
            ->groupBy('user_id')->orderByDesc('total')->limit(8)->get()
            ->map(fn (ProductDesignAsset $row) => ['user' => $row->user?->name ?? ('User #'.$row->user_id), 'total' => (int) $row->total, 'approved' => (int) $row->approved, 'pending' => max(0, (int) $row->total - (int) $row->approved)]);
    }

    private function topUsers(CarbonImmutable $month, ?int $productId): Collection
    {
        return ProductDesignAsset::query()
            ->selectRaw('user_id, COUNT(*) as total, SUM(CASE WHEN is_approved = 1 THEN 1 ELSE 0 END) as approved')
            ->with('user:id,name')
            ->whereBetween('created_at', [$month->startOfMonth()->toDateTimeString(), $month->endOfMonth()->toDateTimeString()])
            ->when($productId, fn (Builder $query) => $query->where('product_id', $productId))
            ->groupBy('user_id')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(fn (ProductDesignAsset $row) => [
                'user' => $row->user?->name ?? ('User #'.$row->user_id),
                'total' => (int) ($row->getAttribute('total') ?? 0),
                'approved' => (int) ($row->getAttribute('approved') ?? 0),
                'pending' => max(0, (int) ($row->getAttribute('total') ?? 0) - (int) ($row->getAttribute('approved') ?? 0)),
            ]);
    }

    private function monthOptions(User $viewer, ?int $ownerId, ?int $productId): Collection
    {
        $query = ProductDesignAsset::query();

        if ($this->isPrivileged($viewer) && $ownerId) {
            $query->where('user_id', $ownerId);
        } elseif (! $this->isPrivileged($viewer)) {
            $query->where('user_id', $viewer->id);
        }

        if ($productId) {
            $query->where('product_id', $productId);
        }

        $options = $query
            ->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month')
            ->distinct()
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get()
            ->map(fn (object $row) => [
                'value' => sprintf('%04d-%02d', $row->year, $row->month),
                'label' => sprintf('%02d/%04d', $row->month, $row->year),
            ]);

        return collect([['value' => 'all', 'label' => 'All']])->concat($options)->values();
    }

    private function totals(Builder $query): array
    {
        $total = (clone $query)->count();
        $approved = (clone $query)->where('is_approved', true)->count();

        return [
            'total' => $total,
            'pending' => max(0, $total - $approved),
            'approved' => $approved,
            'uploaded' => (clone $query)->whereNotNull('drive_uploaded_at')->count(),
        ];
    }

    private function productUsersCount(User $viewer, Product $product): int
    {
        if ($this->isPrivileged($viewer)) {
            return $product->users()->count();
        }

        return $viewer->products->contains('id', $product->id) ? 1 : 0;
    }

    private function percentChange(int $current, int $previous): ?string
    {
        if ($previous === 0) {
            return $current === 0 ? null : 'New';
        }

        $delta = (($current - $previous) / $previous) * 100;
        $prefix = $delta >= 0 ? '+' : '';

        return $prefix.round($delta, 1).'%';
    }
}
