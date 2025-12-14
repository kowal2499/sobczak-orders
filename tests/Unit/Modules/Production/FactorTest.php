<?php

namespace App\Tests\Unit\Modules\Production;

use App\Entity\AgreementLine;
use App\Module\Production\Entity\Factor;
use App\Module\Production\Entity\FactorSource;
use App\Module\Production\Factor\FactorCalculator;
use App\Tests\Utilities\PrivateProperty;
use PHPUnit\Framework\TestCase;

class FactorTest extends TestCase
{
    private FactorCalculator $calcUnderTest;

    protected function setUp(): void
    {
        $this->calcUnderTest = new FactorCalculator();
    }

    public function testShouldReturnZeroForEmptyPoolInDepartmentMode(): void
    {
        // Given
        $agreementLine = $this->makeAgreementLineWithFactor(1.5);
        $factorsPool = [];
        // When
        $resultDpt = $this->calcUnderTest->calculate(
            $agreementLine,
            'dpt01',
            $factorsPool,
            FactorSource::FACTOR_ADJUSTMENT_RATIO
        );
        // Then
        $this->assertEquals(0, $resultDpt->factor);

        $this->assertEmpty($resultDpt->factorsStack);
    }

    public function testShouldReturnZeroForEmptyPoolInDepartmentBonusMode(): void
    {
        // Given
        $agreementLine = $this->makeAgreementLineWithFactor(1.5);
        $factorsPool = [];
        // When
        $resultDptBonus = $this->calcUnderTest->calculate(
            $agreementLine,
            'dpt01',
            $factorsPool,
            FactorSource::FACTOR_ADJUSTMENT_BONUS
        );
        // Then
        $this->assertEquals(0, $resultDptBonus->factor);
        $this->assertEmpty($resultDptBonus->factorsStack);
    }

    public function testShouldFallbackToAgreementValueWhenPoolIsEmptyInAgreementMode()
    {
        // Given
        $agreement = $this->makeAgreementLineWithFactor(2.0);
        $factorsPool = [];
        // When
        $result = $this->calcUnderTest->calculate(
            $agreement,
            null,
            $factorsPool,
            FactorSource::AGREEMENT_LINE
        );
        // Then
        $this->assertEquals(2.0, $result->factor);
        $this->assertCount(1, $result->factorsStack);
    }

    public function testShouldReturnPoolValueIfPollExistsInAgreementMode()
    {
        // Given
        $agreement = $this->makeAgreementLineWithFactor(2.0);
        $poolItem = $this->makeFactor(FactorSource::AGREEMENT_LINE, 3.5);
        // When
        $result = $this->calcUnderTest->calculate(
            $agreement,
            null,
            [$poolItem],
            FactorSource::AGREEMENT_LINE
        );
        // Then
        $this->assertEquals(3.5, $result->factor);
        $this->assertCount(1, $result->factorsStack);
    }

    private function makeAgreementLineWithFactor(float $factor): AgreementLine
    {
        $agreementLine = new AgreementLine();
        $agreementLine->setFactor($factor);
        PrivateProperty::setId($agreementLine);
        return $agreementLine;
    }

    private function makeFactor(FactorSource $source, float $value): Factor
    {
        $factor = new Factor();
        PrivateProperty::setId($factor);
        $factor->setSource($source);
        $factor->setFactorValue($value);
        if ($source === FactorSource::AGREEMENT_LINE) {
            $agreementLine = $this->makeAgreementLineWithFactor(1.0);
            $reflection = new \ReflectionClass(Factor::class);
            $property = $reflection->getProperty('agreementLine');
            $property->setAccessible(true);
            $property->setValue($factor, $agreementLine);
        }
        return $factor;
    }
}