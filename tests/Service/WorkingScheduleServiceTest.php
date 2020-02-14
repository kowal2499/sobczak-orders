<?php


namespace App\Tests\Service;
use App\Entity\WorkingSchedule;
use App\Service\WorkingScheduleService;
use App\Test\KernelTestCase;
use PHPUnit\Framework\TestCase;

class WorkingScheduleServiceTest extends KernelTestCase
{
    protected function setUp(): void
    {

        $this->getDBConnection()->beginTransaction();

        $em = $this->getManager();
        $day = new WorkingSchedule();
        $day->setDate(new \DateTime('2020-05-11'))
            ->setIsWorking(true);

        $em->persist($day);
        $em->flush();

    }

    public function testShouldGetBeginningAndEndingOfTheMonthByGivenDay(): void
    {
        $date = new \DateTimeImmutable('2020-02-09');

        $result = WorkingScheduleService::getTimeRange($date);
        $this->assertEquals(new \DateTime('2020-02-01'), $result['start']);
        $this->assertEquals(new \DateTime('2020-02-29'), $result['end']);
        $this->assertGreaterThan($result['start'], $result['end']);
    }

    public function testShouldReturnAllHolidaysWithinDaterange()
    {
        $service = new WorkingScheduleService(null);
        $service->initialize('2020-02-09');
        $result = $service->getDefaultNotWorkingDays();

        $this->assertEquals([
            '2020-02-01', '2020-02-02', '2020-02-08', '2020-02-09', '2020-02-15', '2020-02-16',
            '2020-02-22', '2020-02-23', '2020-02-29'
            ], $result);

        $service->initialize('2020-05-09');
        $result = $service->getDefaultNotWorkingDays();

        $this->assertEquals([
            '2020-05-01', '2020-05-02', '2020-05-03', '2020-05-09', '2020-05-10', '2020-05-16',
            '2020-05-17', '2020-05-23', '2020-05-24', '2020-05-30', '2020-05-31'
        ], $result);
    }
}