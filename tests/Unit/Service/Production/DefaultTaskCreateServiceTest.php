<?php

namespace App\Tests\Unit\Service\Production;

use App\DTO\Production\ProductionTaskDTO;
use App\Entity\AgreementLine;
use App\Entity\Production;
use App\Service\Production\DefaultTaskCreateService;
use PHPUnit\Framework\TestCase;

class DefaultTaskCreateServiceTest extends TestCase
{
    /** @var ProductionTaskDTO $task */
    private $task;
    /** @var DefaultTaskCreateService  */
    private $serviceUnderTest;
    /** @var AgreementLine */
    private $agreementLine;

    protected function setUp(): void
    {
        $this->task = new ProductionTaskDTO('some_slug', 'title', 0, null, null);
        $this->serviceUnderTest = new DefaultTaskCreateService();
        $this->agreementLine = new AgreementLine();
        $this->agreementLine->setConfirmedDate(new \DateTime('2021-05-30'));
    }

    public function testShouldReturnProductionEntity()
    {
        // Given && When && Then
        $result = $this->serviceUnderTest->create($this->task, $this->agreementLine);
        // Then
        $this->assertInstanceOf(Production::class, $result);
    }

    public function testShouldSetDateStartAtTodayWhenDateStartIsNotProvided()
    {
        // Given
        $this->task->setDateFrom(null);
        $today = (new \DateTime())->format('Y-m-d');
        // When
        $production = $this->serviceUnderTest->create($this->task, $this->agreementLine);
        // Then
        $this->assertEquals($today, $production->getDateStart()->format('Y-m-d'));
    }

    public function testShouldNotChangeDateStartWhenItIsProvided()
    {
        // Given
        $dateFrom = new \DateTime('2021-05-20');
        $this->task->setDateFrom($dateFrom);
        // When
        $production = $this->serviceUnderTest->create($this->task, $this->agreementLine);
        // Then
        $this->assertEquals($dateFrom, $production->getDateStart());
    }

    public function testShouldNotChangeDateEndWhenItIsProvided()
    {
        // Given
        $dateTo = new \DateTime('2021-05-20');
        $this->task->setDateTo($dateTo);
        // When
        $production = $this->serviceUnderTest->create($this->task, $this->agreementLine);
        // Then
        $this->assertEquals($dateTo, $production->getDateEnd());
    }

    public function testShouldSetDateEndAtDeadlineDateWhenDateEndIsNotProvided()
    {
        // Given
        $this->task->setDateTo(null);
        // When
        $production = $this->serviceUnderTest->create($this->task, $this->agreementLine);
        // Then
        $this->assertEquals(new \DateTime('2021-05-30'), $production->getDateEnd());
    }

    public function testShouldSetDateEndForDpt01At7DaysBeforeDeadline()
    {
        // Given
        $this->task->setTaskSlug('dpt01');
        $this->task->setDateTo(null);
        // When
        $production = $this->serviceUnderTest->create($this->task, $this->agreementLine);
        // Then
        $this->assertEquals(new \DateTime('2021-05-23'), $production->getDateEnd());
    }

    public function testShouldSetDateEndForDpt01NotEarlierThanDateStart()
    {
        // Given
        $this->task->setTaskSlug('dpt01');
        $this->task->setDateFrom(new \DateTime('2021-05-28'));
        $this->task->setDateTo(null);
        // When
        $production = $this->serviceUnderTest->create($this->task, $this->agreementLine);
        // Then
        $this->assertEquals(new \DateTime('2021-05-28'), $production->getDateEnd());
    }
}