<?php

namespace App\Services\Prompt;

use App\Models\Product;
use App\Models\Prompt;
use App\Models\User;
use App\Repositories\Product\ProductRepository;
use App\Repositories\Prompt\PromptRepository;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;
use RuntimeException;

class PromptService
{
    public const MAX_PROMPTS_PER_PRODUCT = 4;

    public function __construct(
        private readonly ProductRepository $products,
        private readonly PromptRepository $prompts,
    ) {}

    public function productBySlug(string $productSlug): Product
    {
        return $this->products->findActiveBySlug($productSlug);
    }

    /**
     * @return Collection<int, Prompt>
     */
    public function promptsForUserProduct(User $user, string $productSlug): Collection
    {
        $product = $this->productBySlug($productSlug);

        return $this->prompts->forUserAndProduct($user->id, $product->id);
    }

    public function createNextPrompt(User $user, string $productSlug): Prompt
    {
        $product = $this->productBySlug($productSlug);
        $prompts = $this->prompts->forUserAndProduct($user->id, $product->id);

        if ($prompts->count() >= self::MAX_PROMPTS_PER_PRODUCT) {
            throw new RuntimeException('Trang nay da du 4 prompt.');
        }

        $usedNumbers = $prompts->pluck('prompt_number')->map(fn ($number): int => (int) $number)->all();
        $promptNumber = $this->nextPromptNumber($usedNumbers);

        return $this->prompts->createForSlot(
            $user->id,
            $product->id,
            $promptNumber,
            $this->defaultPromptName($promptNumber),
        );
    }

    public function updatePrompt(User $user, string $productSlug, int $promptId, string $name, string $content): Prompt
    {
        $product = $this->productBySlug($productSlug);
        $prompt = $this->prompts->findForUserAndProduct($promptId, $user->id, $product->id);

        $name = trim($name);
        $content = trim($content);

        if ($name === '') {
            throw new InvalidArgumentException('Ten prompt khong duoc de trong.');
        }

        if ($content === '') {
            throw new InvalidArgumentException('Noi dung prompt khong duoc de trong.');
        }

        return $this->prompts->updatePrompt($prompt, $name, $content);
    }

    public function defaultPromptName(int $promptNumber): string
    {
        return match ($promptNumber) {
            1 => 'Design',
            2 => 'Mockup1',
            3 => 'Mockup2',
            4 => 'Mockup3',
            default => 'Prompt '.$promptNumber,
        };
    }

    /**
     * @param array<int, int> $usedNumbers
     */
    private function nextPromptNumber(array $usedNumbers): int
    {
        for ($number = 1; $number <= self::MAX_PROMPTS_PER_PRODUCT; $number++) {
            if (! in_array($number, $usedNumbers, true)) {
                return $number;
            }
        }

        throw new RuntimeException('Trang nay da du 4 prompt.');
    }
}
