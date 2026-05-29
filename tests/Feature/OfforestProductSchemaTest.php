<?php

namespace Tests\Feature;

use App\Livewire\Modals\Sticker\EditProductDetail;
use App\Models\Product;
use App\Models\ProductDesignAsset;
use App\Models\Prompt;
use App\Models\User;
use App\Models\VertexApiCredential;
use App\Repositories\Product\ProductDesignAssetRepository;
use App\Services\Sticker\PsdMockupRenderer;
use App\Services\Sticker\PsdMockupTemplateService;
use App\Services\Sticker\StickerService;
use App\Services\Vertex\VertexImageGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Mockery\MockInterface;
use Tests\TestCase;

class OfforestProductSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_product_prompt_credential_and_design_asset_relationships_work(): void
    {
        $user = User::factory()->create();
        $product = Product::where('slug', 'mockup')->firstOrFail();

        $user->products()->attach($product);

        $credential = VertexApiCredential::create([
            'user_id' => $user->id,
            'project_id' => 'offorest-project',
            'location' => 'global',
            'client_email' => 'vertex@example.iam.gserviceaccount.com',
            'private_key' => 'secret-key',
            'credentials_json' => ['client_email' => 'vertex@example.iam.gserviceaccount.com'],
        ]);

        $prompt = Prompt::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'prompt_number' => 1,
            'name' => 'Mockup prompt 1',
            'content' => 'Create a clean mockup.',
        ]);

        $asset = ProductDesignAsset::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'item_number' => 1,
            'keyword' => 'sunflower',
            'image_link' => 'https://example.com/source.png',
            'redesign' => 'https://example.com/redesign.png',
            'mockup1' => 'https://example.com/mockup1.png',
        ]);

        $this->assertTrue($user->products->contains($product));
        $this->assertTrue($user->vertexApiCredential->is($credential));
        $this->assertTrue($user->prompts->contains($prompt));
        $this->assertTrue($user->productDesignAssets->contains($asset));
        $this->assertTrue($product->prompts->contains($prompt));
        $this->assertTrue($product->designAssets->contains($asset));
    }

    public function test_design_asset_item_number_increments_per_user_and_product(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();
        $sticker = Product::where('slug', 'sticker')->firstOrFail();
        $mockup = Product::where('slug', 'mockup')->firstOrFail();

        $repository = app(ProductDesignAssetRepository::class);

        $firstSticker = $repository->createDraft($firstUser->id, $sticker->id, 'first sticker');
        $secondSticker = $repository->createDraft($firstUser->id, $sticker->id, 'second sticker');
        $firstUserMockup = $repository->createDraft($firstUser->id, $mockup->id, 'first mockup');
        $secondUserSticker = $repository->createDraft($secondUser->id, $sticker->id, 'another user sticker');

        $this->assertSame(1, $firstSticker->item_number);
        $this->assertSame(2, $secondSticker->item_number);
        $this->assertSame(1, $firstUserMockup->item_number);
        $this->assertSame(1, $secondUserSticker->item_number);
    }

    public function test_user_can_open_assigned_product_page(): void
    {
        $user = User::factory()->create();
        $product = Product::where('slug', 'mockup')->firstOrFail();

        $user->products()->attach($product);

        $this->actingAs($user)
            ->get(route('offorest.products.mockup'))
            ->assertOk()
            ->assertSee('Mockup');
    }

    public function test_user_cannot_open_unassigned_product_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('offorest.products.mockup'))
            ->assertForbidden();
    }

    public function test_non_admin_user_cannot_open_admin_page(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get(route('offorest.admin.users'))
            ->assertForbidden();
    }

    public function test_admin_user_can_open_admin_page(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        $this->actingAs($user)
            ->get(route('offorest.admin.users'))
            ->assertOk()
            ->assertSee('User access');
    }

    public function test_edit_product_detail_modal_updates_asset_without_page_reload(): void
    {
        $user = User::factory()->create();
        $product = Product::where('slug', 'sticker')->firstOrFail();
        $user->products()->attach($product);

        $asset = ProductDesignAsset::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'item_number' => 1,
            'keyword' => 'old sticker',
            'image_link' => 'https://example.com/old.png',
        ]);

        $this->actingAs($user);

        Livewire::test(EditProductDetail::class)
            ->call('open', $asset->id)
            ->assertSet('isOpen', true)
            ->assertSet('keyword', 'old sticker')
            ->set('keyword', 'new sticker')
            ->set('imageLink', 'https://example.com/new.jpg')
            ->call('save')
            ->assertSet('isOpen', false);

        $this->assertDatabaseHas('product_design_assets', [
            'id' => $asset->id,
            'keyword' => 'new sticker',
            'image_link' => 'https://example.com/new.jpg',
        ]);
    }

    public function test_sticker_service_creates_asset_with_normalized_source_details(): void
    {
        $user = User::factory()->create();
        $product = Product::where('slug', 'sticker')->firstOrFail();
        $user->products()->attach($product);

        $asset = app(StickerService::class)->createAsset(
            $user,
            '  cute cat sticker  ',
            '  https://example.com/source.png  ',
        );

        $this->assertSame(1, $asset->item_number);
        $this->assertSame('cute cat sticker', $asset->keyword);
        $this->assertSame('https://example.com/source.png', $asset->image_link);
    }

    public function test_sticker_service_generates_redesign_and_final_images(): void
    {
        $user = User::factory()->create();
        $product = Product::where('slug', 'sticker')->firstOrFail();
        $user->products()->attach($product);

        foreach ([1, 2, 3] as $promptNumber) {
            Prompt::create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'prompt_number' => $promptNumber,
                'content' => "Prompt {$promptNumber}",
            ]);
        }

        $asset = ProductDesignAsset::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'item_number' => 1,
            'keyword' => 'cat sticker',
            'image_link' => 'https://example.com/source.png',
        ]);

        $this->mock(VertexImageGenerator::class, function (MockInterface $mock): void {
            $mock->shouldReceive('generate')
                ->once()
                ->withArgs(fn (User $user, string $imageUri, string $prompt, string $folder): bool => $imageUri === 'https://example.com/source.png'
                    && $prompt === 'Prompt 1'
                    && $folder === 'generated/sticker/redesign')
                ->andReturn('/storage/generated/sticker/redesign/master.png');

            $mock->shouldReceive('generate')
                ->once()
                ->withArgs(fn (User $user, string $imageUri, string $prompt, string $folder): bool => $imageUri === '/storage/generated/sticker/redesign/master.png'
                    && $prompt === 'Prompt 2'
                    && $folder === 'generated/sticker/final')
                ->andReturn('/storage/generated/sticker/final/lifestyle.png');

            $mock->shouldReceive('generate')
                ->once()
                ->withArgs(fn (User $user, string $imageUri, string $prompt, string $folder): bool => $imageUri === '/storage/generated/sticker/redesign/master.png'
                    && $prompt === 'Prompt 3'
                    && $folder === 'generated/sticker/final')
                ->andReturn('/storage/generated/sticker/final/mockup.png');
        });

        $service = app(StickerService::class);

        $service->generateRedesign($user, $asset->id);
        $service->generateFinalImages($user, $asset->id);

        $this->assertDatabaseHas('product_design_assets', [
            'id' => $asset->id,
            'redesign' => '/storage/generated/sticker/redesign/master.png',
            'mockup1' => '/storage/generated/sticker/final/lifestyle.png',
            'mockup2' => '/storage/generated/sticker/final/mockup.png',
        ]);
    }

    public function test_user_can_store_many_psd_templates_but_only_one_is_active_for_sticker_custom_mockup(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $product = Product::where('slug', 'sticker')->firstOrFail();
        $user->products()->attach($product);

        $service = app(PsdMockupTemplateService::class);

        $first = $service->uploadStickerTemplate(
            $user,
            UploadedFile::fake()->create('first.psd', 10, 'application/octet-stream'),
            'First PSD',
        );

        $second = $service->uploadStickerTemplate(
            $user,
            UploadedFile::fake()->create('second.psd', 10, 'application/octet-stream'),
            'Second PSD',
        );

        $this->assertDatabaseHas('psd_mockup_templates', [
            'id' => $first->id,
            'is_active' => false,
        ]);

        $this->assertDatabaseHas('psd_mockup_templates', [
            'id' => $second->id,
            'is_active' => true,
        ]);

        $this->assertSame(2, $service->stickerTemplatesForUser($user)->count());
        $this->assertTrue($service->activeStickerTemplateForUser($user)->is($second));
    }

    public function test_sticker_service_renders_psd_mockups_from_active_template(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $product = Product::where('slug', 'sticker')->firstOrFail();
        $user->products()->attach($product);

        app(PsdMockupTemplateService::class)->uploadStickerTemplate(
            $user,
            UploadedFile::fake()->create('mockup.psd', 10, 'application/octet-stream'),
            'Sticker PSD',
        );

        $asset = ProductDesignAsset::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'item_number' => 1,
            'keyword' => 'cat sticker',
            'image_link' => 'https://example.com/source.png',
            'redesign' => '/storage/generated/sticker/redesign/master.png',
        ]);

        $this->mock(PsdMockupRenderer::class, function (MockInterface $mock): void {
            $mock->shouldReceive('render')
                ->once()
                ->withArgs(fn ($template, string $masterImageUri, int $assetId): bool => $template->name === 'Sticker PSD'
                    && $masterImageUri === '/storage/generated/sticker/redesign/master.png'
                    && $assetId > 0)
                ->andReturn([
                    '/storage/generated/sticker/mockups/1/MOCKUP 1.png',
                    '/storage/generated/sticker/mockups/1/MOCKUP 2.png',
                ]);
        });

        app(StickerService::class)->generatePsdMockups($user, $asset->id);

        $this->assertDatabaseHas('product_design_assets', [
            'id' => $asset->id,
            'mockup2' => '/storage/generated/sticker/mockups/1/MOCKUP 1.png',
            'mockup3' => '/storage/generated/sticker/mockups/1/MOCKUP 2.png',
        ]);
    }
}
