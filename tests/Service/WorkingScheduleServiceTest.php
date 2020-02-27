<?php


namespace App\Tests\Service;
use App\Service\WorkingScheduleService;
use App\Test\KernelTestCase;

class WorkingScheduleServiceTest extends KernelTestCase
{
    private $em;

    protected function setUp(): void
    {
        $this->getDBConnection()->beginTransaction();
        $this->em = $this->getManager();
    }

    public function testShouldGetExceptionWhenPassedWrongParameterDuringInitialization(): void
    {
        $this->assertException(\Exception::class, function () {
            $scheduler = new WorkingScheduleService($this->em);
            $scheduler->initialize('');
        });

        $this->assertException(\Exception::class, function () {
            $scheduler = new WorkingScheduleService($this->em);
            $scheduler->initialize('22-02-2020');
        });

        $this->assertException(\Exception::class, function () {
            $scheduler = new WorkingScheduleService($this->em);
            $scheduler->initialize('2020-02');
        });
    }

    public function testShouldReturnAllHolidaysWithinDaterange()
    {
        $service = new WorkingScheduleService($this->getManager());
        $service->initialize('2020-02-09');
        $result = $service->getNotWorkingDays();

        $this->assertEquals([
            '2020-02-01', '2020-02-02', '2020-02-08', '2020-02-09', '2020-02-15', '2020-02-16',
            '2020-02-22', '2020-02-23', '2020-02-29'
            ], $result);

        $service->initialize('2020-05-09');
        $result = $service->getNotWorkingDays();

        $this->assertEquals([
            '2020-05-01', '2020-05-02', '2020-05-03', '2020-05-09', '2020-05-10', '2020-05-16',
            '2020-05-17', '2020-05-23', '2020-05-24', '2020-05-30', '2020-05-31'
        ], $result);
    }

    public function testShouldSetHolidayAsWorkingDay()
    {
        $service = new WorkingScheduleService($this->getManager());
        $service->initialize('2020-05-09');

        $service->setWorkingDay('2020-05-01');
        $this->assertNotContains('2020-05-01', $service->getNotWorkingDays());
    }

    public function testShouldSetNewFreeDay()
    {
        $service = new WorkingScheduleService($this->getManager());
        $service->initialize('2020-05-09');

        $service->setNotWorkingDay('2020-05-04');
        $this->assertContains('2020-05-04', $service->getNotWorkingDays());
    }
}