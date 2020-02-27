<?php


namespace App\Tests\Service;


use App\Service\CockpitIndicatorsService;
use App\Test\AgreementLineTestCase;

class CockpitIndicatorsServiceTest extends AgreementLineTestCase
{
    private $cockpitIndicatorsUnderTest;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cockpitIndicatorsUnderTest = new CockpitIndicatorsService($this->em, $this->agreementLineRepository);
    }

    public function testShouldBeInstanceOfCockpitIndicatorsService(): void
    {
        $this->assertInstanceOf(CockpitIndicatorsService::class, $this->cockpitIndicatorsUnderTest);
    }

    public function testShouldReturnArrayWithSpecifiedKeys()
    {
        $result = $this->cockpitIndicatorsUnderTest->calculate(new \DateTimeImmutable('2020-02-23'));
        $this->assertArrayHasKey('workingSchedule', $result);
        $this->assertArrayHasKey('workingDaysCount', $result['workingSchedule']);
        $this->assertArrayHasKey('factorsLimit', $result['workingSchedule']);
        $this->assertArrayHasKey('allOrders', $result);
        $this->assertArrayHasKey('totalFactors', $result['allOrders']);
        $this->assertArrayHasKey('finishedOrders', $result);
        $this->assertArrayHasKey('quantity', $result['finishedOrders']);
        $this->assertArrayHasKey('factors', $result['finishedOrders']);
        $this->assertArrayHasKey('notFinishedOrders', $result);
        $this->assertArrayHasKey('quantity', $result['notFinishedOrders']);
        $this->assertArrayHasKey('factors', $result['notFinishedOrders']);
    }

    public function testShouldFactorsSummaryCountByFactorBindDateAndSkipDeletedAndWithoutFactorBindDate()
    {
        $result = $this->cockpitIndicatorsUnderTest->calculate(new \DateTimeImmutable('2020-01-15'));
        $this->assertEquals(0.60, $result['allOrders']['totalFactors']);

        $result = $this->cockpitIndicatorsUnderTest->calculate(new \DateTimeImmutable('2020-02-15'));
        $this->assertEquals(0.47, $result['allOrders']['totalFactors']);
        $this->assertEquals(3, $result['allOrders']['total']);
    }

    public function testShouldCalcCompletedOrdersUsingFactorMethod()
    {
        $result = $this->cockpitIndicatorsUnderTest->calculate(new \DateTimeImmutable('2020-02-15'));
        $this->assertEquals(2, $result['finishedOrders']['quantity']);
        $this->assertEquals(0.42, $result['finishedOrders']['factors']);
    }
}