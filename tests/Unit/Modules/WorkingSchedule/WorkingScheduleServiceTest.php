<?php

namespace App\Tests\Unit\Modules\WorkingSchedule;

use App\Module\WorkingSchedule\Repository\WorkingScheduleRepository;
use App\Module\WorkingSchedule\Service\DefaultHolidaysProvider;
use App\Module\WorkingSchedule\Service\WorkingScheduleService;
use App\Module\WorkingSchedule\ValueObject\ScheduleDayType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WorkingScheduleServiceTest extends TestCase
{
    private WorkingScheduleRepository|MockObject $repository;
    private DefaultHolidaysProvider|MockObject $defaultHolidaysProvider;
    private WorkingScheduleService $sut;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->createMock(WorkingScheduleRepository::class);
        $this->defaultHolidaysProvider = new DefaultHolidaysProvider();
//            $this->createMock(DefaultHolidaysProvider::class);
        $this->sut = new WorkingScheduleService($this->repository, $this->defaultHolidaysProvider);
    }

    public function testShouldInitializeService(): void
    {
        $days = $this->sut->getSchedule(2025, 12, ScheduleDayType::Holiday);
        dump($days);
        $this->assertTrue(true);
    }
}