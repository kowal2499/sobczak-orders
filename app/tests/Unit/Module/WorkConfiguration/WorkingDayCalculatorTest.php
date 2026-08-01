<?php

namespace App\Tests\Unit\Module\WorkConfiguration;

use App\Module\WorkConfiguration\Entity\WorkSchedule;
use App\Module\WorkConfiguration\Repository\WorkScheduleRepository;
use App\Module\WorkConfiguration\Service\DefaultHolidaysProvider;
use App\Module\WorkConfiguration\Service\WorkingDayCalculator;
use App\Module\WorkConfiguration\Service\WorkScheduleService;
use App\Module\WorkConfiguration\ValueObject\ScheduleDayType;
use PHPUnit\Framework\TestCase;

class WorkingDayCalculatorTest extends TestCase
{
    public function testSkipsWeekendWhenShiftingForward(): void
    {
        // piątek 2026-05-15 + 1 dzień roboczy => poniedziałek 2026-05-18
        $this->assertSame(
            '2026-05-18',
            $this->makeCalculator()->shift(new \DateTime('2026-05-15'), 1)->format('Y-m-d')
        );
    }

    public function testSkipsWeekendWhenShiftingBackwards(): void
    {
        // poniedziałek 2026-05-18 - 1 dzień roboczy => piątek 2026-05-15
        $this->assertSame(
            '2026-05-15',
            $this->makeCalculator()->shift(new \DateTime('2026-05-18'), -1)->format('Y-m-d')
        );
    }

    public function testSkipsPublicHoliday(): void
    {
        // 2026-05-01 (piątek) to święto ustawowe, 2-3 maja to weekend
        // => czwartek 2026-04-30 + 1 dzień roboczy to poniedziałek 2026-05-04
        $this->assertSame(
            '2026-05-04',
            $this->makeCalculator()->shift(new \DateTime('2026-04-30'), 1)->format('Y-m-d')
        );
    }

    public function testSkipsCustomHolidayFromSchedule(): void
    {
        // firmowy dzień wolny we wtorek 2026-05-19
        $calculator = $this->makeCalculator([
            new WorkSchedule(
                date: new \DateTimeImmutable('2026-05-19'),
                dayType: ScheduleDayType::Holiday,
                description: 'dzień wolny firmowy',
            ),
        ]);

        $this->assertSame(
            '2026-05-20',
            $calculator->shift(new \DateTime('2026-05-18'), 1)->format('Y-m-d')
        );
    }

    public function testZeroShiftReturnsSameDayEvenIfFree(): void
    {
        // niedziela pozostaje niedzielą — zero oznacza brak przesunięcia, nie "najbliższy roboczy"
        $this->assertSame(
            '2026-05-17',
            $this->makeCalculator()->shift(new \DateTime('2026-05-17 15:30:00'), 0)->format('Y-m-d')
        );
    }

    public function testTruncatesTimeToMidnight(): void
    {
        $this->assertSame(
            '00:00:00',
            $this->makeCalculator()->shift(new \DateTime('2026-05-18 15:30:00'), 1)->format('H:i:s')
        );
    }

    public function testCrossesYearBoundary(): void
    {
        // 2026-12-31 (czwartek) + 1 dzień roboczy => 2027-01-04 (piątek 1 stycznia to święto)
        $this->assertSame(
            '2027-01-04',
            $this->makeCalculator()->shift(new \DateTime('2026-12-31'), 1)->format('Y-m-d')
        );
    }

    public function testCountsWorkingDaysExcludingWeekend(): void
    {
        // piątek 2026-05-15 -> środa 2026-05-20: pon, wt, śr = 3 dni robocze (weekend pominięty)
        $this->assertSame(
            3,
            $this->makeCalculator()->countWorkingDays(new \DateTime('2026-05-15'), new \DateTime('2026-05-20'))
        );
    }

    public function testCountsZeroForSameDayOrReversedRange(): void
    {
        $calculator = $this->makeCalculator();

        $this->assertSame(
            0,
            $calculator->countWorkingDays(new \DateTime('2026-05-20 08:00:00'), new \DateTime('2026-05-20 18:00:00'))
        );
        $this->assertSame(
            0,
            $calculator->countWorkingDays(new \DateTime('2026-05-20'), new \DateTime('2026-05-15'))
        );
    }

    /**
     * @param WorkSchedule[] $customHolidays
     */
    private function makeCalculator(array $customHolidays = []): WorkingDayCalculator
    {
        $repository = $this->createMock(WorkScheduleRepository::class);
        $repository->method('findHolidaysByRange')->willReturn($customHolidays);
        $repository->method('findWorkingDaysByRange')->willReturn([]);

        return new WorkingDayCalculator(
            new WorkScheduleService($repository, new DefaultHolidaysProvider())
        );
    }
}
