<?php

namespace App\Services\SuncatcherTwo;

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
    public const ORNAMENT_CUSTOM_MOCKUP = 'suncatcher-two_custom_mockup';

    public function __construct(
        private readonly ProductRepository $products,
        private readonly PsdMockupTemplateRepository $templates,
    ) {}

    /**
     * @return Collection<int, PsdMockupTemplate>
     */
    public function suncatcherTwoTemplatesForUser(User $user): Collection
    {
        return $this->templates->forUserProductAndFunction(
            $user->id,
            $this->suncatcherTwoProduct()->id,
            self::ORNAMENT_CUSTOM_MOCKUP,
        );
    }

    public function activeSuncatcherTwoTemplateForUser(User $user): ?PsdMockupTemplate
    {
        return $this->templates->activeForUserProductAndFunction(
            $user->id,
            $this->suncatcherTwoProduct()->id,
            self::ORNAMENT_CUSTOM_MOCKUP,
        );
    }

    public function uploadSuncatcherTwoTemplate(User $user, UploadedFile $file, ?string $name = null): PsdMockupTemplate
    {
        $this->ensurePsdFile($file);

        $product = $this->suncatcherTwoProduct();
        return $this->templates->uploadActive(
            $user->id,
            $product->id,
            self::ORNAMENT_CUSTOM_MOCKUP,
            $this->normalizeName($name ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)),
            $file,
            "psd-mockups/{$user->id}/suncatcher-two",
        );
    }

    public function activateSuncatcherTwoTemplate(User $user, int $templateId): PsdMockupTemplate
    {
        $template = $this->templates->findForUserProductAndFunction(
            $templateId,
            $user->id,
            $this->suncatcherTwoProduct()->id,
            self::ORNAMENT_CUSTOM_MOCKUP,
        );

        return $this->templates->activate($template);
    }

    private function suncatcherTwoProduct(): Product
    {
        return $this->products->findActiveBySlug('suncatcher-two');
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

