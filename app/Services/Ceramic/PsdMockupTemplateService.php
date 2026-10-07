<?php

namespace App\Services\Ceramic;

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
    public const CERAMIC_CUSTOM_MOCKUP = 'ceramic_custom_mockup';

    public function __construct(
        private readonly ProductRepository $products,
        private readonly PsdMockupTemplateRepository $templates,
    ) {}

    /**
     * @return Collection<int, PsdMockupTemplate>
     */
    public function ceramicTemplatesForUser(User $user): Collection
    {
        return $this->templates->forUserProductAndFunction(
            $user->id,
            $this->ceramicProduct()->id,
            self::CERAMIC_CUSTOM_MOCKUP,
        );
    }

    public function activeCeramicTemplateForUser(User $user): ?PsdMockupTemplate
    {
        return $this->templates->activeForUserProductAndFunction(
            $user->id,
            $this->ceramicProduct()->id,
            self::CERAMIC_CUSTOM_MOCKUP,
        );
    }

    public function uploadCeramicTemplate(User $user, UploadedFile $file, ?string $name = null): PsdMockupTemplate
    {
        $this->ensurePsdFile($file);

        $product = $this->ceramicProduct();
        $filename = Str::uuid().'.psd';
        $path = $file->storeAs("psd-mockups/{$user->id}/ceramic", $filename, 'public');

        return $this->templates->createActive(
            $user->id,
            $product->id,
            self::CERAMIC_CUSTOM_MOCKUP,
            $this->normalizeName($name ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)),
            $file->getClientOriginalName(),
            $path,
        );
    }

    public function activateCeramicTemplate(User $user, int $templateId): PsdMockupTemplate
    {
        $template = $this->templates->findForUserProductAndFunction(
            $templateId,
            $user->id,
            $this->ceramicProduct()->id,
            self::CERAMIC_CUSTOM_MOCKUP,
        );

        return $this->templates->activate($template);
    }

    private function ceramicProduct(): Product
    {
        return $this->products->findActiveBySlug('ceramic');
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



