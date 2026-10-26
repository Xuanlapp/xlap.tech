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
            'title' => str_repeat('T', 70),
            'item_highlight' => str_repeat('H', 124),
            'generic_keyword' => str_repeat('k', 230),
            'description' => str_repeat('D', 2001),
        ];
        foreach (range(1, 5) as $number) {
            $payload["bullet_point_{$number}"] = '✨ '.str_repeat('B', 458);
        }

        try {
            $validate->invoke($service, $payload);
            $this->fail('Expected validation to reject a field exceeding its maximum.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('description=2001', $exception->getMessage());
        }
    }
    public function test_it_accepts_ceramic_copy_shorter_than_the_former_minimums(): void
    {
        $service = (new ReflectionClass(MarketplaceListingMetadataService::class))->newInstanceWithoutConstructor();
        $validate = new ReflectionMethod($service, 'validateCeramicAmazonPayload');
        $payload = [
            'title' => str_repeat('T', 40),
            'item_highlight' => 'Short highlight',
            'generic_keyword' => 'ceramic ornament',
            'description' => 'A short, factual product description.',
        ];
        foreach (range(1, 5) as $number) {
            $payload["bullet_point_{$number}"] = '! Short, factual bullet.';
        }

        $validate->invoke($service, $payload);
        $this->assertTrue(true);
    }
    public function test_it_accepts_the_short_ceramic_output_lengths_reported_in_production(): void
    {
        $service = (new ReflectionClass(MarketplaceListingMetadataService::class))->newInstanceWithoutConstructor();
        $validate = new ReflectionMethod($service, 'validateCeramicAmazonPayload');
        $payload = [
            'title' => str_repeat('T', 69),
            'item_highlight' => 'A factual ceramic ornament highlight.',
            'generic_keyword' => str_repeat('k', 228),
            'description' => str_repeat('D', 542),
        ];
        foreach ([96, 100, 100, 77, 75] as $index => $length) {
            $payload['bullet_point_'.($index + 1)] = "! ".str_repeat('B', $length - 2);
        }

        $validate->invoke($service, $payload);
        $this->assertTrue(true);
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
            $invalidPayload["bullet_point_{$number}"] = '! '.str_repeat('B', 480);
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
            $payload["bullet_point_{$number}"] = '! '.str_repeat('B', 480);
        }
        $calls = 0;
        try {
            $repair->invoke($service, $payload, function (RuntimeException $exception, array $previous) use (&$calls): array {
                $calls++;

                return $previous;
            });
            $this->fail('Expected invalid output to stop after the repair limit.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('bullet_point_1=482', $exception->getMessage());
            $this->assertSame(2, $calls);
        }
    }
    public function test_it_repairs_unexpected_schema_keys(): void
    {
        $service = (new ReflectionClass(MarketplaceListingMetadataService::class))->newInstanceWithoutConstructor();
        $repair = new ReflectionMethod($service, 'repairCeramicPayload');
        $payload = [
            'title' => str_repeat('T', 71),
            'item_highlight' => str_repeat('H', 20),
            'generic_keyword' => str_repeat('k', 230),
            'description' => str_repeat('D', 1800),
            'unexpected_key' => 'extra',
        ];
        foreach (range(1, 5) as $number) {
            $payload["bullet_point_{$number}"] = '! '.str_repeat('B', 458);
        }
        $corrected = $payload;
        unset($corrected['unexpected_key']);
        $calls = 0;
        $result = $repair->invoke($service, $payload, function (RuntimeException $exception) use (&$calls, $corrected): array {
            $calls++;
            $this->assertStringContainsString('Extra: unexpected_key', $exception->getMessage());

            return $corrected;
        });
        $this->assertSame($corrected, $result);
        $this->assertSame(1, $calls);
    }

    public function test_it_repairs_overlong_titles_instead_of_failing_before_repair(): void
    {
        $service = (new ReflectionClass(MarketplaceListingMetadataService::class))->newInstanceWithoutConstructor();
        $validate = new ReflectionMethod($service, 'validateCeramicAmazonOutput');
        $payload = [
            'title' => str_repeat('T', 76),
            'item_highlight' => str_repeat('H', 20),
            'generic_keyword' => str_repeat('k', 230),
            'description' => str_repeat('D', 1800),
        ];
        foreach (range(1, 5) as $number) {
            $payload["bullet_point_{$number}"] = '! '.str_repeat('B', 458);
        }
        try {
            $validate->invoke($service, $payload);
            $this->fail('Expected the Ceramic title limit to reject 76 characters.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('title exceeds 75 characters', $exception->getMessage());
        }
    }
    public function test_it_trims_overlong_ceramic_fields_without_partial_words_and_drops_extra_keys(): void
    {
        $service = (new ReflectionClass(MarketplaceListingMetadataService::class))->newInstanceWithoutConstructor();
        $normalize = new ReflectionMethod($service, 'normalizeAmazonPayload');
        $validateSchema = new ReflectionMethod($service, 'validateAmazonPayload');
        $validateCeramic = new ReflectionMethod($service, 'validateCeramicAmazonPayload');
        $payload = [
            'title' => 'Personalized Christmas Ceramic Ornament For Family Garden Holiday Keepsake Decoration',
            'item_highlight' => str_repeat('Short highlight phrase ', 10),
            'generic_keyword' => implode('; ', array_fill(0, 30, 'ceramic ornament')),
            'description' => str_repeat('Verified product detail sentence. ', 100),
            'safety_check' => 'pass',
        ];
        foreach (range(1, 5) as $number) {
            $payload["bullet_point_{$number}"] = '! '.str_repeat('Verified feature sentence. ', 30);
        }

        $result = $normalize->invoke($service, $payload, true);
        $validateSchema->invoke($service, $result);
        $validateCeramic->invoke($service, $result);

        $this->assertArrayNotHasKey('safety_check', $result);
        $this->assertLessThanOrEqual(75, mb_strlen($result['title']));
        $this->assertLessThanOrEqual(124, mb_strlen($result['item_highlight']));
        $this->assertLessThanOrEqual(240, mb_strlen($result['generic_keyword']));
        $this->assertLessThanOrEqual(2000, mb_strlen($result['description']));
        foreach (range(1, 5) as $number) {
            $this->assertLessThanOrEqual(480, mb_strlen($result["bullet_point_{$number}"]));
            $this->assertMatchesRegularExpression('/^[^\p{L}\p{N}\s]\s/u', $result["bullet_point_{$number}"]);
        }
        $this->assertStringNotContainsString('Decoration', $result['title']);
        $this->assertStringEndsWith('ornament', $result['generic_keyword']);
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
