<?php

namespace App\Tests\Unit\Modules\Agreement\Service;

use App\Module\Agreement\Service\AgreementLineTaggingPolicy;
use PHPUnit\Framework\TestCase;

class AgreementLineTaggingPolicyTest extends TestCase
{
    private AgreementLineTaggingPolicy $policy;

    protected function setUp(): void
    {
        $this->policy = new AgreementLineTaggingPolicy();
    }

    public function testReturnsCapacityExceededTagWhenFlagIsTrue(): void
    {
        // Given
        $productData = [
            'productId' => 123,
            'isCapacityExceeded' => true,
            'requiredDate' => '2026-03-15',
        ];

        // When
        $tags = $this->policy->getTagsForAgreementLine($productData);

        // Then
        $this->assertContains(
            AgreementLineTaggingPolicy::TAG_CAPACITY_EXCEEDED,
            $tags
        );
        $this->assertCount(1, $tags);
    }

    public function testReturnsEmptyArrayWhenCapacityNotExceeded(): void
    {
        // Given
        $productData = [
            'productId' => 123,
            'isCapacityExceeded' => false,
            'requiredDate' => '2026-03-15',
        ];

        // When
        $tags = $this->policy->getTagsForAgreementLine($productData);

        // Then
        $this->assertEmpty($tags);
    }

    public function testReturnsEmptyArrayWhenFlagIsMissing(): void
    {
        // Given - brak flagi w danych
        $productData = [
            'productId' => 123,
            'requiredDate' => '2026-03-15',
        ];

        // When
        $tags = $this->policy->getTagsForAgreementLine($productData);

        // Then
        $this->assertEmpty($tags);
    }

    public function testReturnsOnlyUniqueTagsWhenMultipleRulesMatch(): void
    {
        // Given - scenariusz na przyszłość gdy będzie więcej reguł
        $productData = [
            'isCapacityExceeded' => true,
        ];

        // When
        $tags = $this->policy->getTagsForAgreementLine($productData);

        // Then
        $uniqueTags = array_unique($tags);
        $this->assertCount(
            count($uniqueTags),
            $tags,
            'Policy should not return duplicate tags'
        );
    }
}
