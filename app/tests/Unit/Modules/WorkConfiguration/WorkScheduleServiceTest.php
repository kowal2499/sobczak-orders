<?php

namespace App\Tests\Unit\Modules\WorkConfiguration;

use App\Module\WorkConfiguration\Entity\WorkSchedule;
use App\Module\WorkConfiguration\Repository\WorkScheduleRepository;
use App\Module\WorkConfiguration\Service\DefaultHolidaysProvider;
use App\Module\WorkConfiguration\Service\WorkScheduleService;
use App\Module\WorkConfiguration\ValueObject\ScheduleDayType;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WorkScheduleServiceTest extends TestCase
{
    private WorkScheduleRepository|MockObject $repository;
    private DefaultHolidaysProvider|MockObject $defaultHolidaysProvider;
    private WorkScheduleService $sut;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->createMock(WorkScheduleRepository::class);
        $this->defaultHolidaysProvider = new DefaultHolidaysProvider();
        $this->sut = new WorkScheduleService($this->repository, $this->defaultHolidaysProvider);
    }

    public function testShouldGetFreeDays(): void
    {
        // Given
        $days = array_map(
            fn (WorkSchedule $day) => $day->getDate()->format('Y-m-d'),
            $this->sut->getFreeDays(
                \DateTimeImmutable::createFromFormat('Y-m-d', '2025-12-01'),
                \DateTimeImmutable::createFromFormat('Y-m-d', '2025-12-31')
            )
        );
        // When & Then
        $this->assertSame($this->december2025Holidays(), $days);
    }

    public function testShouldGetWorkingDays(): void
    {
        // Given
        $workingDays = array_map(
            fn(WorkSchedule $day) => $day->getDate()->format('Y-m-d'),
            $this->sut->getWorkingDays(
                \DateTimeImmutable::createFromFormat('Y-m-d', '2025-12-01'),
                \DateTimeImmutable::createFromFormat('Y-m-d', '2025-12-31')
            )
        );
        // When & Then
        $this->assertSame([
            '2025-12-01',
            '2025-12-02',
            '2025-12-03',
            '2025-12-04',
            '2025-12-05',
            '2025-12-08',
            '2025-12-09',
            '2025-12-10',
            '2025-12-11',
            '2025-12-12',
            '2025-12-15',
            '2025-12-16',
            '2025-12-17',
            '2025-12-18',
            '2025-12-19',
            '2025-12-22',
            '2025-12-23',
            '2025-12-29',
            '2025-12-30',
            '2025-12-31',
        ], $workingDays);
    }

    public function testShouldRespectCustomHolidaysFromRepository(): void
    {
        // Given
        $holidays = $this->december2025Holidays([
            '2025-12-22', // custom holiday
            '2025-12-23', // custom holiday
        ]);

        $this->repository->expects($this->once())
            ->method('findHolidaysByRange')
            ->with(
                DateTimeImmutable::createFromFormat('Y-m-d', '2025-12-01'),
                DateTimeImmutable::createFromFormat('Y-m-d', '2025-12-31')
            )
            ->willReturn([
                $this->getEntity('2025-12-22', ScheduleDayType::Holiday),
                $this->getEntity('2025-12-23', ScheduleDayType::Holiday),
            ]);

        // When
        $days = $this->sut->getFreeDays(
            \DateTimeImmutable::createFromFormat('Y-m-d', '2025-12-01'),
            \DateTimeImmutable::createFromFormat('Y-m-d', '2025-12-31')
        );
        // Then
        $this->assertCount(count($holidays), $days);
        foreach ($days as $index => $day) {
            $this->assertEquals($day->getDate()->format('Y-m-d'), $holidays[$index]);
        }
    }

    public function testShouldAllowToTransformHolidayIntoWorkingDay(): void
    {
        // Given
        $this->repository->expects($this->once())
            ->method('findWorkingDaysByRange')
            ->with(
                DateTimeImmutable::createFromFormat('Y-m-d', '2025-12-01'),
                DateTimeImmutable::createFromFormat('Y-m-d', '2025-12-31'),
            )
            ->willReturn([
                $this->getEntity('2025-12-24', ScheduleDayType::Working),
                $this->getEntity('2025-12-25', ScheduleDayType::Working),
                $this->getEntity('2025-12-26', ScheduleDayType::Working),
            ])
        ;
        // When
        $days = $this->sut->getFreeDays(
            \DateTimeImmutable::createFromFormat('Y-m-d', '2025-12-01'),
            \DateTimeImmutable::createFromFormat('Y-m-d', '2025-12-31')
        );

        // Then
        $holidays = array_map(fn(WorkSchedule $day) => $day->getDate()->format('Y-m-d'), $days);
        $this->assertNotContains('2025-12-24', $holidays);
        $this->assertNotContains('2025-12-25', $holidays);
        $this->assertNotContains('2025-12-26', $holidays);
        $this->assertCount(count($this->december2025Holidays()) - 3, $days);
    }


    private function getEntity(string $date, ScheduleDayType $type): WorkSchedule
    {
        return new WorkSchedule(DateTimeImmutable::createFromFormat('Y-m-d', $date), $type);
    }

    private function december2025Holidays(array $additionalHolidays = []): array
    {
        $result = array_merge([
            '2025-12-06',
            '2025-12-07',
            '2025-12-13',
            '2025-12-14',
            '2025-12-20',
            '2025-12-21',
            '2025-12-24',
            '2025-12-25',
            '2025-12-26',
            '2025-12-27',
            '2025-12-28',
        ], $additionalHolidays);

        sort($result);
        return $result;
    }
}
