<?php

namespace Tests\Feature;

use App\Livewire\Modals\Sticker\EditProductDetail;
use App\Models\Product;
use App\Models\ProductDesignAsset;
use App\Models\Prompt;
use App\Models\User;
use App\Models\VertexApiCredential;
use App\Repositories\ProductDesignAssetRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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
}
