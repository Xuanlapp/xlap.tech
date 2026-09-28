<?php

namespace App\Services\Decal;

use App\Models\Product;
use App\Models\PsdMockupTemplate;
use App\Models\User;
use App\Repositories\Product\ProductRepository;
use App\Repositories\Product\PsdMockupTemplateRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use InvalidArgumentException;

class PsdMockupTemplateService
{
    public const DECAL_CUSTOM_MOCKUP = 'decal_custom_mockup';

    public function __construct(
        private readonly ProductRepository $products,
        private readonly PsdMockupTemplateRepository $templates,
    ) {}

    /**
     * @return Collection<int, PsdMockupTemplate>
     */
    public function decalTemplatesForUser(User $user): Collection
    {
        return $this->templates->forUserProductAndFunction(
            $user->id,
            $this->decalProduct()->id,
            self::DECAL_CUSTOM_MOCKUP,
        );
    }

    public function activeDecalTemplateForUser(User $user): ?PsdMockupTemplate
    {
        return $this->templates->activeForUserProductAndFunction(
            $user->id,
            $this->decalProduct()->id,
            self::DECAL_CUSTOM_MOCKUP,
        );
    }

    public function uploadDecalTemplate(User $user, UploadedFile $file, ?string $name = null): PsdMockupTemplate
    {
        $this->ensurePsdFile($file);

        $product = $this->decalProduct();
        $filename = Str::uuid().'.psd';
        $path = $file->storeAs("psd-mockups/{$user->id}/decal", $filename, 'public');

        return $this->templates->createActive(
            $user->id,
            $product->id,
            self::DECAL_CUSTOM_MOCKUP,
            $this->normalizeName($name ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)),
            $file->getClientOriginalName(),
            $path,
        );
    }

    public function activateDecalTemplate(User $user, int $templateId): PsdMockupTemplate
    {
        $template = $this->templates->findForUserProductAndFunction(
            $templateId,
            $user->id,
            $this->decalProduct()->id,
            self::DECAL_CUSTOM_MOCKUP,
        );

        return $this->templates->activate($template);
    }

    private function decalProduct(): Product
    {
        return $this->products->findActiveBySlug('decal');
    }

    private function ensurePsdFile(UploadedFile $file): void
    {
        if (strtolower($file->getClientOriginalExtension()) !== 'psd') {
            throw new InvalidArgumentException('File mockup phai la PSD.');
        }
    }

    private function normalizeName(string $name): string
    {
        $name = trim($name);

        if ($name === '') {
            throw new InvalidArgumentException('Ten PSD khong duoc de trong.');
        }

        return Str::limit($name, 255, '');
    }
}
