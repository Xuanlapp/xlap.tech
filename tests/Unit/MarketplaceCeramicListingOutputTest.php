<?php

namespace Tests\Unit;

use App\Services\Marketplace\MarketplaceListingMetadataService;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use Tests\TestCase;

class MarketplaceCeramicListingOutputTest extends TestCase
{
    public function test_it_accepts_ceramic_listing_fields_with_requested_character_counts(): void
    {
        $service = (new ReflectionClass(MarketplaceListingMetadataService::class))->newInstanceWithoutConstructor();
        $validate = new ReflectionMethod($service, 'validateCeramicAmazonPayload');
        $payload = [
            'title' => str_repeat('T', 70),
            'item_highlight' => str_repeat('H', 124),
            'generic_keyword' => str_repeat('k', 230),
            'description' => str_repeat('D', 1800),
        ];
        foreach (range(1, 5) as $number) {
            $payload["bullet_point_{$number}"] = '✨ '.str_repeat('B', 458);
        }

        $validate->invoke($service, $payload);
        $this->assertTrue(true);
    }

    public function test_it_rejects_ceramic_listing_fields_outside_character_limits(): void
    {
        $service = (new ReflectionClass(MarketplaceListingMetadataService::class))->newInstanceWithoutConstructor();
        $validate = new ReflectionMethod($service, 'validateCeramicAmazonPayload');
        $payload = [
            'title' => str_repeat('T', 69),
            'item_highlight' => str_repeat('H', 124),
            'generic_keyword' => str_repeat('k', 230),
            'description' => str_repeat('D', 1800),
        ];
        foreach (range(1, 5) as $number) {
            $payload["bullet_point_{$number}"] = '✨ '.str_repeat('B', 458);
        }

        try {
            $validate->invoke($service, $payload);
            $this->fail('Expected character-count validation to reject the title.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('title=69', $exception->getMessage());
        }
    }
    public function test_repair_instructions_include_validation_errors_and_previous_output(): void
    {
        $service = (new ReflectionClass(MarketplaceListingMetadataService::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod($service, 'ceramicRepairInstructions');
        $payload = ['bullet_point_1' => 'short text'];
        $message = $method->invoke($service, new RuntimeException('bullet_point_1=10 (expected 460-480)'), $payload);

        $this->assertStringContainsString('bullet_point_1=10', $message);
        $this->assertStringContainsString('short text', $message);
        $this->assertStringContainsString('complete replacement JSON', $message);
    }

    public function test_it_retries_ceramic_output_repairs_until_all_character_counts_pass(): void
    {
        $service = (new ReflectionClass(MarketplaceListingMetadataService::class))->newInstanceWithoutConstructor();
        $repair = new ReflectionMethod($service, 'repairCeramicPayload');
        $invalidPayload = [
            'title' => str_repeat('T', 70),
            'item_highlight' => str_repeat('H', 20),
            'generic_keyword' => str_repeat('k', 230),
            'description' => str_repeat('D', 1800),
        ];
        foreach (range(1, 5) as $number) {
            $invalidPayload["bullet_point_{$number}"] = '! '.str_repeat('B', 366);
        }
        $validPayload = $invalidPayload;
        foreach (range(1, 5) as $number) {
            $validPayload["bullet_point_{$number}"] = '! '.str_repeat('B', 458);
        }
        $calls = 0;
        $result = $repair->invoke($service, $invalidPayload, function () use (&$calls, $invalidPayload, $validPayload): array {
            $calls++;

            return $calls === 1 ? $invalidPayload : $validPayload;
        });
        $this->assertSame(2, $calls);
        $this->assertSame($validPayload, $result);
    }

    public function test_it_stops_after_the_configured_ceramic_repair_limit(): void
    {
        $service = (new ReflectionClass(MarketplaceListingMetadataService::class))->newInstanceWithoutConstructor();
        $repair = new ReflectionMethod($service, 'repairCeramicPayload');
        $payload = [
            'title' => str_repeat('T', 70),
            'item_highlight' => str_repeat('H', 20),
            'generic_keyword' => str_repeat('k', 230),
            'description' => str_repeat('D', 1800),
        ];
        foreach (range(1, 5) as $number) {
            $payload["bullet_point_{$number}"] = '! '.str_repeat('B', 366);
        }
        $calls = 0;
        try {
            $repair->invoke($service, $payload, function (RuntimeException $exception, array $previous) use (&$calls): array {
                $calls++;

                return $previous;
            });
            $this->fail('Expected invalid output to stop after the repair limit.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('bullet_point_1=368', $exception->getMessage());
            $this->assertSame(2, $calls);
        }
    }
    public function test_title_truncation_removes_the_partial_final_word(): void
    {
        $service = (new ReflectionClass(MarketplaceListingMetadataService::class))->newInstanceWithoutConstructor();
        $titleValue = new ReflectionMethod($service, 'titleValue');
        $title = 'Personalized Guinea Pig Memorial Glass Christmas Ornament, Custom Tree Decoration';
        $result = $titleValue->invoke($service, $title, 74);

        $this->assertLessThanOrEqual(74, mb_strlen($result));
        $this->assertDoesNotMatchRegularExpression('/\bDec$/', $result);
        $this->assertStringNotContainsString('Decoration', $result);
    }

}
