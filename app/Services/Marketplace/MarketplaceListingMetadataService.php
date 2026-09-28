<?php

namespace App\Services\Marketplace;

use App\Models\ProductDesignAsset;
use App\Models\User;
use App\Models\UserApiCredential;
use App\Repositories\Product\ProductDesignAssetRepository;
use App\Services\Ai\ApiKeyImageGenerator;
use App\Services\Logging\ActivityLogService;
use App\Services\Vertex\VertexImageGenerator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;
use JsonException;
use RuntimeException;
use Throwable;

class MarketplaceListingMetadataService
{
    private const AMAZON_PROMPT_TEMPLATE = <<<'PROMPT'
Bạn hãy đóng vai một chuyên gia viết content Amazon chuyên nghiệp bằng tiếng Anh, chuyên tối ưu Amazon SEO, Title, Bullet Points, Generic Keywords và Product Description. Mục tiêu là viết nội dung dễ đọc, tự nhiên như người bản xứ, tăng tỷ lệ chuyển đổi, tránh keyword stuffing, tránh từ bị cấm, tránh claim quá đà, tránh dùng tên thương hiệu đối thủ, và tuân thủ chính sách Amazon.

Sản phẩm của tôi là: {amazon_product_from_sheet}

Link đối thủ để tham khảo cấu trúc, keyword, cách trình bày và insight khách hàng:
[LINK ĐỐI THỦ: {competitor_link}]

Danh sách keyword ưu tiên của tôi, hãy dùng theo thứ tự ưu tiên từ trên xuống dưới. Keyword nào ở trên thì ưu tiên đưa vào Title, Bullet Points, Generic Keywords và Product Description trước. Hãy dùng tối đa nhiều keyword nhất có thể nhưng phải tự nhiên, không spam, không lặp quá mức:
[KEYWORDS: {keyword_phrase}]

YÊU CẦU ĐẦU RA:

TITLE AMAZON

Viết 1 Title bằng tiếng Anh, tối ưu keyword, dễ đọc, tự nhiên, phù hợp Amazon US.

Yêu cầu bắt buộc:

* Độ dài Title nằm trong khoảng 180–195 ký tự tính cả dấu cách.
* Không được vượt quá 200 ký tự bao gồm cả dấu cách.
* Ưu tiên keyword chính ở đầu Title.
* Không nhồi keyword quá lộ.
* Không dùng ALL CAPS.
* Không dùng ký tự đặc biệt không cần thiết.
* Không dùng claim như Best, #1, Guaranteed, Official, Luxury nếu không có căn cứ.
* Không dùng tên thương hiệu đối thủ.
* Title phải mô tả rõ loại sản phẩm, điểm cá nhân hóa, đối tượng tặng quà và dịp sử dụng.

Sau Title, ghi rõ:
TITLE — [SỐ KÝ TỰ] CHARACTERS

BULLET POINTS AMAZON

Viết 5 Bullet Points bằng tiếng Anh.

Yêu cầu bắt buộc:

* Mỗi bullet point phải có độ dài từ 460–480 ký tự tính cả dấu cách.
* Không bullet nào được vượt quá 480 ký tự.
* Mỗi bullet có 1 icon phù hợp ở đầu dòng.
* Bullet point đầu tiên phải mô tả trực tiếp sản phẩm của tôi: sản phẩm là gì, dùng để làm gì, điểm cá nhân hóa chính.
* Các bullet còn lại phải tập trung vào lợi ích, tính năng, quà tặng, dịp sử dụng, cảm xúc, cách cá nhân hóa và giá trị lưu giữ kỷ niệm.
* Dùng keyword theo thứ tự ưu tiên từ danh sách tôi đưa.
* Keyword phải được đưa vào tự nhiên, không spam.
* Nội dung phải phù hợp với khách hàng Amazon US.
* Không dùng câu cam kết tuyệt đối như “will last forever”, “guaranteed to make them happy”, “best quality”.
* Không dùng từ bị cấm hoặc claim y tế, tôn giáo, chính trị, phân biệt đối tượng nếu không liên quan.
* Không dùng tên brand đối thủ hoặc trademark của người khác.

Sau mỗi bullet, ghi rõ:
BULLET POINT 1 — [SỐ KÝ TỰ] CHARACTERS
BULLET POINT 2 — [SỐ KÝ TỰ] CHARACTERS
BULLET POINT 3 — [SỐ KÝ TỰ] CHARACTERS
BULLET POINT 4 — [SỐ KÝ TỰ] CHARACTERS
BULLET POINT 5 — [SỐ KÝ TỰ] CHARACTERS

GENERIC KEYWORDS

Viết Generic Keywords theo đúng danh sách keyword tôi đưa, ưu tiên từ trên xuống dưới.

Yêu cầu bắt buộc:

* Các từ khóa cách nhau bằng dấu “;”.
* Độ dài Generic Keywords nằm trong khoảng 230–240 ký tự tính cả dấu cách thì dừng lại.
* Không được vượt quá 240 ký tự bao gồm cả dấu cách.
* Không thêm keyword nếu vượt giới hạn.
* Không dùng tên thương hiệu đối thủ.
* Không dùng ASIN.
* Không dùng từ sai chính tả nếu làm listing thiếu chuyên nghiệp.
* Không lặp lại một từ khóa quá nhiều nếu không cần thiết.
* Ưu tiên keyword có volume/search intent cao hơn.

Sau phần Generic Keywords, ghi rõ:
GENERIC KEYWORDS — [SỐ KÝ TỰ] CHARACTERS

PRODUCT DESCRIPTION

Viết Product Description bằng tiếng Anh.

Yêu cầu bắt buộc:

* Độ dài nằm trong khoảng 1800–1900 ký tự tính cả dấu cách.
* Không được vượt quá 2000 ký tự bao gồm cả dấu cách.
* Nội dung phải giàu cảm xúc, tự nhiên, dễ đọc, tăng chuyển đổi.
* Giải thích rõ sản phẩm là gì, dùng như thế nào, cá nhân hóa ra sao, phù hợp tặng ai, phù hợp dịp nào.
* Đưa nhiều keyword nhất có thể theo thứ tự ưu tiên từ danh sách tôi cung cấp, nhưng phải tự nhiên.
* Không lặp keyword quá dày.
* Không claim quá đà.
* Không dùng tên brand đối thủ.
* Không viết thông tin sai về chất liệu, kích thước, quy trình sản xuất nếu tôi chưa cung cấp.
* Nếu thông tin sản phẩm chưa rõ, hãy viết theo hướng an toàn, không khẳng định quá cụ thể.
* Tập trung vào cảm xúc: family memories, holiday tradition, meaningful keepsake, personalized gift, Christmas tree decor, loved ones, special moments.

Sau phần Product Description, ghi rõ:
PRODUCT DESCRIPTION — [SỐ KÝ TỰ] CHARACTERS

KIỂM TRA CUỐI CÙNG

Trước khi trả kết quả, hãy tự kiểm tra:

* Title có nằm trong 180–195 ký tự không?
* Title có vượt 200 ký tự không?
* Mỗi bullet có nằm trong 460–480 ký tự không?
* Có bullet nào vượt 480 ký tự không?
* Generic Keywords có nằm trong 230–240 ký tự không?
* Generic Keywords có vượt 240 ký tự không?
* Product Description có nằm trong 1800–1900 ký tự không?
* Product Description có vượt 2000 ký tự không?
* Có dùng tên thương hiệu đối thủ không?
* Có dùng claim quá đà hoặc từ có rủi ro chính sách không?
* Keyword có được dùng tự nhiên không?
* Nội dung có phù hợp khách hàng Amazon US không?

ĐỊNH DẠNG TRẢ KẾT QUẢ:

Trả kết quả theo đúng format sau, không giải thích dài dòng:

TITLE — [SỐ KÝ TỰ] CHARACTERS

[Title hoàn chỉnh]

BULLET POINT 1 — [SỐ KÝ TỰ] CHARACTERS

[Bullet 1]

BULLET POINT 2 — [SỐ KÝ TỰ] CHARACTERS

[Bullet 2]

BULLET POINT 3 — [SỐ KÝ TỰ] CHARACTERS

[Bullet 3]

BULLET POINT 4 — [SỐ KÝ TỰ] CHARACTERS

[Bullet 4]

BULLET POINT 5 — [SỐ KÝ TỰ] CHARACTERS

[Bullet 5]

GENERIC KEYWORDS — [SỐ KÝ TỰ] CHARACTERS

[Generic Keywords]

PRODUCT DESCRIPTION — [SỐ KÝ TỰ] CHARACTERS

[Product Description]

SAFETY CHECK

Brand/trademark risk: Passed / Needs Review
Amazon policy risk: Passed / Needs Review
Keyword stuffing risk: Low / Medium / High
Readability: Good / Needs Improvement

Return ONLY valid JSON. Do not include markdown, explanation, comments, character-count labels, safety-check text, or extra keys.

Required JSON schema, with exact keys:

{
"title": "string",
"description": "string",
"bullet_point_1": "string",
"bullet_point_2": "string",
"bullet_point_3": "string",
"bullet_point_4": "string",
"bullet_point_5": "string",
"generic_keyword": "string"
}

PROMPT;

    private const AMAZON_STICKER_PROMPT_TEMPLATE = <<<'PROMPT'
Ban hay dong vai dong vai mot chuyen gia viet content Amazon chuyen nghiep bang tieng anh, chuyen toi uu title, bullet points, description theo dung chuan SEO cua Amazon, tranh tu bi cam, dam bao tang ty le chuyen doi va tuan thu chinh sach.
San pham cua toi la: {amazon_product_from_sheet}
LINK DOI THU : {competitor_link}
KEYWORDS: {keyword_phrase}

Ban hay viet cho toi:
Title toi uu keyword, de doc, tuan thu do dai Amazon o cuoi tieu de co ( 3PCS,3") ( co do dai nam trong khoang 180-195 ky tu tinh ca dau cach, khong duoc vuot qua 200 ky tu bao gom ca dau cach, khong duoc lap lai tu stickers qua 2 lan )
Bullet Points (5 dong) ( moi bullet points phai co do dai nam trong khoang 460 den 480 ky tu tinh ca dau cach, khong duoc vuot qua 480 ky tu bao gom ca dau cach - mo ta loi ich va tinh nang san pham
+ Bullet point dau mo ta ve san pham cua toi
+ Co cac icon phu hop o dau cac bullet point
Generic Keyword : Ten sticker toi dua va khoang 5 den 8 tu ben duoi toi dua , theo thu tu uu tien tu tren xuong duoi cac tu cach nhau boi dau ; (Neu Generic Keyword co do dai nam trong khoang 230-240 ky tu tinh ca dau cach thi dung lai khong them cac tu o duoi nua ,Generic Keyword khong duoc vuot qua 240 ky tu bao gom ca dau cach)
Product Description ( co do dai nam trong khoang 1800 den 1900 ky tu tinh ca dau cach, khong duoc vuot qua 2000 ky tu bao gom ca dau cach ) - tang tinh cam xuc & giai thich chi tiet
Chu y so luong ky tu khong duoc vuot qua yeu cau cua toi, va so luong ky tu bao gom ca dau cach

Return ONLY valid JSON. Do not include markdown, explanation, comments, character-count labels, safety-check text, or extra keys.

Required JSON schema, with exact keys:
{
  "title": "string",
  "description": "string",
  "bullet_point_1": "string",
  "bullet_point_2": "string",
  "bullet_point_3": "string",
  "bullet_point_4": "string",
  "bullet_point_5": "string",
  "generic_keyword": "string"
}

Luu y:
- Dung dung san pham la sticker, khong viet theo kieu ornament.
- Khong duoc vuot qua so luong ky tu toi yeu cau, va so luong ky tu tinh ca dau cach.
- Su dung keyword tu nhien, uu tien theo thu tu da cung cap trong KEYWORDS.
- Khong nhac toi doi thu, khong dua ten thuong hieu doi thu vao noi dung tra ve.
PROMPT;
    private const ETSY_PROMPT_TEMPLATE = <<<'PROMPT'
You are an expert Etsy SEO listing copywriter.

Create marketplace metadata for the product keyword below.
Return ONLY valid JSON. Do not include markdown.

Keyword: "{keyword}"
Product page: "{product}"

Required JSON schema:
{
  "title": "Etsy SEO title, max 140 characters",
  "description": "Friendly Etsy product description, 1-2 paragraphs",
  "tags": "13 Etsy tags, comma-separated, each tag 20 characters or less"
}

Rules:
- Use natural US English.
- Focus on buyer intent, giftability, style, and product use.
- Do not mention Amazon, Midjourney, AI, or prompts.
- Do not include trademarked brands unless they are present in the keyword.
PROMPT;

    public function __construct(
        private readonly VertexImageGenerator $generator,
        private readonly ApiKeyImageGenerator $apiKeyGenerator,
        private readonly ProductDesignAssetRepository $assets,
    ) {}

    /**
     * Generate and persist listing metadata for the approved asset based on the owner's marketplace access.
     */
    public function generateForApprovedAsset(int $assetId): ?ProductDesignAsset
    {
        $asset = ProductDesignAsset::query()
            ->with(['user', 'product'])
            ->findOrFail($assetId);

        if (! $asset->is_approved) {
            return null;
        }

        if (in_array($asset->product?->slug, ['ornament-amazon-2', 'sticker', 'decal'], true)) {
            return $this->assets->markListingCompleted($this->generateAmazonMetadata($asset), 'amazon');
        }

        if ($asset->user->can_generate_amazon_listing) {
            return $this->assets->markListingCompleted($this->generateAmazonMetadata($asset), 'amazon');
        }

        if ($asset->user->can_generate_etsy_listing) {
            return $this->assets->markListingCompleted($this->generateEtsyMetadata($asset), 'etsy');
        }

        throw new RuntimeException('User chua bat quyen tao listing metadata Amazon hoac Etsy.');
    }

    public function retryApprovedAsset(int $assetId): ?ProductDesignAsset
    {
        $asset = ProductDesignAsset::query()
            ->with(['user', 'product'])
            ->findOrFail($assetId);

        if (! $asset->is_approved) {
            throw new RuntimeException('Item nay chua duyet nen khong the tao listing metadata.');
        }

        if ($asset->title) {
            return $asset;
        }

        $otherProcessing = ProductDesignAsset::query()
            ->where('user_id', $asset->user_id)
            ->where('id', '!=', $asset->id)
            ->where('marketplace_listing_status', 'processing')
            ->exists();

        if ($otherProcessing) {
            throw new RuntimeException('User nay dang co mot listing metadata running. Hay doi item hien tai hoan tat roi thu lai.');
        }

        $processing = $this->assets->markListingProcessing($asset, $this->marketplaceForAsset($asset));

        try {
            return $this->generateForApprovedAsset($processing->id);
        } catch (Throwable $exception) {
            $this->assets->markListingFailed($processing, $exception->getMessage());

            throw $exception;
        }
    }

    /**
     * Generate listing metadata for approved assets that do not have a title yet.
     */
    public function generatePendingApprovedAssets(int $limit = 0, int $delaySeconds = 0): int
    {
        $this->recoverStaleProcessingAssets();

        $processed = 0;
        $claimed = 0;

        while ($limit <= 0 || $claimed < $limit) {
            $asset = $this->claimNextPendingApprovedAsset();

            if (! $asset) {
                break;
            }

            $claimed++;

            try {
                if ($this->generateForApprovedAsset($asset->id)) {
                    $processed++;
                }
            } catch (Throwable $exception) {
                $this->assets->markListingFailed($asset, $exception->getMessage());
                Log::warning('Marketplace listing metadata generation failed.', [
                    'asset_id' => $asset->id,
                    'user_id' => $asset->user_id,
                    'message' => $exception->getMessage(),
                ]);
            }

            if ($delaySeconds > 0 && ($limit <= 0 || $claimed < $limit)) {
                sleep($delaySeconds);
            }
        }

        return $processed;
    }

    public function recoverStaleProcessingAssets(): int
    {
        return ProductDesignAsset::query()
            ->where('is_approved', true)
            ->whereNull('title')
            ->where('marketplace_listing_status', 'processing')
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('marketplace_listing_started_at')
                    ->orWhere('marketplace_listing_started_at', '<=', $this->staleProcessingCutoff());
            })
            ->update([
                'marketplace_listing_status' => 'failed',
                'marketplace_listing_completed_at' => now(),
                'marketplace_listing_error' => 'Listing metadata bi ket qua lau khong cap nhat. He thong da tu dong chuyen ve Failed, hay bam Retry de chay lai.',
            ]);
    }

    private function claimNextPendingApprovedAsset(): ?ProductDesignAsset
    {
        return DB::transaction(function (): ?ProductDesignAsset {
            $runningUserIds = ProductDesignAsset::query()
                ->where('marketplace_listing_status', 'processing')
                ->pluck('user_id')
                ->filter()
                ->all();

            $asset = $this->eligiblePendingApprovedAssetsQuery()
                ->when($runningUserIds !== [], fn (Builder $query): Builder => $query->whereNotIn('user_id', $runningUserIds))
                ->limit(500)
                ->lockForUpdate()
                ->get()
                ->first(fn (ProductDesignAsset $asset): bool => ! $this->isProviderPausedForAsset($asset));

            if (! $asset) {
                return null;
            }

            return $this->assets->markListingProcessing($asset, $this->marketplaceForAsset($asset));
        });
    }

    private function eligiblePendingApprovedAssetsQuery(): Builder
    {
        return ProductDesignAsset::query()
            ->with(['user', 'product'])
            ->where('is_approved', true)
            ->whereNull('title')
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('marketplace_listing_status')
                    ->orWhere('marketplace_listing_status', 'waiting')
                    ->orWhere('marketplace_listing_status', 'failed')
                    ->orWhere(function (Builder $query): void {
                        $query
                            ->where('marketplace_listing_status', 'processing')
                            ->where(function (Builder $query): void {
                                $query
                                    ->whereNull('marketplace_listing_started_at')
                                    ->orWhere('marketplace_listing_started_at', '<=', $this->staleProcessingCutoff());
                            });
                    });
            })
            ->where(function (Builder $query): void {
                $query
                    ->where(function (Builder $query): void {
                        $query
                            ->whereHas('product', fn (Builder $query): Builder => $query->where('slug', 'ornament-amazon-2'))
                            ->whereHas('user', function (Builder $query): void {
                                $query
                                    ->where('can_generate_amazon_listing', true)
                                    ->whereHas('aiProviders', fn (Builder $query): Builder => $query->where('provider_key', 'v98store')->where('is_enabled', true));
                            });
                    })
                    ->orWhere(function (Builder $query): void {
                        $query
                            ->whereDoesntHave('product', fn (Builder $query): Builder => $query->where('slug', 'ornament-amazon-2'))
                            ->whereHas('user', function (Builder $query): void {
                                $query
                                    ->where('can_generate_amazon_listing', true)
                                    ->orWhere('can_generate_etsy_listing', true);
                            });
                    });
            })
            ->orderBy('approved_at')
            ->orderBy('id');
    }

    private function staleProcessingCutoff(): \DateTimeInterface
    {
        $minutes = max(1, (int) config('services.marketplace_listing.stale_processing_minutes', 10));

        return now()->subMinutes($minutes);
    }

    private function generateAmazonMetadata(ProductDesignAsset $asset): ProductDesignAsset
    {
        $payload = $this->jsonPayload(
            $this->generateAmazonListingText($asset),
        );

        $updatedAsset = $this->assets->updateListingMetadata($asset, [
            'title' => $this->stringValue($payload, 'title', 199),
            'description' => $this->stringValue($payload, 'description', 199),
            'bullet_point_1' => $this->stringValue($payload, 'bullet_point_1', 699),
            'bullet_point_2' => $this->stringValue($payload, 'bullet_point_2', 699),
            'bullet_point_3' => $this->stringValue($payload, 'bullet_point_3', 699),
            'bullet_point_4' => $this->stringValue($payload, 'bullet_point_4', 699),
            'bullet_point_5' => $this->stringValue($payload, 'bullet_point_5', 699),
            'generic_keyword' => $this->stringValue($payload, 'generic_keyword', 249),
            'tags' => null,
        ]);

        $this->logGenerated($updatedAsset, 'amazon');

        return $updatedAsset;
    }


    private function generateAmazonListingText(ProductDesignAsset $asset): string
    {
        return $this->generateListingText($asset, $this->prompt($this->amazonPromptTemplate($asset), $asset));
    }

    private function isProviderPausedForAsset(ProductDesignAsset $asset): bool
    {
        $providerKey = $this->listingProviderKey($asset);

        if (! in_array($providerKey, ['v98store', 'cheapkeyai'], true)) {
            return false;
        }

        return Cache::has('provider-pause:'.$providerKey.':user:'.$asset->user_id);
    }

    private function providerLabel(string $providerKey): string
    {
        return (string) config("ai_providers.providers.{$providerKey}.label", $providerKey);
    }
    private function listingProviderKey(ProductDesignAsset $asset): string
    {
        $providerKey = trim((string) $asset->ai_provider_key);

        if ($providerKey !== '') {
            return $providerKey;
        }

        return 'cheapkeyai';
    }

    private function generateListingText(ProductDesignAsset $asset, string $prompt): string
    {
        $configuredProvider = trim((string) $asset->ai_provider_key);
        $providers = $configuredProvider !== ''
            ? [$configuredProvider]
            : ['cheapkeyai', 'vertex'];
        $errors = [];

        foreach ($providers as $providerKey) {
            try {
                if ($this->isProviderPausedForAsset($asset) && $providerKey !== 'vertex') {
                    throw new RuntimeException(
                        $this->providerLabel($providerKey).' cua user nay dang tam dung do het tien/het quota.',
                    );
                }

                return $this->generateListingTextWithProvider($asset, $prompt, $providerKey);
            } catch (Throwable $exception) {
                $errors[] = $this->providerLabel($providerKey).': '.$exception->getMessage();
            }
        }

        throw new RuntimeException(implode(' | ', $errors));
    }

    private function generateListingTextWithProvider(ProductDesignAsset $asset, string $prompt, string $providerKey): string
    {
        if ($providerKey === 'vertex') {
            return $this->generator->generateText($asset->user, $prompt, true);
        }

        if (! in_array($providerKey, ['v98store', 'cheapkeyai'], true)) {
            throw new RuntimeException("AI provider '{$providerKey}' khong duoc ho tro cho Listing metadata.");
        }

        $functionKey = trim((string) $asset->product?->slug);

        if ($functionKey === '') {
            throw new RuntimeException('Khong xac dinh duoc trang san pham de chay Listing metadata.');
        }

        $this->ensureUserOwnsApiCredential($asset->user, $providerKey, $functionKey);

        return $this->apiKeyGenerator->generateText(
            user: $asset->user,
            providerKey: $providerKey,
            prompt: $prompt,
            model: $providerKey === 'cheapkeyai' ? 'gpt-5.4-nano' : 'gpt-5.4',
            functionKey: $functionKey,
        );
    }

    private function ensureUserOwnsApiCredential(User $user, string $providerKey, string $functionKey): void
    {
        $hasCredential = UserApiCredential::query()
            ->where('user_id', $user->id)
            ->where('provider_key', $providerKey)
            ->where('function_key', $functionKey)
            ->where('is_active', true)
            ->exists();

        if (! $hasCredential) {
            throw new RuntimeException("User chua cau hinh {$providerKey} active cho trang {$functionKey} de tao Listing metadata.");
        }
    }

    private function ensureV98StoreBalance(User $user, ?string $productSlug = null): void
    {
        $balance = $this->v98StoreBalanceForUser($user);

        if (! is_array($balance) || ($balance['ok'] ?? false) !== true) {
            return;
        }

        $remaining = is_numeric($balance['remain_quota'] ?? null) ? (float) $balance['remain_quota'] : 0.0;

        if ($remaining <= 0) {
            $this->notifyV98StoreBalanceExhausted($user, $balance);

            $message = $productSlug === 'suncatcher'
                ? 'v98Store da het tien/het quota. Suncatcher listing metadata logs tam dung, vui long nap them tien roi chay lai.'
                : 'v98Store da het tien/het quota. Listing metadata logs tam dung, vui long nap them tien roi chay lai.';

            throw new RuntimeException($message);
        }
    }

    /**
     * @return array{ok: bool, remain_quota?: float|int, used_quota?: float|int, name?: string|null, message?: string, credential_id?: int}|null
     */
    private function v98StoreBalanceForUser(User $user): ?array
    {
        $credential = UserApiCredential::query()
            ->where('provider_key', 'v98store')
            ->where('is_active', true)
            ->where(function ($query) use ($user): void {
                $query->where('user_id', $user->id)
                    ->orWhereNull('user_id');
            })
            ->orderByRaw('CASE WHEN user_id = ? THEN 0 ELSE 1 END', [$user->id])
            ->first();

        if (! $credential) {
            return ['ok' => false, 'message' => 'No key'];
        }

        try {
            return Cache::remember(
                "v98store-balance:{$credential->id}",
                now()->addSeconds(15),
                fn (): array => array_merge($this->fetchV98StoreBalance($credential), ['credential_id' => $credential->id]),
            );
        } catch (Throwable) {
            return array_merge($this->fetchV98StoreBalance($credential), ['credential_id' => $credential->id]);
        }
    }

    /**
     * @return array{ok: bool, remain_quota?: float|int, used_quota?: float|int, name?: string|null, message?: string}
     */
    private function fetchV98StoreBalance(UserApiCredential $credential): array
    {
        $endpoint = config('services.api_key_providers.v98store.balance_endpoint', 'https://v98store.com/check-balance');

        if (! is_string($endpoint) || trim($endpoint) === '') {
            return ['ok' => false, 'message' => 'No endpoint'];
        }

        try {
            $apiKey = $credential->key_api;
        } catch (Throwable) {
            return ['ok' => false, 'message' => 'Key decrypt error'];
        }

        if (! is_string($apiKey) || trim($apiKey) === '') {
            return ['ok' => false, 'message' => 'Empty key'];
        }

        try {
            $response = Http::timeout(10)->get(trim($endpoint), [
                'key_api' => trim($apiKey),
            ]);
        } catch (Throwable) {
            return ['ok' => false, 'message' => 'Request failed'];
        }

        if ($response->failed()) {
            return ['ok' => false, 'message' => 'HTTP '.$response->status()];
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            return ['ok' => false, 'message' => 'Invalid balance'];
        }

        return [
            'ok' => true,
            'remain_quota' => is_numeric($payload['remain_quota'] ?? null) ? $payload['remain_quota'] + 0 : 0,
            'used_quota' => is_numeric($payload['used_quota'] ?? null) ? $payload['used_quota'] + 0 : 0,
            'name' => is_string($payload['name'] ?? null) ? $payload['name'] : null,
            'message' => is_string($payload['message'] ?? null) ? $payload['message'] : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $balance
     */
    private function notifyV98StoreBalanceExhausted(User $user, array $balance): void
    {
        $credentialId = (string) ($balance['credential_id'] ?? 'unknown');
        $alertKey = "v98store-listing-balance-alert:{$credentialId}";

        if (! Cache::add($alertKey, true, now()->addHours(6))) {
            return;
        }

        $remaining = is_numeric($balance['remain_quota'] ?? null) ? (float) $balance['remain_quota'] : 0.0;
        $used = is_numeric($balance['used_quota'] ?? null) ? (float) $balance['used_quota'] : null;
        $accountName = is_string($balance['name'] ?? null) ? $balance['name'] : 'v98Store';
        $subject = 'v98Store het tien/quota - Listing metadata logs da tam dung';
        $body = implode("\n", array_filter([
            'v98Store het tien/quota nen Listing metadata logs da tam dung.',
            '',
            'User: #'.$user->id.' '.$user->name.' <'.$user->email.'>',
            'Account: '.$accountName,
            'Remain: $'.number_format($remaining, 4, '.', ''),
            $used !== null ? 'Used: '.number_format($used, 4, '.', '') : null,
            'Time: '.now()->format('Y-m-d H:i:s'),
            '',
            'Vui long nap them tien/quota roi bam Duyet lai/Retry de chay Listing metadata logs.',
        ]));

        $recipients = collect([$user->email])
            ->merge(User::query()->where('is_admin', true)->pluck('email'))
            ->filter(fn (mixed $email): bool => is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values();

        foreach ($recipients as $email) {
            try {
                Mail::raw($body, fn ($mail) => $mail->to($email)->subject($subject));
            } catch (Throwable) {
            }
        }
    }
    private function generateEtsyMetadata(ProductDesignAsset $asset): ProductDesignAsset
    {
        $payload = $this->jsonPayload(
            $this->generateListingText($asset, $this->prompt(self::ETSY_PROMPT_TEMPLATE, $asset)),
        );

        $updatedAsset = $this->assets->updateListingMetadata($asset, [
            'title' => $this->stringValue($payload, 'title', 199),
            'description' => $this->stringValue($payload, 'description', 199),
            'bullet_point_1' => null,
            'bullet_point_2' => null,
            'bullet_point_3' => null,
            'bullet_point_4' => null,
            'bullet_point_5' => null,
            'generic_keyword' => null,
            'tags' => $this->stringValue($payload, 'tags'),
        ]);

        $this->logGenerated($updatedAsset, 'etsy');

        return $updatedAsset;
    }

    private function marketplaceForAsset(ProductDesignAsset $asset): string
    {
        return in_array($asset->product?->slug, ['ornament-amazon-2', 'sticker', 'decal'], true)
            ? 'amazon'
            : ($asset->user->can_generate_amazon_listing ? 'amazon' : 'etsy');
    }

    private function amazonPromptTemplate(ProductDesignAsset $asset): string
    {
        return in_array($asset->product?->slug, ['sticker', 'decal'], true)
            ? self::AMAZON_STICKER_PROMPT_TEMPLATE
            : self::AMAZON_PROMPT_TEMPLATE;
    }
    private function prompt(string $template, ProductDesignAsset $asset): string
    {
        return strtr($template, [
            '{amazon_product_from_sheet}' => $this->amazonProductFromSheet($asset),
            '{product}' => $asset->product?->name ?? 'Product',
            '{competitor_link}' => $this->competitorLink($asset),
            '{keyword_phrase}' => $this->keywordPhrase($asset),
        ]);
    }

    private function competitorLink(ProductDesignAsset $asset): string
    {
        $sourceData = is_array($asset->data_item_add) ? $asset->data_item_add : [];
        $link = $sourceData['competitor_link'] ?? $sourceData['product_link'] ?? $sourceData['link'] ?? '';

        return is_string($link) && trim($link) !== '' ? trim($link) : 'N/A';
    }

    private function keywordPhrase(ProductDesignAsset $asset): string
    {
        $sourceData = is_array($asset->data_item_add) ? $asset->data_item_add : [];
        $keywordPhrase = $sourceData['keyword_phrase'] ?? '';

        return is_string($keywordPhrase) && trim($keywordPhrase) !== '' ? trim($keywordPhrase) : $asset->keyword;
    }

    private function amazonProductFromSheet(ProductDesignAsset $asset): string
    {
        $sourceData = is_array($asset->data_item_add) ? $asset->data_item_add : [];
        $product = $sourceData['product'] ?? '';

        return is_string($product) && trim($product) !== '' ? trim($product) : ($asset->product?->name ?? $asset->keyword);
    }

    /**
     * @return array<string, mixed>
     */
    private function jsonPayload(string $text): array
    {
        $text = trim($text);
        $text = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $text) ?? $text;

        try {
            $payload = json_decode($text, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $start = strpos($text, '{');
            $end = strrpos($text, '}');

            if ($start !== false && $end !== false && $end > $start) {
                $json = substr($text, $start, $end - $start + 1);
                $json = preg_replace('/,\s*([}\]])/', '$1', $json) ?? $json;

                try {
                    $payload = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
                } catch (JsonException $exception) {
                    throw new RuntimeException(
                        "Listing metadata provider khong tra ve JSON listing hop le. RAW: ".$this->shortErrorPayload($text)." | EXTRACTED: ".$this->shortErrorPayload($json),
                        previous: $exception,
                    );
                }
            } else {
                throw new RuntimeException('Listing metadata provider khong tra ve JSON listing hop le. RAW: '.$this->shortErrorPayload($text));
            }
        }

        if (! is_array($payload)) {
            throw new RuntimeException('Listing metadata provider khong tra ve JSON listing hop le. RAW: '.$this->shortErrorPayload($text));
        }

        return $payload;
    }

    private function shortErrorPayload(string $text, int $limit = 1500): string
    {
        $text = preg_replace('/\s+/', ' ', trim($text)) ?? trim($text);

        return mb_substr($text, 0, $limit);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function stringValue(array $payload, string $key, ?int $maxLength = null): ?string
    {
        $value = $payload[$key] ?? null;

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        return $maxLength ? mb_substr($value, 0, $maxLength) : $value;
    }

    private function logGenerated(ProductDesignAsset $asset, string $marketplace): void
    {
        app(ActivityLogService::class)->record(
            event: "marketplace_listing.{$marketplace}_generated",
            description: "Generated {$marketplace} listing metadata for approved asset.",
            subject: $asset,
            properties: [
                'item_number' => $asset->item_number,
                'keyword' => $asset->keyword,
            ],
        );
    }
}
