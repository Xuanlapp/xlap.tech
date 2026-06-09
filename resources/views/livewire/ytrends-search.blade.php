<div class="space-y-6">
    <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <form wire:submit.prevent="search" class="flex flex-col gap-3 lg:flex-row lg:items-end">
            <div class="min-w-0 flex-1">
                <label for="ytrends-keyword" class="text-sm font-medium text-slate-600">Keyword</label>
                <input
                    id="ytrends-keyword"
                    type="text"
                    wire:model.defer="keyword"
                    placeholder="Nhap keyword Etsy..."
                    class="mt-1 w-full rounded-md border-slate-300 bg-white text-slate-950"
                >
            </div>

            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="search"
                class="inline-flex items-center justify-center gap-2 rounded-md bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700 disabled:cursor-wait disabled:opacity-70"
            >
                <svg wire:loading wire:target="search" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"></path>
                </svg>
                <span wire:loading.remove wire:target="search">Analyze</span>
                <span wire:loading wire:target="search">Loading...</span>
            </button>
        </form>

        @error('keyword')
            <div class="mt-3 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{{ $message }}</div>
        @enderror

        @if ($error)
            <div class="mt-3 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                {{ $error }}
            </div>
        @endif
    </div>

    <div wire:loading.flex wire:target="search" class="items-center gap-3 rounded-lg border border-cyan-200 bg-cyan-50 px-4 py-3 text-sm font-semibold text-cyan-800">
        <svg class="h-5 w-5 animate-spin" viewBox="0 0 24 24" aria-hidden="true">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"></path>
        </svg>
        Dang tai du lieu tu YTrends MCP...
    </div>

    @if ($data)
        @php
            $stats = data_get($data, 'data.stats', []);
            $scores = data_get($data, 'scores', []);
            $insights = collect(data_get($data, 'insights', []));
            $relatedKeywords = collect(data_get($data, 'data.related_keywords', []));
            $relatedKeywordColumns = $relatedKeywords
                ->flatMap(fn ($row) => is_array($row) ? array_keys($row) : [])
                ->unique()
                ->values();
            $topListings = collect(data_get($data, 'data.top_listings', []));
            $timeline = collect(data_get($data, 'data.timeline', []))->take(14);
            $snapshot = data_get($data, 'snapshot', []);
            $formatValue = function ($value) {
                if ($value === null || $value === '') {
                    return '-';
                }

                if (is_bool($value)) {
                    return $value ? 'Yes' : 'No';
                }

                if (is_array($value)) {
                    return json_encode($value, JSON_UNESCAPED_UNICODE);
                }

                if (is_numeric($value)) {
                    return is_float($value + 0)
                        ? rtrim(rtrim(number_format((float) $value, 4), '0'), '.')
                        : number_format((int) $value);
                }

                return $value;
            };
        @endphp

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase text-slate-500">Keyword</p>
                <p class="mt-2 text-2xl font-bold text-slate-950">{{ data_get($stats, 'keyword', $searchedKeyword) }}</p>
                <p class="mt-1 text-xs text-slate-400">As of {{ data_get($snapshot, 'as_of_date', '-') }}</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase text-slate-500">Opportunity</p>
                <p class="mt-2 text-2xl font-bold text-cyan-700">{{ $formatValue(data_get($scores, 'opportunity', data_get($stats, 'opportunity_score'))) }}</p>
                <p class="mt-1 text-xs text-slate-400">{{ data_get($stats, 'opportunity_grade', '-') }} | {{ data_get($stats, 'recommended_action', '-') }}</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase text-slate-500">Competition</p>
                <p class="mt-2 text-2xl font-bold text-slate-950">{{ data_get($stats, 'competition_level', '-') }}</p>
                <p class="mt-1 text-xs text-slate-400">Listings {{ $formatValue(data_get($stats, 'total_listings')) }}</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase text-slate-500">Revenue</p>
                <p class="mt-2 text-2xl font-bold text-emerald-700">${{ $formatValue(data_get($stats, 'total_revenue')) }}</p>
                <p class="mt-1 text-xs text-slate-400">Avg price ${{ $formatValue(data_get($stats, 'avg_price')) }}</p>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                <h2 class="font-semibold text-slate-950">Keyword metrics</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-white text-left text-slate-500">
                        <tr>
                            <th class="px-4 py-3 font-medium">Rank</th>
                            <th class="px-4 py-3 font-medium">Tier</th>
                            <th class="px-4 py-3 font-medium">Sellers</th>
                            <th class="px-4 py-3 font-medium">Avg price</th>
                            <th class="px-4 py-3 font-medium">Conversion</th>
                            <th class="px-4 py-3 font-medium">Sold 24h</th>
                            <th class="px-4 py-3 font-medium">Views 24h</th>
                            <th class="px-4 py-3 font-medium">Price range</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <tr>
                            <td class="px-4 py-4 font-semibold text-slate-950">{{ $formatValue(data_get($stats, 'rank')) }}</td>
                            <td class="px-4 py-4">{{ $formatValue(data_get($stats, 'tier')) }}</td>
                            <td class="px-4 py-4">{{ $formatValue(data_get($stats, 'total_sellers')) }}</td>
                            <td class="px-4 py-4">${{ $formatValue(data_get($stats, 'avg_price')) }}</td>
                            <td class="px-4 py-4">{{ $formatValue((float) data_get($stats, 'avg_conversion_rate', 0) * 100) }}%</td>
                            <td class="px-4 py-4">{{ $formatValue(data_get($stats, 'avg_sold_24h')) }}</td>
                            <td class="px-4 py-4">{{ $formatValue(data_get($stats, 'avg_views_24h')) }}</td>
                            <td class="px-4 py-4">{{ data_get($stats, 'recommended_price_range', '-') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        @if ($insights->isNotEmpty())
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-semibold text-slate-950">Insights</h2>
                <div class="mt-4 grid gap-3 lg:grid-cols-2">
                    @foreach ($insights as $insight)
                        <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm leading-6 text-slate-700">
                            {{ $insight }}
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($relatedKeywords->isNotEmpty())
            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                    <h2 class="font-semibold text-slate-950">Related keywords</h2>
                    <p class="mt-1 text-xs text-slate-500">Showing {{ $relatedKeywords->count() }} keywords and all fields returned by YTrends.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-white text-left text-slate-500">
                            <tr>
                                @foreach ($relatedKeywordColumns as $column)
                                    <th class="whitespace-nowrap px-4 py-3 font-medium">{{ str($column)->replace('_', ' ')->title() }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @foreach ($relatedKeywords as $keywordRow)
                                <tr>
                                    @foreach ($relatedKeywordColumns as $column)
                                        <td class="max-w-sm px-4 py-4 align-top {{ $column === 'tag' || $column === 'keyword' ? 'font-semibold text-slate-950' : 'text-slate-700' }}">
                                            <span class="block break-words">{{ $formatValue(data_get($keywordRow, $column)) }}</span>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if ($topListings->isNotEmpty())
            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                    <h2 class="font-semibold text-slate-950">Top listings</h2>
                    <p class="mt-1 text-xs text-slate-500">Showing {{ $topListings->count() }} listings returned by YTrends.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-white text-left text-slate-500">
                            <tr>
                                <th class="px-4 py-3 font-medium">Listing</th>
                                <th class="px-4 py-3 font-medium">Shop</th>
                                <th class="px-4 py-3 font-medium">Price</th>
                                <th class="px-4 py-3 font-medium">Revenue</th>
                                <th class="px-4 py-3 font-medium">Sold 24h</th>
                                <th class="px-4 py-3 font-medium">Conversion</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @foreach ($topListings as $listing)
                                @php
                                    $listingId = data_get($listing, 'listing_id', data_get($listing, 'id'));
                                    $listingUrl = $listingId ? 'https://www.etsy.com/listing/'.$listingId : null;
                                    $imageUrl = data_get($listing, 'image_url');
                                @endphp
                                <tr>
                                    <td class="px-4 py-4">
                                        <div class="flex min-w-[360px] items-start gap-3">
                                            @if ($imageUrl)
                                                <img
                                                    src="{{ $imageUrl }}"
                                                    alt=""
                                                    class="h-16 w-16 rounded-md border border-slate-200 object-cover"
                                                    loading="lazy"
                                                    decoding="async"
                                                >
                                            @endif
                                            <div class="min-w-0">
                                                <p class="max-w-xl font-semibold text-slate-950">{{ data_get($listing, 'title', '-') }}</p>
                                                @if ($listingUrl)
                                                    <a href="{{ $listingUrl }}" target="_blank" rel="noopener" class="mt-1 block break-all text-xs font-semibold text-cyan-700 hover:text-cyan-900">
                                                        {{ $listingUrl }}
                                                    </a>
                                                @endif
                                                @if ($imageUrl)
                                                    <a href="{{ $imageUrl }}" target="_blank" rel="noopener" class="mt-1 block break-all text-xs text-slate-400 hover:text-slate-600">
                                                        Image: {{ $imageUrl }}
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">{{ data_get($listing, 'shop_name', data_get($listing, 'shop_id', '-')) }}</td>
                                    <td class="px-4 py-4">${{ $formatValue(data_get($listing, 'price_usd', data_get($listing, 'price'))) }}</td>
                                    <td class="px-4 py-4">${{ $formatValue(data_get($listing, 'revenue_usd', data_get($listing, 'total_revenue'))) }}</td>
                                    <td class="px-4 py-4">{{ $formatValue(data_get($listing, 'sold_24h')) }}</td>
                                    <td class="px-4 py-4">{{ $formatValue((float) data_get($listing, 'conversion_rate', 0) * 100) }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if ($timeline->isNotEmpty())
            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                    <h2 class="font-semibold text-slate-950">Recent timeline</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-white text-left text-slate-500">
                            <tr>
                                <th class="px-4 py-3 font-medium">Date</th>
                                <th class="px-4 py-3 font-medium">Listings</th>
                                <th class="px-4 py-3 font-medium">Sellers</th>
                                <th class="px-4 py-3 font-medium">Sold 24h</th>
                                <th class="px-4 py-3 font-medium">Views 24h</th>
                                <th class="px-4 py-3 font-medium">Revenue</th>
                                <th class="px-4 py-3 font-medium">Trend</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @foreach ($timeline as $day)
                                <tr>
                                    <td class="px-4 py-4 font-semibold text-slate-950">{{ data_get($day, 'snapshot_date', '-') }}</td>
                                    <td class="px-4 py-4">{{ $formatValue(data_get($day, 'listing_count')) }}</td>
                                    <td class="px-4 py-4">{{ $formatValue(data_get($day, 'seller_count')) }}</td>
                                    <td class="px-4 py-4">{{ $formatValue(data_get($day, 'total_sold_24h')) }}</td>
                                    <td class="px-4 py-4">{{ $formatValue(data_get($day, 'total_views_24h')) }}</td>
                                    <td class="px-4 py-4">${{ $formatValue(data_get($day, 'total_revenue_usd')) }}</td>
                                    <td class="px-4 py-4">{{ data_get($day, 'trend_direction', '-') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @endif
</div>
