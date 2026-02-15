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

    public function testShouldReturnDateStartAsNullForAllTasksExceptDpt01()
    {
        // Given
        $production = new Production();
        $production->setDateStart(null);
        $production->setDateEnd(null);

        $deadline = new \DateTime('2021-06-30');

        // When & Then
        $production->setDepartmentSlug('dpt01');
        $resolvedDate = $this->serviceUnderTest->resolveDateFrom($production, $deadline);
        $this->assertNotEquals(null, $resolvedDate);

        $production->setDepartmentSlug('dpt02');
        $resolvedDate = $this->serviceUnderTest->resolveDateFrom($production, $deadline);
        $this->assertEquals(null, $resolvedDate);

        $production->setDepartmentSlug('dpt03');
        $resolvedDate = $this->serviceUnderTest->resolveDateFrom($production, $deadline);
        $this->assertEquals(null, $resolvedDate);

        $production->setDepartmentSlug('dpt04');
        $resolvedDate = $this->serviceUnderTest->resolveDateFrom($production, $deadline);
        $this->assertEquals(null, $resolvedDate);

        $production->setDepartmentSlug('dpt05');
        $resolvedDate = $this->serviceUnderTest->resolveDateFrom($production, $deadline);
        $this->assertEquals(null, $resolvedDate);
    }

    public function testShouldReturnDateStartForDpt01WhichIs7daysEarlierThanDeadline()
    {
        // Given
        $deadline = new \DateTime('2021-06-30');
        $production = new Production();
        $production->setDepartmentSlug('dpt01');

        // When
        $production->setDateStart($this->serviceUnderTest->resolveDateFrom($production, $deadline));

        // Then
        $this->assertEquals(
            (new \DateTime('2021-06-23'))->setTime(7, 0),
            $production->getDateStart()
        );
    }

    public function testShouldReturnDateEndWhichIsEqualToDeadlineDateForEachTasks()
    {
        // Given
        $production = new Production();
        $production->setDateStart(null);
        $production->setDateEnd(null);

        $deadline = new \DateTime('2021-06-30');

        // When & Then
        $production->setDepartmentSlug('dpt01');
        $resolvedDate = $this->serviceUnderTest->resolveDateTo($production, $deadline);
        $this->assertEquals($deadline, $resolvedDate);

        $production->setDepartmentSlug('dpt02');
        $resolvedDate = $this->serviceUnderTest->resolveDateTo($production, $deadline);
        $this->assertEquals($deadline, $resolvedDate);

        $production->setDepartmentSlug('dpt03');
        $resolvedDate = $this->serviceUnderTest->resolveDateTo($production, $deadline);
        $this->assertEquals($deadline, $resolvedDate);

        $production->setDepartmentSlug('dpt04');
        $resolvedDate = $this->serviceUnderTest->resolveDateTo($production, $deadline);
        $this->assertEquals($deadline, $resolvedDate);

        $production->setDepartmentSlug('dpt05');
        $resolvedDate = $this->serviceUnderTest->resolveDateTo($production, $deadline);
        $this->assertEquals($deadline, $resolvedDate);
    }

}