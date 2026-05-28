<?php

namespace App\Repositories;

use App\Models\Prompt;

class PromptRepository
{
    public function contentForUserProductAndNumber(int $userId, int $productId, int $promptNumber): ?string
    {
        return Prompt::query()
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->where('prompt_number', $promptNumber)
            ->value('content');
    }
}
