<?php

namespace Tests\Feature;

use App\Livewire\Pages\Order\OrderAmazonPaste;
use App\Models\HistoryOrderReport;
use App\Models\OrderProduct;
use App\Models\Product;
use App\Models\SkuOrderItem;
use App\Models\User;
use App\Services\Google\GoogleDriveService;
use App\Services\Ai\ApiKeyImageGenerator;
use App\Models\UserApiCredential;
use App\Models\ListingPromptOverride;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrderTextTest extends TestCase
{
    use RefreshDatabase;

    public function test_amazon_paste_page_requires_product_access(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'role' => 'user', 'status' => 'active']);
        $product = Product::where('slug', 'order-amazon-paste')->firstOrFail();
        $this->actingAs($user)->get(route('offorest.products.order-amazon-paste'))->assertForbidden();
        $user->products()->attach($product->id);
        $this->assertTrue($user->canAccessProduct('order-amazon-paste'));
        $this->get(route('offorest.products.order-amazon-paste'))->assertOk()->assertSee('Order Amazon Paste');
    }
    public function test_only_super_admin_can_upload_order_bounds_templates(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['is_admin' => true, 'role' => 'admin']);
        $super = User::factory()->create(['is_admin' => true, 'role' => 'super_admin']);
        $file = UploadedFile::fake()->image('glass.png', 200, 200);
        $this->actingAs($admin);
        Livewire::test(OrderAmazonPaste::class)->set('orderAmazonBoundsType', 'glass')->set('orderAmazonBoundsTemplate', $file)->call('saveOrderAmazonBoundsTemplate')->assertForbidden();
        $this->actingAs($super);
        Livewire::test(OrderAmazonPaste::class)->set('orderAmazonBoundsType', 'glass')->set('orderAmazonBoundsTemplate', UploadedFile::fake()->image('glass.png', 200, 200))->call('saveOrderAmazonBoundsTemplate')->assertHasNoErrors()->assertDispatched('close-modal');
        Storage::disk('public')->assertExists('admin/order-amazon-paste/bounds/glass.png');
        Storage::disk('public')->assertExists('admin/order-amazon-paste/bounds/glass.json');
        Livewire::test(OrderAmazonPaste::class)->set('orderAmazonBoundsType', 'ceramic')->set('orderAmazonBoundsTemplate', UploadedFile::fake()->image('ceramic.jpg', 120, 180))->call('saveOrderAmazonBoundsTemplate')->assertHasNoErrors();
        Storage::disk('public')->assertExists('admin/order-amazon-paste/bounds/ceramic.png');
        $this->assertSame(IMAGETYPE_PNG, getimagesizefromstring(Storage::disk('public')->get('admin/order-amazon-paste/bounds/ceramic.png'))[2]);
        Livewire::test(OrderAmazonPaste::class)->set('orderAmazonBoundsType', '../glass')->set('orderAmazonBoundsTemplate', UploadedFile::fake()->image('bad.png'))->call('saveOrderAmazonBoundsTemplate')->assertHasErrors(['orderAmazonBoundsType']);
    }

    public function test_order_template_save_validates_and_preserves_original_master(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['is_admin' => true, 'role' => 'super_admin']);
        $this->actingAs($user);
        $image = UploadedFile::fake()->image('template.png', 100, 100);
        Storage::disk('public')->put('admin/order-amazon-paste/bounds/glass.png', file_get_contents($image->getRealPath()));
        session()->put('order_amazon_paste.'.$user->id, [
            'orderTextRows' => [['id' => '114-7037254-1645825', 'sku' => 'FCAMZ1716', 'shipTo' => '', 'company' => '', 'phone' => '', 'customizations' => '', 'quantity' => '1']],
            'orderTextMasterImages' => ['/storage/master.png'],
            'orderTextApproved' => [true],
            'orderTextMasterDriveLinks' => ['old-link'],
        ]);
        $data = 'data:image/png;base64,'.base64_encode(file_get_contents($image->getRealPath()));
        $component = Livewire::test(OrderAmazonPaste::class)->call('saveOrderTextTemplate', 0, 'glass', $data)->assertHasNoErrors()->assertDispatched('order-template-saved');
        $this->assertSame('/storage/master.png', $component->get('orderTextMasterImages')[0]);
        $this->assertEmpty($component->get('orderTextApproved'));
        $this->assertEmpty($component->get('orderTextMasterDriveLinks'));
        Storage::disk('public')->assertExists(substr($component->get('orderTextTemplateImages')[0], strlen('/storage/')));
        $component->call('saveOrderTextTemplate', 0, 'ceramic', $data)->assertHasErrors('orderTextMaster');
        $component->call('saveOrderTextTemplate', 0, '../glass', $data)->assertHasErrors('orderTextMaster');
    }
    public function test_replacing_template_removes_only_previous_template(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['is_admin' => true, 'role' => 'super_admin']);
        $this->actingAs($user);
        $image = UploadedFile::fake()->image('template.png', 100, 100);
        Storage::disk('public')->put('admin/order-amazon-paste/bounds/glass.png', file_get_contents($image->getRealPath()));
        $old = 'order-amazon-paste/'.$user->id.'/old.png';
        Storage::disk('public')->put($old, 'old');
        session()->put('order_amazon_paste.'.$user->id, [
            'orderTextRows' => [['id' => '114-7037254-1645825', 'sku' => 'FIRST', 'shipTo' => '', 'company' => '', 'phone' => '', 'customizations' => '', 'quantity' => '1']],
            'orderTextMasterImages' => ['/storage/master.png'],
            'orderTextTemplateImages' => ['/storage/'.$old],
        ]);
        $data = 'data:image/png;base64,'.base64_encode(file_get_contents($image->getRealPath()));
        $component = Livewire::test(OrderAmazonPaste::class)->call('saveOrderTextTemplate', 0, 'glass', $data)->assertHasNoErrors();
        $new = substr($component->get('orderTextTemplateImages')[0], strlen('/storage/'));
        $this->assertStringContainsString('order-amazon-paste/'.$user->id.'/glass/', $new);
        $this->assertStringContainsString('FIRST_'.$user->id.'_unknown_'.now()->format('dmY').'_template_', $new);
        Storage::disk('public')->assertExists($new);
        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists('admin/order-amazon-paste/bounds/glass.png');
    }

    public function test_clearing_draft_deletes_owned_generated_images_only(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);
        $owned = 'order-amazon-paste/'.$user->id.'/master.png';
        $template = 'order-amazon-paste/'.$user->id.'/template.png';
        $other = 'order-amazon-paste/'.($user->id + 1).'/other.png';
        $stale = 'order-amazon-paste/'.$user->id.'/ceramic/old-order/image.png';
        $legacy = 'order-amazon-paste/'.$user->id.'/old-order/image.png';
        foreach ([$owned, $template, $other, $stale, $legacy] as $path) Storage::disk('public')->put($path, 'image');
        $row = ['id' => '114-7037254-1645825', 'sku' => 'FIRST', 'shipTo' => '', 'company' => '', 'phone' => '', 'customizations' => '', 'quantity' => '1'];
        session()->put('order_amazon_paste.'.$user->id, [
            'orderTextRows' => [$row, $row],
            'orderTextMasterImages' => ['/storage/'.$owned, '/storage/'.$other],
            'orderTextTemplateImages' => ['/storage/'.$template],
        ]);
        Livewire::test(OrderAmazonPaste::class)->call('clearOrderTextRows')->assertCount('orderTextRows', 0);
        Storage::disk('public')->assertMissing($owned);
        Storage::disk('public')->assertMissing($template);
        Storage::disk('public')->assertMissing($stale);
        Storage::disk('public')->assertMissing($legacy);
        $this->assertFalse(Storage::disk('public')->directoryExists('order-amazon-paste/'.$user->id));
        Storage::disk('public')->assertExists($other);
    }

    public function test_order_image_cards_stay_inside_image_section(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);
        $row = ['id' => '114-7037254-1645825', 'sku' => 'FIRST', 'shipTo' => '', 'company' => '', 'phone' => '', 'customizations' => '', 'quantity' => '1'];
        session()->put('order_amazon_paste.'.$user->id, ['orderTextRows' => [$row, array_replace($row, ['sku' => 'SECOND'])]]);
        $html = Livewire::test(OrderAmazonPaste::class)->html();
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        $xpath = new \DOMXPath($dom);
        $this->assertSame(2, $xpath->query('//section[@aria-labelledby="order-text-images-title"]/article')->length);
    }
    public function test_approval_loading_target_is_scoped_to_each_card(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);
        $row = ['id' => '114-7037254-1645825', 'sku' => 'FIRST', 'shipTo' => '', 'company' => '', 'phone' => '', 'customizations' => '', 'quantity' => '1'];
        session()->put('order_amazon_paste.'.$user->id, [
            'orderTextRows' => [$row, array_replace($row, ['sku' => 'SECOND'])],
            'orderTextMasterImages' => ['/storage/master-1.png', '/storage/master-2.png'],
            'orderTextTemplateImages' => ['/storage/template-1.png', '/storage/template-2.png'],
        ]);
        $html = Livewire::test(OrderAmazonPaste::class)->html();
        $this->assertStringNotContainsString('wire:target="generateOrderTextMaster,exportOrderText"', $html);
        $this->assertStringContainsString('wire:target="approveOrderTextMaster(0),generateOrderTextMaster(0),exportOrderText"', $html);
        $this->assertStringContainsString('wire:target="approveOrderTextMaster(1),generateOrderTextMaster(1),exportOrderText"', $html);
    }

    public function test_skip_design_link_is_per_row_persists_and_exports_without_source_link(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);
        $first = ['id' => '114-7037254-1645825', 'sku' => 'FIRST', 'shipTo' => '', 'company' => '', 'phone' => '', 'customizations' => '', 'quantity' => '1'];
        $second = array_replace($first, ['id' => '114-7037254-1645826', 'sku' => 'SECOND']);
        SkuOrderItem::create(['user_id' => $user->id, 'sku' => 'SECOND', 'image_link' => 'https://drive.google.com/file/d/source/view', 'source' => 'manual_import']);
        session()->put('order_amazon_paste.'.$user->id, [
            'orderTextRows' => [$first, $second],
            'orderTextApproved' => [true],
            'orderTextTemplateImages' => ['/storage/t1.png'],
            'orderTextMasterDriveLinks' => ['https://drive.google.com/file/d/approved/view'],
        ]);
        $component = Livewire::test(OrderAmazonPaste::class)->set('orderTextSkipDesignLink.1', true);
        $component->call('exportOrderText')->assertFileDownloaded();
        $history = HistoryOrderReport::where('user_id', $user->id)->where('order_id', $second['id'])->firstOrFail();
        $this->assertSame('', $history->report_data['link_design']);
        $this->assertSame([], $history->images_link);
        Livewire::test(OrderAmazonPaste::class)->assertSet('orderTextSkipDesignLink.1', true)
            ->call('removeOrderTextRow', 0)->assertSet('orderTextSkipDesignLink.0', true)
            ->call('exportOrderText')->assertFileDownloaded()
            ->set('orderTextSkipDesignLink.0', false)->call('exportOrderText')->assertHasErrors(['orderTextExport'])
            ->call('clearOrderTextRows')->assertSet('orderTextSkipDesignLink', []);
    }

    public function test_export_button_requires_design_links_for_all_rows(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);
        $row = ['id' => '114-7037254-1645825', 'sku' => 'FIRST', 'shipTo' => '', 'company' => '', 'phone' => '', 'customizations' => '', 'quantity' => '1'];
        session()->put('order_amazon_paste.'.$user->id, [
            'orderTextRows' => [$row, array_replace($row, ['sku' => 'SECOND'])],
            'orderTextMasterDriveLinks' => [0 => 'https://drive.google.com/file/d/design-1/view'],
        ]);
        $component = Livewire::test(OrderAmazonPaste::class);
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$component->html());
        $button = (new \DOMXPath($dom))->query('//button[@*[name()="wire:click"]="exportOrderText"]');
        $this->assertSame(1, $button->length);
        $this->assertTrue($button->item(0)->hasAttribute('disabled'));
        $component->assertSee('Cần duyệt đủ ảnh Template')->call('exportOrderText')->assertHasErrors(['orderTextExport']);
        $this->assertDatabaseCount('history_order_report', 0);
        session()->put('order_amazon_paste.'.$user->id, [
            'orderTextRows' => [$row, array_replace($row, ['sku' => 'SECOND'])],
            'orderTextApproved' => [true, true],
            'orderTextTemplateImages' => ['/storage/first.png', '/storage/second.png'],
            'orderTextMasterDriveLinks' => ['https://drive.google.com/file/d/first/view', 'https://drive.google.com/file/d/second/view'],
        ]);
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">'.Livewire::test(OrderAmazonPaste::class)->html());
        $button = (new \DOMXPath($dom))->query('//button[@*[name()="wire:click"]="exportOrderText"]');
        $this->assertSame(1, $button->length);
        $this->assertFalse($button->item(0)->hasAttribute('disabled'));
    }

    public function test_order_images_offer_glass_style_preview(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);
        SkuOrderItem::create(['user_id' => $user->id, 'sku' => 'FIRST', 'image_link' => 'https://example.com/source.png', 'source' => 'manual_import']);
        session()->put('order_amazon_paste.'.$user->id, [
            'orderTextRows' => [['id' => '114-7037254-1645825', 'sku' => 'FIRST', 'shipTo' => '', 'company' => '', 'phone' => '', 'customizations' => '', 'quantity' => '1']],
            'orderTextMasterImages' => ['https://example.com/master.png'],
            'orderTextTemplateImages' => ['https://example.com/template.png'],
        ]);
        $html = Livewire::test(OrderAmazonPaste::class)->html();
        $this->assertSame(3, substr_count($html, 'cursor-zoom-in'));
        $this->assertSame(3, substr_count($html, "dispatch('review-image'"));
        $this->assertStringNotContainsString('imageOnly: true', $html);
    }
    public function test_extracts_fields_from_amazon_order_text(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $text = "Order details  Order ID: # 114-7037254-1645825 Custom Order\nShip to\n\nErin Salmon\nW7722 AQUARIUS DR\nPARDEEVILLE, WI 53954-9386\nAddress Type: Residential\nOrder contents\nBuy shipping\nProduct name\nMore information\nUnshipped\n\nCustom Glass Ornament | Personalized Christmas Tree Decor\nASIN: B0HNCLZL2R\nSKU: FCAMZ1716\nCustomizations:\nSurface 1:\nSELECT SIZES: 3 inches\nCHOOSE YOUR HANGING ROPE.: Red satin ribbon\nADD NAME (example: DAVID - TOM - ALEX ): Sawyer Salmon\nADD YEAR: October 24, 2026\nChoose Pack: Pack 1\nWould you prefer your item to come with a gift box?: BOX\nCustomization Information\nShow less\nCondition: New
Order Item ID: 173304455103881
1	$20.56";

        $component = Livewire::test(OrderAmazonPaste::class)

            ->set('orderText', $text)
            ->call('parseOrderText')
            ->assertSet('orderTextId', '114-7037254-1645825')
            ->assertSet('orderTextShipTo', "Erin Salmon\nW7722 AQUARIUS DR\nPARDEEVILLE, WI 53954-9386")
            ->assertSet('orderTextProduct', 'Custom Glass Ornament | Personalized Christmas Tree Decor')
            ->assertSet('orderTextSku', 'FCAMZ1716')
            ->assertSet('orderTextQuantity', '1')
            ->assertSet('orderTextCustomizations', "Surface 1:\nSELECT SIZES: 3 inches\nCHOOSE YOUR HANGING ROPE.: Red satin ribbon\nADD NAME (example: DAVID - TOM - ALEX ): Sawyer Salmon\nADD YEAR: October 24, 2026\nChoose Pack: Pack 1\nWould you prefer your item to come with a gift box?: BOX")
            ->assertSeeInOrder(['TO_NAME', 'TO_ADDRESS1', 'TO_ADDRESS2', 'TO_CITY', 'TO_STATE', 'TO_POSTCODE', 'Erin Salmon', 'W7722 AQUARIUS DR', 'PARDEEVILLE', 'WI', '53954-9386'])
            ->assertSet('orderTextRows.0.customizations', "Surface 1:\nSELECT SIZES: 3 inches\nCHOOSE YOUR HANGING ROPE.: Red satin ribbon\nADD NAME (example: DAVID - TOM - ALEX ): Sawyer Salmon\nADD YEAR: October 24, 2026\nChoose Pack: Pack 1\nWould you prefer your item to come with a gift box?: BOX")
            ->assertDontSee('SELECT SIZES')
            ->assertDontSee('3 inches')
            ->assertDontSee('ADD NAME (example: DAVID - TOM - ALEX )')
            ->assertSee('Sawyer Salmon')
            ->assertDontSee('Would you prefer your item to come with a gift box?')
            ->assertSee('BOX')
            ->assertDontSee('wire:model="orderTextId"', false);

        $this->assertSame(1, preg_match('/<table\b[^>]*>.*?<\/table>/s', $component->html(), $matches));
        foreach (['ADD NAME', 'ADD YEAR', 'Choose Pack', 'Surface 1', 'SELECT SIZES', '3 inches'] as $hidden) {
            $this->assertStringNotContainsString($hidden, $matches[0]);
        }
        $this->assertStringContainsString('CHOOSE YOUR HANGING ROPE.', $matches[0]);
    }

    public function test_address_line_two_and_unrecognized_address_are_safe(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $component = Livewire::test(OrderAmazonPaste::class);
        $component->set('orderText', "Ship to\nJane Doe\n123 Main Street\nApt 2\nBoston, MA 02110\nAddress Type: Residential")
            ->call('parseOrderText')
            ->assertSeeInOrder(['TO_ADDRESS1', 'TO_ADDRESS2', 'TO_CITY', 'TO_STATE', 'TO_POSTCODE', '123 Main Street', 'Apt 2', 'Boston', 'MA', '02110']);
        $component->call('clearOrderTextInput')->set('orderText', "Ship to\nJane Doe\nUnknown City\nAddress Type: Residential")
            ->call('parseOrderText')
            ->assertDontSee('Ship to (bản gốc)')
            ->assertSeeInOrder(['TO_NAME', 'TO_ADDRESS1', 'TO_ADDRESS2', 'TO_CITY', 'TO_STATE', 'TO_POSTCODE', '-', '-', '-', '-', '-', '-'])
            ->assertSet('orderTextShipTo', "Jane Doe\nUnknown City");
    }

    public function test_design_links_respect_ownership_and_admin_access(): void
    {
        $owner = User::factory()->create(['name' => 'Owner', 'is_admin' => false]);
        $other = User::factory()->create(['name' => 'Other', 'is_admin' => false]);
        $admin = User::factory()->create(['is_admin' => true]);
        SkuOrderItem::create(['user_id' => $owner->id, 'sku' => 'FCAMZ1716', 'image_link' => 'https://example.com/owner', 'source' => 'manual_import']);
        SkuOrderItem::create(['user_id' => $other->id, 'sku' => 'FCAMZ1716', 'image_link' => 'https://example.com/other', 'source' => 'manual_import']);

        $this->actingAs($owner);
        $component = Livewire::test(OrderAmazonPaste::class)
            ->set('orderText', "SKU: FCAMZ1716")->call('parseOrderText')
            ->assertSee('Wait')->assertViewHas('orderTextResults', fn ($rows) => $rows[0]['designs']->pluck('image_link')->all() === ['https://example.com/owner']);
        $component->call('clearOrderTextInput')->set('orderText', "SKU: MISSING")->call('parseOrderText')
            ->assertSee('Design Link')->assertCount('orderTextRows', 2);
        $component->call('clearOrderTextInput')->set('orderText', "Order without SKU")->call('parseOrderText')
            ->assertCount('orderTextRows', 3);

        $this->actingAs($admin);
        Livewire::test(OrderAmazonPaste::class)
            ->set('orderText', "SKU: FCAMZ1716")->call('parseOrderText')
            ->assertSee('Wait')->assertViewHas('orderTextResults', fn ($rows) => $rows[0]['designs']->pluck('image_link')->all() === ['https://example.com/owner', 'https://example.com/other']);
    }

    public function test_order_product_matches_fulfillment_and_qty(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $glass = Product::where('slug', 'glass')->firstOrFail();
        SkuOrderItem::create(['user_id' => $user->id, 'product_id' => $glass->id, 'sku' => 'FCAMZ1716', 'image_link' => 'https://example.com/design', 'source' => 'manual_import']);
        OrderProduct::create(['source_id' => 11, 'fulfillment_type' => 'FBM', 'order_product_id' => 12345, 'product_name' => '1-side Printed Glass Ornament 3in']);
        OrderProduct::create(['source_id' => 12, 'fulfillment_type' => 'FBA', 'order_product_id' => 67890, 'product_name' => '1-side Printed Glass Ornament 3in']);
        OrderProduct::create(['source_id' => 13, 'fulfillment_type' => 'FBM', 'order_product_id' => 60616, 'product_name' => '2-side Printed Glass Ornament 3in']);
        OrderProduct::create(['source_id' => 14, 'fulfillment_type' => 'FBM', 'order_product_id' => 35503, 'product_name' => 'Holo Broken Glass Vinyl Sticker 10in']);
        $this->actingAs($user);
        Livewire::test(OrderAmazonPaste::class)
            ->set('orderText', "SKU: FCAMZ1716\nPhone: 555-0100\nOrder Item ID: 123\n2\t$20.56")
            ->call('parseOrderText')->assertSet('orderTextQuantity', '2')
            ->assertSee('12345')->assertSee('555-0100')
            ->set('orderTextFbaOnly', true)->assertSee('67890')
            ->assertSeeInOrder(['SKU', 'Order ID', 'Order Product ID', 'Qty', 'Design Link', 'TO_NAME', 'TO_COMPANY', 'TO_PHONE', 'TO_ADDRESS1'])
            ->call('clearOrderTextInput')->set('orderText', "SKU: FCAMZ1716\n4\t$20.56")->call('parseOrderText')->assertSet('orderTextQuantity', '');
    }

    public function test_multiple_orders_can_be_added_and_removed(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $first = "Order details  Order ID: # 114-7037254-1645825\nSKU: FIRST\nCustomizations:\nADD YEAR: 2026\nCondition: New";
        $second = "Order details  Order ID: # 114-7037254-1645826\nSKU: SECOND\nCustomizations:\nCOLOR: Red\nCondition: New";
        Livewire::test(OrderAmazonPaste::class)
            ->set('orderText', $first)->call('parseOrderText')->assertSet('orderTextLocked', true)
            ->assertCount('orderTextRows', 1)->call('clearOrderTextInput')->assertSet('orderText', '')
            ->assertSet('orderTextLocked', false)->assertCount('orderTextRows', 1)
            ->set('orderText', $second)->call('parseOrderText')->assertCount('orderTextRows', 2)
            ->assertSee('114-7037254-1645825')->assertSee('114-7037254-1645826')
            ->assertSee('2026')->assertSee('Red')->call('removeOrderTextRow', 0)
            ->assertCount('orderTextRows', 1)->assertSee('114-7037254-1645826')
            ->call('clearOrderTextRows')->assertCount('orderTextRows', 0);
        Livewire::test(OrderAmazonPaste::class)
            ->set('orderText', $first."\n".$second)->call('parseOrderText')->assertCount('orderTextRows', 2);
    }

    public function test_gift_box_rows_follow_the_parent_and_are_exported(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);
        session()->put('order_amazon_paste.'.$user->id, [
            'orderTextApproved' => [true, true, true],
            'orderTextTemplateImages' => ['/storage/t1.png', '/storage/t2.png', '/storage/t3.png'],
            'orderTextMasterDriveLinks' => ['https://drive.google.com/1', 'https://drive.google.com/2', 'https://drive.google.com/3'],
        ]);
        $text = "Order details Order ID: # 114-7037254-1645825\nSKU: FIRST\nCustomizations:\nWould you prefer your item to come with a gift box?: BOX\nCondition: New\nSKU: SECOND\nCustomizations:\nWould you prefer your item to come with a gift box?: BOX WITH BOW\nCondition: New\nSKU: THIRD\nCustomizations:\nWould you prefer your item to come with a gift box?: No, thanks\nCondition: New";
        $component = Livewire::test(OrderAmazonPaste::class)->set('orderText', $text)->call('parseOrderText');
        $this->assertStringContainsString('59046', $component->html());
        $this->assertStringContainsString('70578', $component->html());
        $component->call('exportOrderText')->assertFileDownloaded();
        $history = HistoryOrderReport::where('user_id', $user->id)->where('order_id', '114-7037254-1645825')->firstOrFail();
        $exported = $history->report_data['order_text_rows'];
        $this->assertCount(5, $exported);
        $this->assertSame(['BOX', '', '59046'], array_slice($exported[1]['values'], 0, 3));
        $this->assertSame(['BOX WITH BOW', '', '70578'], array_slice($exported[3]['values'], 0, 3));
        $component->call('removeOrderTextRow', 0)->assertCount('orderTextRows', 2);
        $this->assertStringNotContainsString('59046', $component->html());
        $this->assertStringContainsString('70578', $component->html());
        $component->set('orderTextFbaOnly', true);
        $this->assertStringNotContainsString('70578', $component->html());
    }

    public function test_export_saves_orders_and_counts_each_export_once(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $other = User::factory()->create(['is_admin' => false]);
        $user->products()->attach(Product::where('slug', 'order-amazon-paste')->value('id'));
        $other->products()->attach(Product::where('slug', 'order-amazon-paste')->value('id'));
        $this->actingAs($user);
        session()->put('order_amazon_paste.'.auth()->id(), ['orderTextApproved' => [true, true], 'orderTextTemplateImages' => ['/storage/t1.png', '/storage/t2.png'], 'orderTextMasterDriveLinks' => ['https://drive.google.com/file/d/one/view', 'https://drive.google.com/file/d/two/view']]);
        $first = "Order details Order ID: # 114-7037254-1645825\nShip to\nErin Salmon\n123 Main Street\nBoston, MA 02110\nAddress Type: Residential\nSKU: FIRST\nCustomizations:\nADD NAME: Alice & Bob\nCondition: New\nOrder Item ID: 123\n1\t$20.56";
        $second = "Order details Order ID: # 114-7037254-1645826\nSKU: SECOND\nCustomizations:\nCOLOR: Red\nCondition: New\nOrder Item ID: 124\n2\t$10.00";
        $component = Livewire::test(OrderAmazonPaste::class)
            ->set('orderText', $first."\n".$second)->call('parseOrderText')
            ->call('exportOrderText')->assertFileDownloaded();
        $history = HistoryOrderReport::where('user_id', $user->id)->where('order_id', '114-7037254-1645825')->firstOrFail();
        $this->assertSame(1, $history->report_data['export_count']);
        $this->assertSame('Erin Salmon', $history->report_data['to_name']);
        $this->assertSame('Alice & Bob', $history->report_data['order_text_rows'][0]['customizations']['ADD NAME']);
        $this->assertDatabaseHas('history_order_report', ['user_id' => $user->id, 'order_id' => '114-7037254-1645826']);
        $component->call('exportOrderText')->assertFileDownloaded();
        $this->assertSame(2, $history->refresh()->report_data['export_count']);
        $this->actingAs($other);
        session()->put('order_amazon_paste.'.auth()->id(), ['orderTextApproved' => [true, true], 'orderTextTemplateImages' => ['/storage/t1.png', '/storage/t2.png'], 'orderTextMasterDriveLinks' => ['https://drive.google.com/file/d/one/view', 'https://drive.google.com/file/d/two/view']]);
        Livewire::test(OrderAmazonPaste::class)->set('orderText', $first)
            ->call('parseOrderText')->call('exportOrderText')->assertFileDownloaded();
        $this->assertSame(2, $history->refresh()->report_data['export_count']);
        $this->assertSame(1, HistoryOrderReport::where('user_id', $other->id)->where('order_id', $history->order_id)->firstOrFail()->report_data['export_count']);
    }

    public function test_export_rejects_missing_order_id_without_saving(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        Livewire::test(OrderAmazonPaste::class)->set('orderText', 'SKU: MISSING')
            ->call('parseOrderText')->call('exportOrderText')->assertHasErrors(['orderTextExport']);
        $this->assertDatabaseCount('history_order_report', 0);
    }

    public function test_multiple_items_share_order_and_keep_individual_details_on_export(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);
        session()->put('order_amazon_paste.'.auth()->id(), ['orderTextApproved' => [true, true], 'orderTextTemplateImages' => ['/storage/t1.png', '/storage/t2.png'], 'orderTextMasterDriveLinks' => ['https://drive.google.com/file/d/one/view', 'https://drive.google.com/file/d/two/view']]);
        $text = "Order details  Order ID: # 114-2330669-0484229\nShip to\nHelen Cagide\n555 MOUNT PROSPECT AVE APT 4G\nNEWARK, NJ 07104-1542\nAddress Type: Residential\nPhone: 555-0100\nOrder contents\nWaiting for pickup\nSKU: FCAMZ396\nCustomizations:\nADD YEAR: 2027\nCustomization Information\nOrder Item ID: 173256582397081\n1\t$16.57\nItem total:$21.40\nWaiting for pickup\nSKU: FCAMZ397\nCustomizations:\nADD YEAR: 2028\nCustomization Information\nOrder Item ID: 173256582397041\n2\t$16.57";
        $component = Livewire::test(OrderAmazonPaste::class)->set('orderText', $text)
            ->call('parseOrderText')->assertCount('orderTextRows', 2)
            ->assertSet('orderTextRows.0.sku', 'FCAMZ396')->assertSet('orderTextRows.1.sku', 'FCAMZ397')
            ->assertSet('orderTextRows.0.quantity', '1')->assertSet('orderTextRows.1.quantity', '2')
            ->assertSet('orderTextRows.0.customizations', 'ADD YEAR: 2027')->assertSet('orderTextRows.1.customizations', 'ADD YEAR: 2028')
            ->assertSet('orderTextRows.1.id', '114-2330669-0484229')->assertSet('orderTextRows.1.shipTo', "Helen Cagide\n555 MOUNT PROSPECT AVE APT 4G\nNEWARK, NJ 07104-1542")
            ->call('exportOrderText')->assertFileDownloaded();
        $history = HistoryOrderReport::where('user_id', $user->id)->where('order_id', '114-2330669-0484229')->firstOrFail();
        $this->assertCount(2, $history->report_data['order_text_rows']);
        $this->assertSame(1, $history->report_data['export_count']);
        $component->call('exportOrderText');
        $this->assertSame(2, $history->refresh()->report_data['export_count']);
    }

    public function test_tracking_upload_is_public_and_reused_on_repeat_export(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $user->products()->attach(Product::where('slug', 'order-amazon-paste')->value('id'));
        $this->actingAs($user);
        session()->put('order_amazon_paste.'.auth()->id(), ['orderTextApproved' => [true, true], 'orderTextTemplateImages' => ['/storage/t1.png', '/storage/t2.png'], 'orderTextMasterDriveLinks' => ['https://drive.google.com/file/d/one/view', 'https://drive.google.com/file/d/two/view']]);
        $this->mock(GoogleDriveService::class, function ($mock): void {
            $mock->shouldReceive('uploadLocalFile')->once()->withArgs(fn ($path, $name, $type, $folder, $public) => $public === true && $folder === null)
                ->andReturn('https://drive.google.com/file/d/tracking/view');
        });
        $component = Livewire::test(OrderAmazonPaste::class)
            ->set('orderText', "Order details Order ID: # 114-7037254-1645825\nSKU: FIRST")
            ->call('parseOrderText')->set('orderTextTrackingFiles.0', UploadedFile::fake()->create('label.pdf', 10, 'application/pdf'))
            ->call('exportOrderText')->assertFileDownloaded()->call('exportOrderText')->assertFileDownloaded();
        $history = HistoryOrderReport::where('user_id', $user->id)->where('order_id', '114-7037254-1645825')->firstOrFail();
        $this->assertSame('https://drive.google.com/file/d/tracking/view', $history->report_data['tracking_id']);
        $this->assertSame('https://drive.google.com/file/d/tracking/view', $history->report_data['order_text_rows'][0]['values'][5]);
        $this->assertSame(2, $history->report_data['export_count']);
    }

    public function test_deleting_a_row_keeps_tracking_file_with_its_order(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        session()->put('order_amazon_paste.'.auth()->id(), ['orderTextApproved' => [true, true], 'orderTextTemplateImages' => ['/storage/t1.png', '/storage/t2.png'], 'orderTextMasterDriveLinks' => ['https://drive.google.com/file/d/one/view', 'https://drive.google.com/file/d/two/view']]);
        $first = "Order details Order ID: # 114-7037254-1645825\nSKU: FIRST";
        $second = "Order details Order ID: # 114-7037254-1645826\nSKU: SECOND";
        $component = Livewire::test(OrderAmazonPaste::class)
            ->set('orderText', $first."\n".$second)->call('parseOrderText')
            ->set('orderTextTrackingFiles.1', UploadedFile::fake()->create('second.pdf', 10, 'application/pdf'))
            ->call('removeOrderTextRow', 0)->assertSet('orderTextRows.0.sku', 'SECOND');
        $this->assertNotNull($component->get('orderTextTrackingFiles.0'));
        $this->mock(GoogleDriveService::class, function ($mock): void {
            $mock->shouldReceive('uploadLocalFile')->once()->withArgs(fn ($path, $name) => str_starts_with($name, '114-7037254-1645826-tracking-'))
                ->andReturn('https://drive.google.com/file/d/second/view');
        });
        $component->call('exportOrderText')->assertFileDownloaded();
        $this->assertSame('https://drive.google.com/file/d/second/view', HistoryOrderReport::where('order_id', '114-7037254-1645826')->firstOrFail()->report_data['tracking_id']);
    }

    public function test_tracking_upload_failure_blocks_export_and_history(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        session()->put('order_amazon_paste.'.auth()->id(), ['orderTextApproved' => [true, true], 'orderTextTemplateImages' => ['/storage/t1.png', '/storage/t2.png'], 'orderTextMasterDriveLinks' => ['https://drive.google.com/file/d/one/view', 'https://drive.google.com/file/d/two/view']]);
        $this->mock(GoogleDriveService::class, function ($mock): void {
            $mock->shouldReceive('uploadLocalFile')->once()->andThrow(new \RuntimeException('Drive unavailable'));
        });
        Livewire::test(OrderAmazonPaste::class)
            ->set('orderText', "Order details Order ID: # 114-7037254-1645825\nSKU: FIRST")
            ->call('parseOrderText')->set('orderTextTrackingFiles.0', UploadedFile::fake()->create('label.pdf', 10, 'application/pdf'))
            ->call('exportOrderText')->assertHasErrors(['orderTextExport']);
        $this->assertDatabaseCount('history_order_report', 0);
    }

    public function test_template_visibility_toggle_is_shared_by_all_order_cards(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);
        $row = ['id' => '114-7037254-1645825', 'sku' => 'FIRST', 'shipTo' => '', 'company' => '', 'phone' => '', 'customizations' => '', 'quantity' => '1'];
        session()->put('order_amazon_paste.'.$user->id, ['orderTextRows' => [$row, array_replace($row, ['sku' => 'SECOND'])]]);
        $html = Livewire::test(OrderAmazonPaste::class)->html();
        $this->assertSame(1, substr_count($html, 'x-on:click="showOrderTemplate = !showOrderTemplate"'));
        $this->assertSame(1, substr_count($html, 'x-data="{ showOrderTemplate: true }"'));
    }

    public function test_template_guide_stays_visible_before_template_result_is_saved(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);
        Storage::disk('public')->put('admin/order-amazon-paste/bounds/glass.png', 'template');
        SkuOrderItem::create(['user_id' => $user->id, 'product_id' => Product::where('slug', 'glass')->firstOrFail()->id, 'sku' => 'FIRST', 'image_link' => 'https://example.com/source.png', 'source' => 'manual_import']);
        session()->put('order_amazon_paste.'.$user->id, [
            'orderTextRows' => [['id' => '114-7037254-1645825', 'sku' => 'FIRST', 'shipTo' => '', 'company' => '', 'phone' => '', 'customizations' => '', 'quantity' => '1']],
            'orderTextMasterImages' => ['/storage/master.png'],
        ]);
        $html = Livewire::test(OrderAmazonPaste::class)->html();
        $templatePanel = strstr($html, '>3. Template</h5>');
        $this->assertStringContainsString('Chưa lưu ảnh Template.', $templatePanel);
        $this->assertStringContainsString('x-show="showOrderTemplate"', $templatePanel);
        $this->assertStringContainsString('order-amazon-paste%2Fbounds%2Fglass.png', $templatePanel);
    }

    public function test_template_panel_does_not_show_master_before_template_is_saved(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);
        session()->put('order_amazon_paste.'.$user->id, [
            'orderTextRows' => [['id' => '114-7037254-1645825', 'sku' => 'FIRST', 'shipTo' => '', 'company' => '', 'phone' => '', 'customizations' => '', 'quantity' => '1']],
            'orderTextMasterImages' => ['/storage/master.png'],
        ]);
        $html = Livewire::test(OrderAmazonPaste::class)->html();
        $templatePanel = strstr($html, '>3. Template</h5>');
        $this->assertNotFalse($templatePanel);
        $this->assertStringContainsString('Chưa lưu ảnh Template.', $templatePanel);
        $this->assertStringNotContainsString('Ảnh Create Master SKU FIRST', $templatePanel);
        $this->assertStringNotContainsString('Duyệt ảnh', $html);
    }

    public function test_master_cannot_be_approved_until_template_is_saved(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);
        session()->put('order_amazon_paste.'.$user->id, [
            'orderTextRows' => [['id' => '114-7037254-1645825', 'sku' => 'FIRST', 'shipTo' => '', 'company' => '', 'phone' => '', 'customizations' => '', 'quantity' => '1']],
            'orderTextMasterImages' => ['/storage/generated/master.png'],
        ]);
        Livewire::test(OrderAmazonPaste::class)->assertDontSee('Duyệt ảnh')->call('approveOrderTextMaster', 0)
            ->assertHasErrors(['orderTextMaster'])->assertSet('orderTextApproved', []);
    }

    public function test_legacy_approval_without_template_cannot_export(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);
        session()->put('order_amazon_paste.'.$user->id, [
            'orderTextRows' => [['id' => '114-7037254-1645825', 'sku' => 'FIRST', 'shipTo' => '', 'company' => '', 'phone' => '', 'customizations' => '', 'quantity' => '1']],
            'orderTextMasterImages' => ['/storage/generated/master.png'],
            'orderTextApproved' => [true],
        ]);
        $this->mock(GoogleDriveService::class, fn ($mock) => $mock->shouldNotReceive('uploadLocalFile'));
        Livewire::test(OrderAmazonPaste::class)->call('exportOrderText')->assertHasErrors(['orderTextExport']);
        $this->assertDatabaseCount('history_order_report', 0);
    }

    public function test_approval_uploads_the_matching_template_and_optional_tracking(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);
        $row = ['id' => '114-7037254-1645825', 'sku' => 'FIRST', 'shipTo' => '', 'company' => '', 'phone' => '', 'customizations' => '', 'quantity' => '1'];
        $template = 'order-amazon-paste/'.$user->id.'/second.png';
        Storage::disk('public')->put($template, 'image');
        session()->put('order_amazon_paste.'.$user->id, [
            'orderTextRows' => [$row, array_replace($row, ['sku' => 'SECOND'])],
            'orderTextTemplateImages' => [0 => '/storage/first.png', 1 => '/storage/'.$template],
            'orderTextApproved' => [0 => true],
            'orderTextMasterDriveLinks' => [0 => 'https://drive.google.com/file/d/first/view'],
        ]);
        $this->mock(GoogleDriveService::class, function ($mock) use ($template): void {
            $mock->shouldReceive('uploadLocalFile')->once()->withArgs(fn ($path, $name, $type, $folder, $public) => $path === Storage::disk('public')->path($template) && $name === 'SECOND-114-7037254-1645825-master.png' && $public === true)->andReturn('https://drive.google.com/file/d/design/view');
            $mock->shouldReceive('uploadLocalFile')->once()->withArgs(fn ($path, $name, $type, $folder, $public) => str_starts_with($name, '114-7037254-1645825-tracking-') && $public === true)->andReturn('https://drive.google.com/file/d/tracking/view');
        });
        Livewire::test(OrderAmazonPaste::class)
            ->set('orderTextTrackingFiles.1', UploadedFile::fake()->create('label.pdf', 10, 'application/pdf'))
            ->call('approveOrderTextMaster', 1)->assertHasNoErrors()
            ->assertSet('orderTextApproved.1', true)
            ->assertSet('orderTextMasterDriveLinks.1', 'https://drive.google.com/file/d/design/view')
            ->assertSet('orderTextTrackingLinks.1', 'https://drive.google.com/file/d/tracking/view')
            ->call('exportOrderText')->assertFileDownloaded();
        $rows = HistoryOrderReport::where('order_id', $row['id'])->firstOrFail()->report_data['order_text_rows'];
        $this->assertSame('https://drive.google.com/file/d/design/view', $rows[1]['values'][4]);
        $this->assertSame('https://drive.google.com/file/d/tracking/view', $rows[1]['values'][5]);
        $this->assertSame('https://drive.google.com/file/d/first/view', $rows[0]['values'][4]);
    }

    public function test_failed_tracking_upload_can_retry_without_uploading_template_twice(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);
        $template = 'order-amazon-paste/'.$user->id.'/template.png';
        Storage::disk('public')->put($template, 'image');
        session()->put('order_amazon_paste.'.$user->id, [
            'orderTextRows' => [['id' => '114-7037254-1645825', 'sku' => 'FIRST', 'shipTo' => '', 'company' => '', 'phone' => '', 'customizations' => '', 'quantity' => '1']],
            'orderTextTemplateImages' => ['/storage/'.$template],
        ]);
        $this->mock(GoogleDriveService::class, function ($mock): void {
            $mock->shouldReceive('uploadLocalFile')->once()->withArgs(fn ($path, $name) => str_ends_with($name, '-master.png'))->andReturn('https://drive.google.com/file/d/design/view');
            $mock->shouldReceive('uploadLocalFile')->twice()->withArgs(fn ($path, $name) => str_contains($name, '-tracking-'))->andReturnUsing(function () {
                static $attempt = 0;
                if (++$attempt === 1) throw new \RuntimeException('Drive unavailable');
                return 'https://drive.google.com/file/d/tracking/view';
            });
        });
        Livewire::test(OrderAmazonPaste::class)
            ->set('orderTextTrackingFiles.0', UploadedFile::fake()->create('label.pdf', 10, 'application/pdf'))
            ->call('approveOrderTextMaster', 0)->assertHasErrors(['orderTextMaster'])->assertSet('orderTextApproved', [])
            ->call('approveOrderTextMaster', 0)->assertHasNoErrors()->assertSet('orderTextApproved.0', true)
            ->assertSet('orderTextTrackingLinks.0', 'https://drive.google.com/file/d/tracking/view');
    }

    public function test_template_image_is_uploaded_on_approval_and_reused_on_export(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);
        UserApiCredential::create(['user_id' => $user->id, 'provider_key' => 'cheapkeyai', 'name' => 'test', 'key_api' => 'sk-test', 'is_active' => true]);
        SkuOrderItem::create(['user_id' => $user->id, 'sku' => 'FIRST', 'image_link' => 'https://drive.google.com/file/d/source/view', 'source' => 'manual_import']);
        Storage::disk('public')->put('generated/master.png', 'image');
        $template = UploadedFile::fake()->image('template.png', 100, 100);
        $templateBytes = file_get_contents($template->getRealPath());
        Storage::disk('public')->put('admin/order-amazon-paste/bounds/glass.png', $templateBytes);
        $this->mock(ApiKeyImageGenerator::class, function ($mock): void {
            $mock->shouldReceive('generate')->once()->withArgs(fn ($user, $providerKey, $imageUri, $prompt, $folder, $functionKey) => $providerKey === 'cheapkeyai' && str_contains($prompt, 'ADD NAME: Alice') && ! str_contains($prompt, 'ADD YEAR:'))
                ->andReturn('/storage/generated/master.png');
        });
        $this->mock(GoogleDriveService::class, function ($mock): void {
            $mock->shouldReceive('uploadLocalFile')->once()->andReturn('https://drive.google.com/file/d/master/view');
        });
        $component = Livewire::test(OrderAmazonPaste::class)
            ->set('orderText', "Order details Order ID: # 114-7037254-1645825\nSKU: FIRST\nCustomizations:\nADD NAME: Alice\nCondition: New")
            ->call('parseOrderText')->call('generateOrderTextMaster', 0)->call('saveOrderTextTemplate', 0, 'glass', 'data:image/png;base64,'.base64_encode($templateBytes))
            ->assertSet('orderTextApproved', [])->assertSee('Wait')->call('approveOrderTextMaster', 0)
            ->assertSee('https://drive.google.com/file/d/master/view')->call('exportOrderText')->assertFileDownloaded()->assertSee('https://drive.google.com/file/d/master/view')->call('exportOrderText')->assertFileDownloaded();
        $history = HistoryOrderReport::where('order_id', '114-7037254-1645825')->firstOrFail();
        $this->assertSame('https://drive.google.com/file/d/master/view', $history->report_data['link_design']);
    }

    public function test_master_uses_source_shared_prompt_and_personalization(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);
        UserApiCredential::create(['user_id' => $user->id, 'provider_key' => 'cheapkeyai', 'name' => 'test', 'key_api' => 'sk-test', 'is_active' => true]);
        $source = 'https://drive.google.com/file/d/source/view';
        SkuOrderItem::create(['user_id' => $user->id, 'sku' => 'FIRST', 'image_link' => $source, 'source' => 'manual_import']);
        ListingPromptOverride::create(['product_id' => Product::where('slug', 'order-amazon-paste')->value('id'), 'marketplace' => 'order-master', 'content' => 'Shared instructions', 'updated_by' => $user->id]);
        ListingPromptOverride::create(['product_id' => Product::where('slug', 'order-amazon-paste')->value('id'), 'marketplace' => 'order-model', 'content' => 'gpt-image-1', 'updated_by' => $user->id]);
        $capturedPrompt = '';
        $capturedModel = null;
        $this->mock(ApiKeyImageGenerator::class, function ($mock) use ($source, &$capturedPrompt, &$capturedModel): void {
            $mock->shouldReceive('generate')->once()->withArgs(function (...$arguments) use ($source, &$capturedPrompt, &$capturedModel): bool {
                [$user, $providerKey, $imageUri, $prompt] = $arguments;
                $capturedModel = $arguments[7] ?? null;
                $capturedPrompt = $prompt;
                return $providerKey === 'cheapkeyai' && $imageUri === $source;
            })->andReturn('/storage/generated/master.png');
        });
        Livewire::test(OrderAmazonPaste::class)
            ->set('orderText', "Order details Order ID: # 114-7037254-1645825\nSKU: FIRST\nCustomizations:\nADD NAME (example: DAVID - TOM - ALEX ): Helen\nADD YEAR: 2027\nCondition: New")
            ->call('parseOrderText')->assertSet('orderTextReplacementSources.0.name', 'Name')->assertSet('orderTextReplacementSources.0.year', 'Year')
            ->set('orderTextReplacementSources.0.name', 'Math')->set('orderTextReplacementSources.0.year', '2024')->call('generateOrderTextMaster', 0)
            ->assertSet('orderTextMasterImages.0', '/storage/generated/master.png')->assertSet('orderTextApproved', []);
        $this->assertSame('gpt-image-1', $capturedModel);
        $this->assertStringContainsString('Shared instructions', $capturedPrompt);
        $this->assertStringContainsString('ADD NAME: Helen', $capturedPrompt);
        $this->assertStringContainsString('ADD YEAR: 2027', $capturedPrompt);
        $this->assertStringContainsString('Replace only the exact text "Math" with "Helen"', $capturedPrompt);
        $this->assertStringContainsString('Replace only the exact text "2024" with "2027"', $capturedPrompt);
        $this->assertStringContainsString('Follow each instruction below', $capturedPrompt);
    }

    public function test_master_adds_year_without_replacing_existing_text(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);
        UserApiCredential::create(['user_id' => $user->id, 'provider_key' => 'cheapkeyai', 'name' => 'test', 'key_api' => 'sk-test', 'is_active' => true]);
        SkuOrderItem::create(['user_id' => $user->id, 'sku' => 'FIRST', 'image_link' => 'https://drive.google.com/file/d/source/view', 'source' => 'manual_import']);
        $prompt = '';
        $this->mock(ApiKeyImageGenerator::class, function ($mock) use (&$prompt): void {
            $mock->shouldReceive('generate')->once()->withArgs(function (...$args) use (&$prompt): bool { $prompt = $args[3]; return true; })->andReturn('/storage/generated/master.png');
        });
        Livewire::test(OrderAmazonPaste::class)
            ->set('orderText', "Order details Order ID: # 114-7037254-1645825\nSKU: FIRST\nCustomizations:\nADD NAME: Helen\nADD YEAR: 2027\nCondition: New")
            ->call('parseOrderText')->set('orderTextReplacementSources.0.year_mode', 'add')
            ->set('orderTextReplacementSources.0.year_position', 'below the name')->call('generateOrderTextMaster', 0);
        $this->assertStringContainsString('Replace only the exact text "Name" with "Helen"', $prompt);
        $this->assertStringContainsString('ADD YEAR: 2027\nAdd this text at below the name', str_replace("\n", '\n', $prompt));
        $this->assertStringNotContainsString('Replace only the exact text "Year"', $prompt);
    }

    public function test_replacement_fields_hide_missing_values_and_remain_per_row(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);
        $row = ['id' => '114-7037254-1645825', 'sku' => 'FIRST', 'shipTo' => '', 'company' => '', 'phone' => '', 'customizations' => "ADD NAME: Helen\nADD YEAR: -", 'quantity' => '1'];
        session()->put('order_amazon_paste.'.$user->id, ['orderTextRows' => [$row, array_replace($row, ['sku' => 'SECOND'])]]);
        $component = Livewire::test(OrderAmazonPaste::class)->assertSee('Xử lý tên')->assertDontSee('Xử lý năm')->assertDontSee('ADD YEAR: -')
            ->set('orderTextReplacementSources.0.name', 'Math')->call('removeOrderTextRow', 0)->assertSet('orderTextReplacementSources.0.name', 'Name');
        $component->set('orderTextReplacementSources.0.name', 'Teacher');
        Livewire::test(OrderAmazonPaste::class)->assertSet('orderTextReplacementSources.0.name', 'Teacher');
    }

    public function test_master_uses_short_filename_without_order_subfolder(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);
        UserApiCredential::create(['user_id' => $user->id, 'provider_key' => 'cheapkeyai', 'name' => 'test', 'key_api' => 'sk-test', 'is_active' => true]);
        SkuOrderItem::create(['user_id' => $user->id, 'sku' => 'FIRST', 'image_link' => 'https://drive.google.com/file/d/source/view', 'source' => 'manual_import']);
        $folder = 'order-amazon-paste/'.$user->id.'/unknown';
        $this->mock(ApiKeyImageGenerator::class, function ($mock) use ($folder): void {
            $mock->shouldReceive('generate')->twice()->withArgs(function (...$arguments) use ($folder): bool {
                $this->assertSame($folder, $arguments[4]);
                Storage::disk('public')->put($folder.'/temporary.png', 'image');
                return true;
            })->andReturn('/storage/'.$folder.'/temporary.png');
        });
        $component = Livewire::test(OrderAmazonPaste::class)->set('orderText', "Order details Order ID: # 114-7037254-1645825\nSKU: FIRST")
            ->call('parseOrderText')->call('generateOrderTextMaster', 0);
        $expected = $folder.'/FIRST_'.$user->id.'_unknown_'.now()->format('dmY').'.png';
        $component->assertSet('orderTextMasterImages.0', '/storage/'.$expected);
        Storage::disk('public')->assertExists($expected);
        $component->call('clearOrderTextInput')->set('orderText', "Order details Order ID: # 114-7037254-1645826\nSKU: FIRST")
            ->call('parseOrderText')->call('generateOrderTextMaster', 1)
            ->assertSee('Tên ảnh đã tồn tại cho đơn khác');
        Storage::disk('public')->assertExists($expected);
        Storage::disk('public')->assertMissing($folder.'/temporary.png');
    }

    public function test_unapproved_image_cannot_export(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);
        UserApiCredential::create(['user_id' => $user->id, 'provider_key' => 'cheapkeyai', 'name' => 'test', 'key_api' => 'sk-test', 'is_active' => true]);
        $source = 'https://drive.google.com/file/d/source/view';
        SkuOrderItem::create(['user_id' => $user->id, 'sku' => 'FIRST', 'image_link' => $source, 'source' => 'manual_import']);
        $this->mock(ApiKeyImageGenerator::class, fn ($mock) => $mock->shouldReceive('generate')->once()->andReturn('/storage/generated/master.png'));
        $this->mock(GoogleDriveService::class, fn ($mock) => $mock->shouldNotReceive('uploadLocalFile'));
        Livewire::test(OrderAmazonPaste::class)
            ->set('orderText', "Order details Order ID: # 114-7037254-1645825\nSKU: FIRST")
            ->call('parseOrderText')->call('generateOrderTextMaster', 0)->call('exportOrderText')->assertHasErrors(['orderTextExport']);
        $this->assertDatabaseCount('history_order_report', 0);
    }

    public function test_cheapkey_balance_uses_own_key_from_another_page_and_handles_missing_key(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $other = User::factory()->create(['is_admin' => true]);
        UserApiCredential::create(['user_id' => $owner->id, 'provider_key' => 'cheapkeyai', 'function_key' => 'glass', 'name' => 'test', 'key_api' => 'sk-test', 'is_active' => true]);
        Http::fake(['*' => Http::response(['success' => true, 'data' => ['user_balance' => 4.5, 'key_unlimited_quota' => true]])]);
        $this->actingAs($owner);
        Livewire::test(OrderAmazonPaste::class)->assertSee('$4.500');
        $this->actingAs($other);
        Livewire::test(OrderAmazonPaste::class)->assertViewHas('cheapKeyAiBalance', null)->assertSee('null');
        Http::assertSentCount(1);
    }

    public function test_master_generation_requires_the_users_own_cheapkey(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);
        UserApiCredential::create(['user_id' => null, 'provider_key' => 'cheapkeyai', 'name' => 'shared', 'key_api' => 'sk-test', 'is_active' => true]);
        SkuOrderItem::create(['user_id' => $user->id, 'sku' => 'FIRST', 'image_link' => 'https://drive.google.com/file/d/source/view', 'source' => 'manual_import']);
        $this->mock(ApiKeyImageGenerator::class, fn ($mock) => $mock->shouldNotReceive('generate'));
        Livewire::test(OrderAmazonPaste::class)->set('orderText', "Order details Order ID: # 114-7037254-1645825\nSKU: FIRST")
            ->call('parseOrderText')->call('generateOrderTextMaster', 0)->assertSee('Chua co CheapKeyAI API key');
    }

    public function test_super_admin_can_set_shared_order_image_model(): void
    {
        $super = User::factory()->create(['is_admin' => true, 'role' => 'super_admin']);
        $admin = User::factory()->create(['is_admin' => true, 'role' => 'admin']);
        $productId = Product::where('slug', 'order-amazon-paste')->value('id');
        $this->actingAs($admin);
        Livewire::test(OrderAmazonPaste::class)->set('orderAmazonImageModel', 'gpt-image-1')->call('saveOrderAmazonImageModel')->assertForbidden();
        $this->assertDatabaseMissing('listing_prompt_overrides', ['product_id' => $productId, 'marketplace' => 'order-model']);
        $this->actingAs($super);
        Livewire::test(OrderAmazonPaste::class)->set('orderAmazonImageModel', 'invalid-model')->call('saveOrderAmazonImageModel')->assertHasErrors(['orderAmazonImageModel']);
        Livewire::test(OrderAmazonPaste::class)->set('orderAmazonImageModel', 'gpt-image-1')->call('saveOrderAmazonImageModel')->assertHasNoErrors()->assertDispatched('close-modal');
        $this->assertDatabaseHas('listing_prompt_overrides', ['product_id' => $productId, 'marketplace' => 'order-model', 'content' => 'gpt-image-1']);
    }

    public function test_only_super_admin_can_edit_prompt(): void
    {
        $user = User::factory()->create(['is_admin' => true, 'role' => 'admin']);
        $this->actingAs($user);
        Livewire::test(OrderAmazonPaste::class)->set('orderAmazonPrompt', 'Secret instruction')->call('saveOrderAmazonPrompt')->assertForbidden();
        $this->assertDatabaseMissing('listing_prompt_overrides', ['marketplace' => 'order-master']);
        $super = User::factory()->create(['is_admin' => true, 'role' => 'super_admin']);
        $this->actingAs($super);
        Livewire::test(OrderAmazonPaste::class)->set('orderAmazonPrompt', 'Shared private prompt')->call('saveOrderAmazonPrompt')->assertHasNoErrors()->assertDispatched('close-modal');
        $this->assertDatabaseHas('listing_prompt_overrides', ['marketplace' => 'order-master', 'content' => 'Shared private prompt']);
        $this->actingAs($user);
        Livewire::test(OrderAmazonPaste::class)->assertSet('orderAmazonPrompt', '')->assertDontSee('Shared private prompt');
    }

    public function test_super_admin_can_save_prompt_longer_than_5000_characters(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true, 'role' => 'super_admin']));
        $prompt = str_repeat('a', 6000);

        Livewire::test(OrderAmazonPaste::class)->set('orderAmazonPrompt', $prompt)->call('saveOrderAmazonPrompt')->assertHasNoErrors();
        $this->assertDatabaseHas('listing_prompt_overrides', ['marketplace' => 'order-master', 'content' => $prompt]);
    }

    public function test_draft_restores_for_same_user_and_is_isolated(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $other = User::factory()->create(['is_admin' => true]);
        $this->actingAs($owner);
        Livewire::test(OrderAmazonPaste::class)
            ->set('orderText', "Order details Order ID: # 114-7037254-1645825\nSKU: FIRST")
            ->call('parseOrderText')->assertCount('orderTextRows', 1);
        Livewire::test(OrderAmazonPaste::class)->assertCount('orderTextRows', 1)->assertSet('orderTextLocked', true);
        $this->actingAs($other);
        Livewire::test(OrderAmazonPaste::class)->assertCount('orderTextRows', 0);
        $this->actingAs($owner);
        Livewire::test(OrderAmazonPaste::class)->call('clearOrderTextRows');
        Livewire::test(OrderAmazonPaste::class)->assertCount('orderTextRows', 0)->assertSet('orderTextLocked', false);
    }

    public function test_empty_input_is_rejected(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        Livewire::test(OrderAmazonPaste::class)->call('parseOrderText')->assertHasErrors(['orderText']);
    }
}





