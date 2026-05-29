<?php

namespace App\Repositories\Prompt;

use App\Models\Prompt;
use Illuminate\Database\Eloquent\Collection;

class PromptRepository
{
    /**
     * @return Collection<int, Prompt>
     */
    public function forUserAndProduct(int $userId, int $productId): Collection
    {
        return Prompt::query()
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->orderBy('prompt_number')
            ->get();
    }

    public function contentForUserProductAndNumber(int $userId, int $productId, int $promptNumber): ?string
    {
        return Prompt::query()
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->where('prompt_number', $promptNumber)
            ->value('content');
    }

    public function createForSlot(int $userId, int $productId, int $promptNumber, string $name, string $content = ''): Prompt
    {
        return Prompt::query()->create([
            'user_id' => $userId,
            'product_id' => $productId,
            'prompt_number' => $promptNumber,
            'name' => $name,
            'content' => $content,
        ]);
    }

    public function findForUserAndProduct(int $promptId, int $userId, int $productId): Prompt
    {
        return Prompt::query()
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->findOrFail($promptId);
    }

    public function updatePrompt(Prompt $prompt, string $name, string $content): Prompt
    {
        $prompt->update([
            'name' => $name,
            'content' => $content,
        ]);

        return $prompt->refresh();
    }
}
