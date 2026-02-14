<?php

namespace App\Tests\Unit\Modules\Production;

use App\Entity\AgreementLine;
use App\Module\Production\Entity\Factor;
use App\Module\Production\Entity\FactorSource;
use App\Module\Production\Factor\FactorCalculator;
use App\Tests\Utilities\PrivateProperty;
use PHPUnit\Framework\TestCase;

class FactorCalculatorTest extends TestCase
{
    private FactorCalculator $calcUnderTest;

    protected function setUp(): void
    {
        $this->calcUnderTest = new FactorCalculator();
    }

    public function testShouldReturnAgreementValueForEmptyPoolInDepartmentMode(): void
    {
        // Given
        $agreementLine = $this->makeAgreementLineWithFactor(1.5);
        // When
        $resultDpt = $this->calcUnderTest->calculate(
            $agreementLine,
            'dpt01',
            [],
            FactorSource::FACTOR_ADJUSTMENT_RATIO
        );
        // Then
        $this->assertEquals(1.5, $resultDpt->factor);
        $this->assertCount(1, $resultDpt->factorsStack);
        $this->assertEquals(FactorSource::AGREEMENT_LINE, $resultDpt->factorsStack[0]->source);
        $this->assertEquals(1.5, $resultDpt->factorsStack[0]->value);
    }

    public function testShouldReturnAgreementValueForEmptyPoolInDepartmentBonusMode(): void
    {
        // Given
        $agreementLine = $this->makeAgreementLineWithFactor(1.5);
        // When
        $resultDptBonus = $this->calcUnderTest->calculate(
            $agreementLine,
            'dpt01',
            [],
            FactorSource::FACTOR_ADJUSTMENT_BONUS
        );
        // Then
        $this->assertEquals(1.5, $resultDptBonus->factor);
        $this->assertCount(1, $resultDptBonus->factorsStack);
        $this->assertEquals(FactorSource::AGREEMENT_LINE, $resultDptBonus->factorsStack[0]->source);
        $this->assertEquals(1.5, $resultDptBonus->factorsStack[0]->value);
    }

    public function testShouldFallbackToAgreementValueWhenPoolIsEmptyInAgreementMode()
    {
        // Given
        $agreement = $this->makeAgreementLineWithFactor(2.0);
        // When
        $result = $this->calcUnderTest->calculate(
            $agreement,
            null,
            [],
            FactorSource::AGREEMENT_LINE
        );
        // Then
        $this->assertEquals(2.0, $result->factor);
        $this->assertCount(1, $result->factorsStack);
        $this->assertEquals(FactorSource::AGREEMENT_LINE, $result->factorsStack[0]->source);
        $this->assertEquals(2.0, $result->factorsStack[0]->value);
    }

    public function testShouldReturnPoolValueIfPollExistsInAgreementMode()
    {
        // Given
        $agreement = $this->makeAgreementLineWithFactor(2.0);
        // When
        $result = $this->calcUnderTest->calculate(
            $agreement,
            null,
            [$this->makeFactor(FactorSource::AGREEMENT_LINE, 3.5)],
            FactorSource::AGREEMENT_LINE
        );
        // Then
        $this->assertEquals(3.5, $result->factor);
        $this->assertCount(1, $result->factorsStack);
        $this->assertEquals(FactorSource::AGREEMENT_LINE, $result->factorsStack[0]->source);
        $this->assertEquals(3.5, $result->factorsStack[0]->value);
    }

    public function testShouldReturnPoolValueIfPollHasManySourcesInAgreementMode()
    {

        // Given
        $agreement = $this->makeAgreementLineWithFactor(2.0);
        // When
        $result = $this->calcUnderTest->calculate(
            $agreement,
            null,
            [
                $this->makeFactor(FactorSource::AGREEMENT_LINE, 3.5),
                $this->makeFactor(FactorSource::FACTOR_ADJUSTMENT_RATIO, 0.2),
                $this->makeFactor(FactorSource::FACTOR_ADJUSTMENT_BONUS, 0.5),
            ],
            FactorSource::AGREEMENT_LINE
        );
        // Then
        $this->assertEquals(3.5, $result->factor);
        $this->assertCount(1, $result->factorsStack);
        $this->assertEquals(FactorSource::AGREEMENT_LINE, $result->factorsStack[0]->source);
        $this->assertEquals(3.5, $result->factorsStack[0]->value);
    }

    public function testShouldCalculateFactorInDepartmentMode()
    {
        // Given
        $agreement = $this->makeAgreementLineWithFactor(3.5);
        // When
        $result = $this->calcUnderTest->calculate(
            $agreement,
            'dpt01',
            [
                $this->makeFactor(
                    FactorSource::FACTOR_ADJUSTMENT_RATIO,
                    0.9,
                    'dpt01',
                    'some desc one'
                ),
                $this->makeFactor(
                    FactorSource::FACTOR_ADJUSTMENT_BONUS,
                    0.5,
                    'dpt01'
                ), // should be ignored
                $this->makeFactor(
                    FactorSource::FACTOR_ADJUSTMENT_RATIO,
                    0.7,
                    'dpt01',
                    'some desc two'
                ),
            ],
            FactorSource::FACTOR_ADJUSTMENT_RATIO
        );
        // Then
        $this->assertEquals(round(3.5 * 0.9 * 0.7, 2), $result->factor);
        $this->assertCount(3, $result->factorsStack);
        $this->assertEquals(FactorSource::AGREEMENT_LINE, $result->factorsStack[0]->source);
        $this->assertEquals(3.5, $result->factorsStack[0]->value);
        $this->assertNull($result->factorsStack[0]->departmentSlug);
        $this->assertEquals('', $result->factorsStack[0]->description);

        $this->assertEquals(FactorSource::FACTOR_ADJUSTMENT_RATIO, $result->factorsStack[1]->source);
        $this->assertEquals(0.9, $result->factorsStack[1]->value);
        $this->assertEquals('dpt01', $result->factorsStack[1]->departmentSlug);
        $this->assertEquals('some desc one', $result->factorsStack[1]->description);

        $this->assertEquals(FactorSource::FACTOR_ADJUSTMENT_RATIO, $result->factorsStack[2]->source);
        $this->assertEquals(0.7, $result->factorsStack[2]->value);
        $this->assertEquals('dpt01', $result->factorsStack[2]->departmentSlug);
        $this->assertEquals('some desc two', $result->factorsStack[2]->description);
    }

    public function testShouldCalculateFactorInDepartmentBonusMode()
    {
        // Given
        $agreement = $this->makeAgreementLineWithFactor(1.0);
        // When
        $result = $this->calcUnderTest->calculate(
            $agreement,
            'dpt01',
            [
                $this->makeFactor(
                    FactorSource::FACTOR_ADJUSTMENT_BONUS,
                    0.2,
                    'dpt01',
                    'some desc one'
                ),
                $this->makeFactor(
                    FactorSource::FACTOR_ADJUSTMENT_BONUS,
                    0.5,
                    'dpt03',
                    'some desc one dpt03'
                ),
                $this->makeFactor(
                    FactorSource::FACTOR_ADJUSTMENT_BONUS,
                    0.3,
                    'dpt01',
                    'some desc two'
                ),
            ],
            FactorSource::FACTOR_ADJUSTMENT_BONUS
        );
        // Then
        $this->assertEquals(1.5, $result->factor);
        $this->assertCount(3, $result->factorsStack);

        $this->assertEquals(FactorSource::FACTOR_ADJUSTMENT_BONUS, $result->factorsStack[1]->source);
        $this->assertEquals(0.2, $result->factorsStack[1]->value);
        $this->assertEquals('dpt01', $result->factorsStack[1]->departmentSlug);
        $this->assertEquals('some desc one', $result->factorsStack[1]->description);

        $this->assertEquals(FactorSource::FACTOR_ADJUSTMENT_BONUS, $result->factorsStack[2]->source);
        $this->assertEquals(0.3, $result->factorsStack[2]->value);
        $this->assertEquals('dpt01', $result->factorsStack[2]->departmentSlug);
        $this->assertEquals('some desc two', $result->factorsStack[2]->description);
    }

    public function testShouldCalculateBonusAdjustmentAfterRatioAdjustment()
    {
        // Given
        $agreement = $this->makeAgreementLineWithFactor(1.0);
        // When
        $result = $this->calcUnderTest->calculate(
            $agreement,
            'dpt01',
            [
                $this->makeFactor(
                    FactorSource::FACTOR_ADJUSTMENT_BONUS,
                    0.2,
                    'dpt01',
                    'some desc two'
                ),
                $this->makeFactor(
                    FactorSource::FACTOR_ADJUSTMENT_RATIO,
                    0.5,
                    'dpt01',
                    'some desc one'
                ),
            ],
            FactorSource::FACTOR_ADJUSTMENT_BONUS
        );
        // Then
        $this->assertEquals(0.7, $result->factor);
        $this->assertCount(3, $result->factorsStack);

        $this->assertEquals(FactorSource::FACTOR_ADJUSTMENT_RATIO, $result->factorsStack[1]->source);
        $this->assertEquals(0.5, $result->factorsStack[1]->value);
        $this->assertEquals('dpt01', $result->factorsStack[1]->departmentSlug);
        $this->assertEquals('some desc one', $result->factorsStack[1]->description);

        $this->assertEquals(FactorSource::FACTOR_ADJUSTMENT_BONUS, $result->factorsStack[2]->source);
        $this->assertEquals(0.2, $result->factorsStack[2]->value);
        $this->assertEquals('dpt01', $result->factorsStack[2]->departmentSlug);
        $this->assertEquals('some desc two', $result->factorsStack[2]->description);
    }

    private function makeAgreementLineWithFactor(float $factor): AgreementLine
    {
        $agreementLine = new AgreementLine();
        $agreementLine->setFactor($factor);
        PrivateProperty::setId($agreementLine);
        return $agreementLine;
    }

    private function makeFactor(
        FactorSource $source,
        float $value,
        ?string $dpt = null,
        string $description = ''
    ): Factor {
        $factor = new Factor();
        PrivateProperty::setId($factor);
        $factor->setSource($source);
        $factor->setFactorValue($value);
        if ($dpt) {
            $factor->setDepartmentSlug($dpt);
        }
        $factor->setDescription($description);
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
