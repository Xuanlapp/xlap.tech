<?php

namespace Tests\Feature;

use App\Livewire\Pages\Order\OrderPage;
use App\Models\HistoryOrderReport;
use App\Models\OrderProduct;
use App\Models\Product;
use App\Models\SkuOrderItem;
use App\Models\User;
use App\Services\Google\GoogleDriveService;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrderTextTest extends TestCase
{
    use RefreshDatabase;

    public function test_extracts_fields_from_amazon_order_text(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $text = "Order details  Order ID: # 114-7037254-1645825 Custom Order\nShip to\n\nErin Salmon\nW7722 AQUARIUS DR\nPARDEEVILLE, WI 53954-9386\nAddress Type: Residential\nOrder contents\nBuy shipping\nProduct name\nMore information\nUnshipped\n\nCustom Glass Ornament | Personalized Christmas Tree Decor\nASIN: B0HNCLZL2R\nSKU: FCAMZ1716\nCustomizations:\nSurface 1:\nSELECT SIZES: 3 inches\nCHOOSE YOUR HANGING ROPE.: Red satin ribbon\nADD NAME (example: DAVID - TOM - ALEX ): Sawyer Salmon\nADD YEAR: October 24, 2026\nChoose Pack: Pack 1\nWould you prefer your item to come with a gift box?: BOX\nCustomization Information\nShow less\nCondition: New
Order Item ID: 173304455103881
1	$20.56";

        Livewire::test(OrderPage::class)
            ->set('showOrderTextModal', true)
            ->set('orderText', $text)
            ->call('parseOrderText')
            ->assertSet('showOrderTextModal', true)
            ->assertSet('orderTextId', '114-7037254-1645825')
            ->assertSet('orderTextShipTo', "Erin Salmon\nW7722 AQUARIUS DR\nPARDEEVILLE, WI 53954-9386")
            ->assertSet('orderTextProduct', 'Custom Glass Ornament | Personalized Christmas Tree Decor')
            ->assertSet('orderTextSku', 'FCAMZ1716')
            ->assertSet('orderTextQuantity', '1')
            ->assertSet('orderTextCustomizations', "Surface 1:\nSELECT SIZES: 3 inches\nCHOOSE YOUR HANGING ROPE.: Red satin ribbon\nADD NAME (example: DAVID - TOM - ALEX ): Sawyer Salmon\nADD YEAR: October 24, 2026\nChoose Pack: Pack 1\nWould you prefer your item to come with a gift box?: BOX")
            ->assertSeeInOrder(['TO_NAME', 'TO_ADDRESS1', 'TO_ADDRESS2', 'TO_CITY', 'TO_STATE', 'TO_POSTCODE', 'Erin Salmon', 'W7722 AQUARIUS DR', 'PARDEEVILLE', 'WI', '53954-9386'])
            ->assertSee('SELECT SIZES')
            ->assertSee('3 inches')
            ->assertSee('ADD NAME')
            ->assertDontSee('ADD NAME (example: DAVID - TOM - ALEX )')
            ->assertSee('Sawyer Salmon')
            ->assertSee('Would you prefer your item to come with a gift box?')
            ->assertSee('BOX')
            ->assertDontSee('wire:model="orderTextId"', false);
    }

    public function test_address_line_two_and_unrecognized_address_are_safe(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $component = Livewire::test(OrderPage::class)->set('showOrderTextModal', true);
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
        $component = Livewire::test(OrderPage::class)->set('showOrderTextModal', true)
            ->set('orderText', "SKU: FCAMZ1716")->call('parseOrderText')
            ->assertSee('https://example.com/owner')->assertDontSee('https://example.com/other');
        $component->call('clearOrderTextInput')->set('orderText', "SKU: MISSING")->call('parseOrderText')
            ->assertSee('Design Link')->assertCount('orderTextRows', 2);
        $component->call('clearOrderTextInput')->set('orderText', "Order without SKU")->call('parseOrderText')
            ->assertCount('orderTextRows', 3);

        $this->actingAs($admin);
        Livewire::test(OrderPage::class)->set('showOrderTextModal', true)
            ->set('orderText', "SKU: FCAMZ1716")->call('parseOrderText')
            ->assertSee('https://example.com/owner')->assertSee('https://example.com/other')
            ->assertSee('Owner')->assertSee('Other');
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
        Livewire::test(OrderPage::class)->set('showOrderTextModal', true)
            ->set('orderText', "SKU: FCAMZ1716\nPhone: 555-0100\nOrder Item ID: 123\n2\t$20.56")
            ->call('parseOrderText')->assertSet('orderTextQuantity', '2')
            ->assertViewHas('orderTextOrderProduct', fn ($product) => $product?->order_product_id === 12345)->assertSee('555-0100')
            ->set('orderTextFbaOnly', true)->assertViewHas('orderTextOrderProduct', fn ($product) => $product?->order_product_id === 67890)
            ->assertSeeInOrder(['SKU', 'Order ID', 'Order Product ID', 'Qty', 'Design Link', 'TO_NAME', 'TO_COMPANY', 'TO_PHONE', 'TO_ADDRESS1'])
            ->call('clearOrderTextInput')->set('orderText', "SKU: FCAMZ1716\n4\t$20.56")->call('parseOrderText')->assertSet('orderTextQuantity', '');
    }

    public function test_multiple_orders_can_be_added_and_removed(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $first = "Order details  Order ID: # 114-7037254-1645825\nSKU: FIRST\nCustomizations:\nADD YEAR: 2026\nCondition: New";
        $second = "Order details  Order ID: # 114-7037254-1645826\nSKU: SECOND\nCustomizations:\nCOLOR: Red\nCondition: New";
        Livewire::test(OrderPage::class)->set('showOrderTextModal', true)
            ->set('orderText', $first)->call('parseOrderText')->assertSet('orderTextLocked', true)
            ->assertCount('orderTextRows', 1)->call('clearOrderTextInput')->assertSet('orderText', '')
            ->assertSet('orderTextLocked', false)->assertCount('orderTextRows', 1)
            ->set('orderText', $second)->call('parseOrderText')->assertCount('orderTextRows', 2)
            ->assertSee('114-7037254-1645825')->assertSee('114-7037254-1645826')
            ->assertSee('2026')->assertSee('Red')->call('removeOrderTextRow', 0)
            ->assertCount('orderTextRows', 1)->assertSee('114-7037254-1645826')
            ->call('clearOrderTextRows')->assertCount('orderTextRows', 0);
        Livewire::test(OrderPage::class)->set('showOrderTextModal', true)
            ->set('orderText', $first."\n".$second)->call('parseOrderText')->assertCount('orderTextRows', 2);
    }

    public function test_export_saves_orders_and_counts_each_export_once(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $other = User::factory()->create(['is_admin' => false]);
        $this->actingAs($user);
        $first = "Order details Order ID: # 114-7037254-1645825\nShip to\nErin Salmon\n123 Main Street\nBoston, MA 02110\nAddress Type: Residential\nSKU: FIRST\nCustomizations:\nADD NAME: Alice & Bob\nCondition: New\nOrder Item ID: 123\n1\t$20.56";
        $second = "Order details Order ID: # 114-7037254-1645826\nSKU: SECOND\nCustomizations:\nCOLOR: Red\nCondition: New\nOrder Item ID: 124\n2\t$10.00";
        $component = Livewire::test(OrderPage::class)->set('showOrderTextModal', true)
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
        Livewire::test(OrderPage::class)->set('showOrderTextModal', true)->set('orderText', $first)
            ->call('parseOrderText')->call('exportOrderText')->assertFileDownloaded();
        $this->assertSame(2, $history->refresh()->report_data['export_count']);
        $this->assertSame(1, HistoryOrderReport::where('user_id', $other->id)->where('order_id', $history->order_id)->firstOrFail()->report_data['export_count']);
    }

    public function test_export_rejects_missing_order_id_without_saving(): void
    {
        $this->actingAs(User::factory()->create());
        Livewire::test(OrderPage::class)->set('showOrderTextModal', true)->set('orderText', 'SKU: MISSING')
            ->call('parseOrderText')->call('exportOrderText')->assertHasErrors(['orderTextExport']);
        $this->assertDatabaseCount('history_order_report', 0);
    }

    public function test_multiple_items_share_order_and_keep_individual_details_on_export(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);
        $text = "Order details  Order ID: # 114-2330669-0484229\nShip to\nHelen Cagide\n555 MOUNT PROSPECT AVE APT 4G\nNEWARK, NJ 07104-1542\nAddress Type: Residential\nPhone: 555-0100\nOrder contents\nWaiting for pickup\nSKU: FCAMZ396\nCustomizations:\nADD YEAR: 2027\nCustomization Information\nOrder Item ID: 173256582397081\n1\t$16.57\nItem total:$21.40\nWaiting for pickup\nSKU: FCAMZ397\nCustomizations:\nADD YEAR: 2028\nCustomization Information\nOrder Item ID: 173256582397041\n2\t$16.57";
        $component = Livewire::test(OrderPage::class)->set('showOrderTextModal', true)->set('orderText', $text)
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
        $this->actingAs($user);
        $this->mock(GoogleDriveService::class, function ($mock): void {
            $mock->shouldReceive('uploadLocalFile')->once()->withArgs(fn ($path, $name, $type, $folder, $public) => $public === true && $folder === null)
                ->andReturn('https://drive.google.com/file/d/tracking/view');
        });
        $component = Livewire::test(OrderPage::class)->set('showOrderTextModal', true)
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
        $this->actingAs(User::factory()->create(['is_admin' => false]));
        $first = "Order details Order ID: # 114-7037254-1645825\nSKU: FIRST";
        $second = "Order details Order ID: # 114-7037254-1645826\nSKU: SECOND";
        $component = Livewire::test(OrderPage::class)->set('showOrderTextModal', true)
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
        $this->actingAs(User::factory()->create(['is_admin' => false]));
        $this->mock(GoogleDriveService::class, function ($mock): void {
            $mock->shouldReceive('uploadLocalFile')->once()->andThrow(new \RuntimeException('Drive unavailable'));
        });
        Livewire::test(OrderPage::class)->set('showOrderTextModal', true)
            ->set('orderText', "Order details Order ID: # 114-7037254-1645825\nSKU: FIRST")
            ->call('parseOrderText')->set('orderTextTrackingFiles.0', UploadedFile::fake()->create('label.pdf', 10, 'application/pdf'))
            ->call('exportOrderText')->assertHasErrors(['orderTextExport']);
        $this->assertDatabaseCount('history_order_report', 0);
    }

    public function test_empty_input_is_rejected(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        Livewire::test(OrderPage::class)->call('parseOrderText')->assertHasErrors(['orderText']);
    }
}
