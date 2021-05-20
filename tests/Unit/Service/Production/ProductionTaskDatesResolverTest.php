<?php

namespace App\Tests\Unit\Service\Production;

use App\DTO\Production\ProductionTaskDTO;
use App\Entity\AgreementLine;
use App\Entity\Production;
use App\Service\Production\ProductionTaskDatesResolver;
use PHPUnit\Framework\TestCase;

class ProductionTaskDatesResolverTest extends TestCase
{
    /** @var ProductionTaskDatesResolver  */
    private $serviceUnderTest;
    /** @var AgreementLine */
    private $agreementLine;
    /** @var Production */
    private $production;

    protected function setUp(): void
    {
        $this->serviceUnderTest = new ProductionTaskDatesResolver();
        $this->agreementLine = new AgreementLine();
        $this->production = new Production();
        $this->production->setAgreementLine($this->agreementLine);
        $this->agreementLine->setConfirmedDate(new \DateTime('2021-05-30'));
    }

    public function testShouldSetDateStartAtToday()
    {
        // Given
        $today = (new \DateTime())->format('Y-m-d');
        // When
        $resolvedDate = $this->serviceUnderTest->resolveDateFrom();
        // Then
        $this->assertEquals($today, $resolvedDate->format('Y-m-d'));
    }

    public function testShouldNotChangeDateStartWhenItIsProvided()
    {
        // Given
        $this->production->setDateStart(new \DateTime('2021-05-20'));
        // When
        $resolvedDate = $this->serviceUnderTest->resolveDateFrom($this->production);
        // Then
        $this->assertEquals(
            $resolvedDate->format('Y-m-d'),
            (new \DateTime('2021-05-20'))->format('Y-m-d')
        );
    }

    public function testShouldSetDateEndForDpt01At7DaysBeforeDeadline()
    {
        // Given
        $this->production->setDepartmentSlug('dpt01');
        // When
        $resolvedDate = $this->serviceUnderTest->resolveDateTo($this->production);
        // Then
        $this->assertEquals($resolvedDate, new \DateTime('2021-05-23'));
    }

    public function testShouldSetDateEndForDpt01NotEarlierThanDateStart()
    {
        // Given
        $this->production->setDepartmentSlug('dpt01');
        $this->production->setDateStart(new \DateTime('2021-05-28'));
        // When
        $resolvedDate = $this->serviceUnderTest->resolveDateTo($this->production);
        // Then
        $this->assertEquals($resolvedDate, new \DateTime('2021-05-28'));
    }
}